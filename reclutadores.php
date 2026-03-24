<?php
$page      = 'reclutadores';
$pageTitle = 'Reclutadores';
include("conexion.php");

$id_empresa = $_SESSION['idempresa'] ?? null;
if (!$id_empresa) { header("Location: login.php"); exit; }
$id_empresa = intval($id_empresa);

// --- ACCIÓN: Crear reclutador ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_reclutador') {
    $nombre         = trim(mysqli_real_escape_string($conexion, $_POST['nombre']   ?? ''));
    $apellido       = trim(mysqli_real_escape_string($conexion, $_POST['apellido'] ?? ''));
    $email          = trim(mysqli_real_escape_string($conexion, $_POST['email']    ?? ''));
    $telefono       = trim(mysqli_real_escape_string($conexion, $_POST['telefono'] ?? ''));
    $password_plain = trim($_POST['password'] ?? '');

    $errores = [];
    if (!$nombre)   $errores[] = 'El nombre es obligatorio.';
    if (!$apellido) $errores[] = 'El apellido es obligatorio.';
    if (!$email)    $errores[] = 'El email es obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido.';
    if (!$password_plain)            $errores[] = 'La contraseña es obligatoria.';
    if (strlen($password_plain) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';

    if (!$errores) {
        $check = mysqli_query($conexion, "SELECT id_usuario FROM users WHERE email = '$email' LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) $errores[] = 'Ya existe un usuario con ese email.';
    }

    if ($errores) {
        $toast_msg  = implode(' ', $errores);
        $toast_tipo = 'error';
        header("Location: reclutadores.php?toast=" . urlencode($toast_msg) . "&tipo=error&reopen=crear");
        exit;
    } else {
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
        $sql_user = "INSERT INTO users (email, contrasena, tipo, id_empresa, estado)
                     VALUES ('$email', '$password_hash', 4, $id_empresa, 'activo')";
        if (mysqli_query($conexion, $sql_user)) {
            $nuevo_id    = mysqli_insert_id($conexion);
            $sql_rec_ins = "INSERT INTO reclutadores (id_usuario, id_empresa, nombre, apellido, telefono, fecha_alta)
                            VALUES ($nuevo_id, $id_empresa, '$nombre', '$apellido', '$telefono', CURDATE())";
            if (mysqli_query($conexion, $sql_rec_ins)) {
                header("Location: reclutadores.php?toast=ok_creado");
            } else {
                mysqli_query($conexion, "DELETE FROM users WHERE id_usuario = $nuevo_id");
                header("Location: reclutadores.php?toast=" . urlencode('Error al registrar reclutador: ' . mysqli_error($conexion)) . "&tipo=error&reopen=crear");
            }
        } else {
            header("Location: reclutadores.php?toast=" . urlencode('Error al crear usuario: ' . mysqli_error($conexion)) . "&tipo=error&reopen=crear");
        }
        exit;
    }
}

// --- ACCIÓN: Editar reclutador ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_reclutador') {
    $id_rec   = intval($_POST['id_usuario'] ?? 0);
    $nombre   = trim(mysqli_real_escape_string($conexion, $_POST['nombre']   ?? ''));
    $apellido = trim(mysqli_real_escape_string($conexion, $_POST['apellido'] ?? ''));
    $telefono = trim(mysqli_real_escape_string($conexion, $_POST['telefono'] ?? ''));

    $errores = [];
    if (!$nombre)   $errores[] = 'El nombre es obligatorio.';
    if (!$apellido) $errores[] = 'El apellido es obligatorio.';

    $check = mysqli_query($conexion, "SELECT id_reclutador FROM reclutadores
                                      WHERE id_usuario = $id_rec AND id_empresa = $id_empresa");
    if (!$check || mysqli_num_rows($check) === 0) $errores[] = 'Reclutador no encontrado.';

    if ($errores) {
        header("Location: reclutadores.php?toast=" . urlencode(implode(' ', $errores)) . "&tipo=error&reopen=editar&id=$id_rec");
        exit;
    }

    $sql_upd = "UPDATE reclutadores SET nombre='$nombre', apellido='$apellido', telefono='$telefono'
                WHERE id_usuario = $id_rec AND id_empresa = $id_empresa";
    if (mysqli_query($conexion, $sql_upd)) {
        header("Location: reclutadores.php?toast=ok_editado");
    } else {
        header("Location: reclutadores.php?toast=" . urlencode('Error al actualizar: ' . mysqli_error($conexion)) . "&tipo=error");
    }
    exit;
}

// --- ACCIÓN: Cambiar estado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'toggle_estado') {
    $id_rec       = intval($_POST['id_usuario'] ?? 0);
    $estado_actual = $_POST['estado_actual'] ?? '';
    $nuevo_estado = ($estado_actual === 'activo') ? 'inactivo' : 'activo';
    $check = mysqli_query($conexion, "SELECT r.id_reclutador FROM reclutadores r
                                      INNER JOIN users u ON r.id_usuario = u.id_usuario
                                      WHERE r.id_usuario = $id_rec AND r.id_empresa = $id_empresa");
    if ($check && mysqli_num_rows($check) > 0) {
        mysqli_query($conexion, "UPDATE users SET estado = '$nuevo_estado' WHERE id_usuario = $id_rec");
        $toast = ($nuevo_estado === 'activo') ? 'ok_activado' : 'ok_baja';
        header("Location: reclutadores.php?toast=$toast&estado=" . ($nuevo_estado === 'activo' ? 'inactivo' : 'activo'));
    } else {
        header("Location: reclutadores.php");
    }
    exit;
}

// --- ACCIÓN: Eliminar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_reclutador') {
    $id_rec = intval($_POST['id_usuario'] ?? 0);
    $check  = mysqli_query($conexion, "SELECT id_reclutador FROM reclutadores
                                       WHERE id_usuario = $id_rec AND id_empresa = $id_empresa");
    if ($check && mysqli_num_rows($check) > 0) {
        mysqli_query($conexion, "DELETE FROM reclutadores WHERE id_usuario = $id_rec");
        mysqli_query($conexion, "DELETE FROM users WHERE id_usuario = $id_rec");
    }
    header("Location: reclutadores.php?toast=ok_eliminado");
    exit;
}

// --- Filtro estado ---
$filtro_estado = $_GET['estado'] ?? 'activo';
$where_estado  = '';
if ($filtro_estado === 'activo')   $where_estado = "AND u.estado = 'activo'";
if ($filtro_estado === 'inactivo') $where_estado = "AND u.estado = 'inactivo'";

// --- Contadores ---
$res_cnt   = mysqli_query($conexion, "SELECT u.estado, COUNT(*) as total FROM reclutadores r
    INNER JOIN users u ON r.id_usuario = u.id_usuario
    WHERE r.id_empresa = $id_empresa GROUP BY u.estado");
$cnt       = ['activo' => 0, 'inactivo' => 0];
$cnt_total = 0;
while ($c = mysqli_fetch_assoc($res_cnt)) {
    if (isset($cnt[$c['estado']])) $cnt[$c['estado']] = intval($c['total']);
    $cnt_total += intval($c['total']);
}

// --- Listar reclutadores ---
$sql_rec = "SELECT r.id_reclutador, r.nombre, r.apellido, r.telefono, r.fecha_alta,
                   u.id_usuario, u.email, u.estado
            FROM reclutadores r
            INNER JOIN users u ON r.id_usuario = u.id_usuario
            WHERE r.id_empresa = $id_empresa $where_estado
            ORDER BY r.id_reclutador DESC";
$res_rec  = mysqli_query($conexion, $sql_rec);
if (!$res_rec) die("<b>ERROR reclutadores:</b> " . mysqli_error($conexion));
$reclutadores = [];
while ($r = mysqli_fetch_assoc($res_rec)) $reclutadores[] = $r;

// --- Toast desde GET ---
$toast_claves = [
    'ok_creado'  => ['msg' => '✓ Reclutador creado correctamente.',      'tipo' => 'success'],
    'ok_editado' => ['msg' => '✓ Reclutador actualizado correctamente.', 'tipo' => 'success'],
    'ok_activado'=> ['msg' => ' Reclutador activado correctamente.',    'tipo' => 'success'],
    'ok_baja'    => ['msg' => ' Reclutador dado de baja correctamente.','tipo' => 'warning'],
    'ok_eliminado'=> ['msg' => ' Reclutador eliminado correctamente.',  'tipo' => 'success'],
];
$toast_get  = $_GET['toast'] ?? '';
$toast_tipo = $_GET['tipo']  ?? 'success';
if (isset($toast_claves[$toast_get])) {
    $toast_msg  = $toast_claves[$toast_get]['msg'];
    $toast_tipo = $toast_claves[$toast_get]['tipo'];
} elseif ($toast_get) {
    $toast_msg = htmlspecialchars(urldecode($toast_get));
}

// Para reabrir modal en caso de error
$reopen    = $_GET['reopen'] ?? '';
$reopen_id = intval($_GET['id'] ?? 0);

include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <div class="bg-gray-50 border-b border-gray-200 px-6 py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Reclutadores</h1>
                <p class="text-sm text-gray-500 mt-0.5">Gestioná los usuarios con acceso al panel de tu empresa</p>
            </div>
            <button onclick="abrirModalCrear()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Agregar reclutador
            </button>
        </div>
    </div>

    <div class="px-6 py-6 max-w-5xl">

        <!-- Pills filtro -->
        <div class="flex flex-wrap items-center gap-2 mb-5">
            <?php
            $pills = [
                'activo'   => ['label' => 'Activos', 'cnt' => $cnt['activo'],   'active' => 'bg-green-500 text-white border-green-500'],
                'inactivo' => ['label' => 'De baja', 'cnt' => $cnt['inactivo'], 'active' => 'bg-gray-500 text-white border-gray-500'],
                ''         => ['label' => 'Todos',   'cnt' => $cnt_total,       'active' => 'bg-indigo-600 text-white border-indigo-600'],
            ];
            foreach ($pills as $val => $pi):
                $es_activo = ($filtro_estado === $val);
                $href = 'reclutadores.php' . ($val ? '?estado=' . $val : '');
            ?>
                <a href="<?= $href ?>"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium border transition
                    <?= $es_activo ? $pi['active'] : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' ?>">
                    <?= $pi['label'] ?>
                    <span class="<?= $es_activo ? 'bg-white bg-opacity-25 text-white' : 'bg-gray-100 text-gray-600' ?> text-xs font-bold px-1.5 py-0.5 rounded-full">
                        <?= $pi['cnt'] ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Lista -->
        <?php if (empty($reclutadores)): ?>
            <div class="bg-white border border-dashed border-gray-300 rounded-2xl p-16 text-center">
                <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <p class="text-gray-500 font-medium text-lg mb-1">Sin reclutadores aún</p>
                <p class="text-gray-400 text-sm mb-5">Agregá el primer reclutador para que pueda gestionar postulantes y ofertas</p>
                <button onclick="abrirModalCrear()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar reclutador
                </button>
            </div>
        <?php else: ?>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Reclutador</th>
                            <th class="text-left px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Fecha de creación</th>
                            <th class="text-left px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Teléfono</th>
                            <th class="text-center px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="text-right px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($reclutadores as $rec):
                            $nombre_rec = ($rec['nombre'] || $rec['apellido'])
                                ? htmlspecialchars(ucwords(strtolower(trim($rec['nombre'] . ' ' . $rec['apellido']))))
                                : 'Sin nombre';
                            $initials = strtoupper(
                                ($rec['nombre']   ? substr($rec['nombre'],   0, 1) : '') .
                                ($rec['apellido'] ? substr($rec['apellido'], 0, 1) : '?')
                            );
                            $activo = ($rec['estado'] === 'activo');
                        ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-bold text-indigo-600"><?= $initials ?></span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate"><?= $nombre_rec ?></p>
                                            <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($rec['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 hidden md:table-cell text-gray-600">
                                    <?= !empty($rec['fecha_alta']) ? date('d/m/Y', strtotime($rec['fecha_alta'])) : '<span class="text-gray-300">—</span>' ?>
                                </td>
                                <td class="px-5 py-4 hidden lg:table-cell text-gray-500">
                                    <?= !empty($rec['telefono']) ? htmlspecialchars($rec['telefono']) : '<span class="text-gray-300">—</span>' ?>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <?php if ($activo): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full border border-green-200">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-500 text-xs font-semibold rounded-full border border-gray-200">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Desactivado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($activo): ?>
                                            <button type="button"
                                                onclick="abrirModalBaja(<?= $rec['id_usuario'] ?>, '<?= htmlspecialchars($nombre_rec, ENT_QUOTES) ?>')"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium border rounded-lg transition border-gray-300 text-gray-600 hover:bg-gray-50">
                                                Dar de baja
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="abrirModalActivar(<?= $rec['id_usuario'] ?>, '<?= htmlspecialchars($nombre_rec, ENT_QUOTES) ?>')"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium border rounded-lg transition border-green-300 text-green-700 bg-green-50 hover:bg-green-100">
                                                Activar
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="abrirModalEditar(<?= $rec['id_usuario'] ?>, '<?= addslashes($rec['nombre']) ?>', '<?= addslashes($rec['apellido']) ?>', '<?= addslashes($rec['telefono'] ?? '') ?>')"
                                            class="inline-flex items-center p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button onclick="confirmarEliminar(<?= $rec['id_usuario'] ?>, '<?= htmlspecialchars($nombre_rec, ENT_QUOTES) ?>')"
                                            class="inline-flex items-center p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== MODAL ACTIVAR ===== -->
<div id="modalActivar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Activar reclutador</h3>
                <p class="text-xs text-gray-400 mt-0.5">El reclutador recuperará acceso al panel</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-600">¿Estás seguro que querés activar a <span id="activar-nombre" class="font-semibold text-gray-900"></span>?</p>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="toggle_estado">
            <input type="hidden" name="estado_actual" value="inactivo">
            <input type="hidden" name="id_usuario" id="activar-id">
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalActivar()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Sí, activar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL DAR DE BAJA ===== -->
<div id="modalBaja" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Dar de baja</h3>
                <p class="text-xs text-gray-400 mt-0.5">El reclutador perderá acceso al panel</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-600">¿Estás seguro que querés dar de baja a <span id="baja-nombre" class="font-semibold text-gray-900"></span>?</p>
            <p class="text-xs text-gray-400 mt-2">Podés volver a activarlo en cualquier momento.</p>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="toggle_estado">
            <input type="hidden" name="estado_actual" value="activo">
            <input type="hidden" name="id_usuario" id="baja-id">
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalBaja()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Sí, dar de baja
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL CREAR RECLUTADOR ===== -->
<div id="modalCrear" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900">Nuevo reclutador</h3>
            </div>
            <button onclick="cerrarModalCrear()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="crear_reclutador">
            <div class="px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" required placeholder="Juan"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Apellido <span class="text-red-500">*</span></label>
                        <input type="text" name="apellido" required placeholder="Pérez"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required placeholder="juan@empresa.com"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Teléfono</label>
                    <input type="text" name="telefono" placeholder="Ej: 3834000000"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password" id="inputPassword" required placeholder="Mínimo 6 caracteres"
                            class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <button type="button" onclick="togglePassword('inputPassword')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">El reclutador podrá cambiarla desde su perfil.</p>
                </div>
                <div class="flex items-start gap-2.5 p-3 bg-indigo-50 border border-indigo-100 rounded-xl">
                    <svg class="w-4 h-4 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs text-indigo-700">El reclutador tendrá acceso al panel de tu empresa para gestionar postulantes y ofertas.</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalCrear()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Crear reclutador
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL EDITAR RECLUTADOR ===== -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between rounded-t-2xl">
            <h3 class="font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar reclutador
            </h3>
            <button onclick="cerrarModalEditar()" class="text-white hover:text-indigo-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="editar_reclutador">
            <input type="hidden" name="id_usuario" id="editar-id">
            <div class="px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" id="editar-nombre" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Apellido <span class="text-red-500">*</span></label>
                        <input type="text" name="apellido" id="editar-apellido" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Teléfono</label>
                    <input type="text" name="telefono" id="editar-telefono" placeholder="Ej: 3834000000"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3">
                    <p class="text-xs text-gray-500"><span class="font-semibold text-gray-600">Nota:</span> Para cambiar la contraseña, el reclutador debe hacerlo desde su propio perfil.</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEditar()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Descartar cambios</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL CONFIRMAR ELIMINAR ===== -->
<div id="modalEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-11 h-11 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Eliminar reclutador</h3>
                    <p id="eliminar-nombre" class="text-sm text-gray-500 mt-0.5"></p>
                </div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-5">
                <p class="text-sm text-red-700 font-medium">Esta acción eliminará permanentemente al reclutador. No se puede deshacer.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="eliminar_reclutador">
                <input type="hidden" name="id_usuario" id="eliminar-id">
                <div class="flex gap-3">
                    <button type="button" onclick="cerrarEliminar()"
                        class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold transition">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL CERRAR SESIÓN ===== -->
<div id="modalCerrarSesion" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Cerrar sesión</h3>
                <p class="text-xs text-gray-400 mt-0.5">Esta acción cerrará tu cuenta</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-600">¿Estás seguro que querés cerrar sesión?</p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
            <button type="button" onclick="cerrarModalSesion()"
                class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
            <a href="cerrar-session.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Sí, cerrar sesión
            </a>
        </div>
    </div>
</div>

<!-- Toast container — abajo a la derecha -->
<div id="toast-container" class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 items-end pointer-events-none [&>*]:pointer-events-auto"></div>

<script>
// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const id  = 'toast-' + Date.now();
    const cfg = {
        success: { border: 'border-green-200', bar: 'bg-green-500', icon: `<svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` },
        error:   { border: 'border-red-200',   bar: 'bg-red-400',   icon: `<svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` },
        warning: { border: 'border-amber-200', bar: 'bg-amber-400', icon: `<svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>` },
    };
    const c = cfg[type] || cfg.success;
    const t = document.createElement('div');
    t.id        = id;
    t.className = `flex items-center gap-3 bg-white border ${c.border} rounded-2xl shadow-lg px-4 py-3.5 min-w-[280px] max-w-sm translate-x-full opacity-0 transition-all duration-300 ease-out relative overflow-hidden`;
    t.innerHTML = `${c.icon}<p class="text-sm font-medium text-gray-800 flex-1">${msg}</p>
    <button onclick="removeToast('${id}')" class="text-gray-400 hover:text-gray-600 ml-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <div class="absolute bottom-0 left-0 h-0.5 w-full ${c.bar} origin-left" id="bar-${id}"></div>`;
    document.getElementById('toast-container').appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        t.classList.replace('translate-x-full', 'translate-x-0');
        t.classList.replace('opacity-0', 'opacity-100');
    }));
    document.getElementById('bar-' + id).style.cssText = 'transition:transform 4s linear;transform:scaleX(0)';
    setTimeout(() => removeToast(id), 4200);
}
function removeToast(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('translate-x-full', 'opacity-0');
    setTimeout(() => el.remove(), 300);
}

// ── Activar ────────────────────────────────────────────────────────────────────
function abrirModalActivar(id, nombre) {
    document.getElementById('activar-id').value           = id;
    document.getElementById('activar-nombre').textContent = nombre;
    document.getElementById('modalActivar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalActivar() {
    document.getElementById('modalActivar').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// ── Dar de baja ────────────────────────────────────────────────────────────────
function abrirModalBaja(id, nombre) {
    document.getElementById('baja-id').value           = id;
    document.getElementById('baja-nombre').textContent = nombre;
    document.getElementById('modalBaja').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalBaja() {
    document.getElementById('modalBaja').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// ── Crear ──────────────────────────────────────────────────────────────────────
function abrirModalCrear() {
    document.getElementById('modalCrear').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalCrear() {
    document.getElementById('modalCrear').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// ── Editar ─────────────────────────────────────────────────────────────────────
function abrirModalEditar(id, nombre, apellido, telefono) {
    document.getElementById('editar-id').value       = id;
    document.getElementById('editar-nombre').value   = nombre;
    document.getElementById('editar-apellido').value = apellido;
    document.getElementById('editar-telefono').value = telefono;
    document.getElementById('modalEditar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// ── Eliminar ───────────────────────────────────────────────────────────────────
function confirmarEliminar(id, nombre) {
    document.getElementById('eliminar-id').value              = id;
    document.getElementById('eliminar-nombre').textContent    = nombre;
    document.getElementById('modalEliminar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarEliminar() {
    document.getElementById('modalEliminar').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// ── Sesión ─────────────────────────────────────────────────────────────────────
function abrirModalSesion()  { document.getElementById('modalCerrarSesion').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function cerrarModalSesion() { document.getElementById('modalCerrarSesion').classList.add('hidden');    document.body.style.overflow = 'auto';   }

// ── Toggle password ────────────────────────────────────────────────────────────
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// ── Cerrar con Escape / clic exterior ─────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        cerrarModalBaja(); cerrarModalCrear(); cerrarModalEditar();
        cerrarEliminar(); cerrarModalSesion(); cerrarModalActivar();
    }
});
['modalBaja','modalCrear','modalEditar','modalEliminar','modalCerrarSesion','modalActivar'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) { this.classList.add('hidden'); document.body.style.overflow = 'auto'; }
    });
});

// ── Disparar toast y reabrir modal desde GET ───────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    <?php if (!empty($toast_msg)): ?>
    showToast('<?= addslashes($toast_msg) ?>', '<?= $toast_tipo ?>');
    <?php endif; ?>
    <?php if ($reopen === 'crear'): ?>
    abrirModalCrear();
    <?php elseif ($reopen === 'editar' && $reopen_id): ?>
    // Se reabre vacío — los datos se perdieron en el redirect, el usuario deberá rellenar de nuevo
    abrirModalEditar(<?= $reopen_id ?>, '', '', '');
    <?php endif; ?>
});
</script>