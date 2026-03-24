<?php
$page      = 'admin-reclutadores';
$pageTitle = 'Reclutadores';
include("conexion.php");

$ok_msg = '';
$err_msg = '';
function esc($c, $v)
{
    return mysqli_real_escape_string($c, trim($v ?? ''));
}

// ── FILTROS ───────────────────────────────────────────────────────────────────
$buscar     = trim($_GET['q']          ?? '');
$filtro_emp = intval($_GET['id_empresa'] ?? 0);
$filtro_est = $_GET['estado']           ?? 'activo';
$page_num   = max(1, intval($_GET['pag'] ?? 1));
$per_page   = 20;
$offset     = ($page_num - 1) * $per_page;

// ── ACCIONES POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Crear reclutador
    if (($_POST['accion'] ?? '') === 'crear_reclutador') {
        $nombre     = esc($conexion, $_POST['nombre']   ?? '');
        $apellido   = esc($conexion, $_POST['apellido'] ?? '');
        $email      = esc($conexion, $_POST['email']    ?? '');
        $telefono   = esc($conexion, $_POST['telefono'] ?? '');
        $id_emp     = intval($_POST['id_empresa_rec']   ?? 0);
        $pass_plain = trim($_POST['password']           ?? '');

        $errores = [];
        if (!$nombre)   $errores[] = 'El nombre es obligatorio.';
        if (!$apellido) $errores[] = 'El apellido es obligatorio.';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email no válido.';
        if (!$id_emp)   $errores[] = 'Debés seleccionar una empresa.';
        if (strlen($pass_plain) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';

        if (!$errores) {
            $chk = mysqli_query($conexion, "SELECT id_usuario FROM users WHERE email='$email' LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) $errores[] = 'Ya existe un usuario con ese email.';
        }

        if ($errores) {
            $err_msg = implode(' ', $errores);
        } else {
            $hash    = password_hash($pass_plain, PASSWORD_DEFAULT);
            $sql_usr = "INSERT INTO users (email, contrasena, tipo, id_empresa, estado)
                        VALUES ('$email','$hash',4,$id_emp,'activo')";
            if (mysqli_query($conexion, $sql_usr)) {
                $new_id  = mysqli_insert_id($conexion);
                $sql_rec = "INSERT INTO reclutadores (id_usuario, id_empresa, nombre, apellido, telefono, fecha_alta)
                            VALUES ($new_id, $id_emp, '$nombre', '$apellido', '$telefono', CURDATE())";
                if (mysqli_query($conexion, $sql_rec)) {
                    $ok_msg = '✓ Reclutador creado correctamente.';
                } else {
                    mysqli_query($conexion, "DELETE FROM users WHERE id_usuario=$new_id");
                    $err_msg = 'Error al registrar reclutador: ' . mysqli_error($conexion);
                }
            } else {
                $err_msg = 'Error al crear usuario: ' . mysqli_error($conexion);
            }
        }
    }

    // Editar reclutador
    if (($_POST['accion'] ?? '') === 'editar_reclutador') {
        $id_rec   = intval($_POST['id_usuario'] ?? 0);
        $nombre   = esc($conexion, $_POST['nombre']   ?? '');
        $apellido = esc($conexion, $_POST['apellido'] ?? '');
        $telefono = esc($conexion, $_POST['telefono'] ?? '');

        if (!$nombre || !$apellido) {
            $err_msg = 'Nombre y apellido son obligatorios.';
        } else {
            mysqli_query($conexion, "UPDATE reclutadores SET nombre='$nombre', apellido='$apellido', telefono='$telefono' WHERE id_usuario=$id_rec");
            $ok_msg = '✓ Reclutador actualizado correctamente.';
        }

        // Actualizar credenciales si se enviaron
        if (!$err_msg) {
            $email_nuevo = esc($conexion, $_POST['email_usuario']    ?? '');
            $pass_nuevo  = trim($_POST['password_nuevo']             ?? '');
            $pass_conf   = trim($_POST['password_confirmar']         ?? '');

            if ($email_nuevo || $pass_nuevo) {
                $credenciales_err = [];

                if ($email_nuevo && !filter_var($email_nuevo, FILTER_VALIDATE_EMAIL))
                    $credenciales_err[] = 'Email no válido.';

                if ($email_nuevo) {
                    $chk = mysqli_query($conexion, "SELECT id_usuario FROM users WHERE email='$email_nuevo' AND id_usuario != $id_rec LIMIT 1");
                    if ($chk && mysqli_num_rows($chk) > 0)
                        $credenciales_err[] = 'Ya existe un usuario con ese email.';
                }

                if ($pass_nuevo && strlen($pass_nuevo) < 6)
                    $credenciales_err[] = 'La contraseña debe tener al menos 6 caracteres.';

                if ($pass_nuevo && $pass_nuevo !== $pass_conf)
                    $credenciales_err[] = 'Las contraseñas no coinciden.';

                if ($credenciales_err) {
                    $err_msg = implode(' ', $credenciales_err);
                    $ok_msg  = '';
                } else {
                    $sets = [];
                    if ($email_nuevo) $sets[] = "email='$email_nuevo'";
                    if ($pass_nuevo)  $sets[] = "contrasena='" . password_hash($pass_nuevo, PASSWORD_DEFAULT) . "'";
                    if ($sets) {
                        mysqli_query($conexion, "UPDATE users SET " . implode(',', $sets) . " WHERE id_usuario=$id_rec AND tipo=4");
                        $ok_msg = '✓ Reclutador y credenciales actualizados correctamente.';
                    }
                }
            }
        }
    }

    // Toggle estado
    if (($_POST['accion'] ?? '') === 'toggle_estado') {
        $id_rec       = intval($_POST['id_usuario'] ?? 0);
        $nuevo_estado = ($_POST['estado_actual'] === 'activo') ? 'inactivo' : 'activo';
        mysqli_query($conexion, "UPDATE users SET estado='$nuevo_estado' WHERE id_usuario=$id_rec AND tipo=4");
        $ok_msg = $nuevo_estado === 'activo' ? '✓ Reclutador activado.' : '✓ Reclutador desactivado.';
    }

    // Eliminar reclutador
    if (($_POST['accion'] ?? '') === 'eliminar_reclutador') {
        $id_rec = intval($_POST['id_usuario'] ?? 0);
        mysqli_query($conexion, "DELETE FROM reclutadores WHERE id_usuario=$id_rec");
        mysqli_query($conexion, "DELETE FROM users WHERE id_usuario=$id_rec AND tipo=4");
        $ok_msg = '✓ Reclutador eliminado.';
    }
}

// ── Query base ────────────────────────────────────────────────────────────────
$where = ["1=1"];
if ($buscar)     $where[] = "(r.nombre LIKE '%" . esc($conexion, $buscar) . "%' OR r.apellido LIKE '%" . esc($conexion, $buscar) . "%' OR u.email LIKE '%" . esc($conexion, $buscar) . "%')";
if ($filtro_emp) $where[] = "r.id_empresa=$filtro_emp";
if ($filtro_est === 'activo')   $where[] = "u.estado='activo'";
if ($filtro_est === 'inactivo') $where[] = "u.estado='inactivo'";
$wsql = implode(' AND ', $where);

$total_res = mysqli_fetch_assoc(mysqli_query(
    $conexion,
    "SELECT COUNT(*) c FROM reclutadores r INNER JOIN users u ON r.id_usuario=u.id_usuario WHERE $wsql"
))['c'] ?? 0;
$total_pages = ceil($total_res / $per_page);

$res = mysqli_query(
    $conexion,
    "SELECT r.id_reclutador, r.nombre, r.apellido, r.telefono, r.fecha_alta,
            u.id_usuario, u.email, u.estado,
            e.id_empresa, e.nombre_empresa, e.logo AS logo_empresa
     FROM reclutadores r
     INNER JOIN users u   ON r.id_usuario  = u.id_usuario
     INNER JOIN empresa e ON r.id_empresa  = e.id_empresa
     WHERE $wsql
     ORDER BY r.id_reclutador DESC
     LIMIT $per_page OFFSET $offset"
);

// Contadores para pills
$cnt_activos   = mysqli_fetch_assoc(mysqli_query(
    $conexion,
    "SELECT COUNT(*) c FROM reclutadores r INNER JOIN users u ON r.id_usuario=u.id_usuario WHERE u.estado='activo'"   . ($filtro_emp ? " AND r.id_empresa=$filtro_emp" : '')
))['c'] ?? 0;
$cnt_inactivos = mysqli_fetch_assoc(mysqli_query(
    $conexion,
    "SELECT COUNT(*) c FROM reclutadores r INNER JOIN users u ON r.id_usuario=u.id_usuario WHERE u.estado='inactivo'" . ($filtro_emp ? " AND r.id_empresa=$filtro_emp" : '')
))['c'] ?? 0;
$cnt_todos     = $cnt_activos + $cnt_inactivos;

// Lista de empresas para el select del modal crear
$res_empresas = mysqli_query($conexion, "SELECT id_empresa, nombre_empresa FROM empresa WHERE estado='activo' ORDER BY nombre_empresa");

// Nombre de empresa filtrada (para el breadcrumb)
$empresa_filtrada_nombre = '';
if ($filtro_emp) {
    $r_ef = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT nombre_empresa FROM empresa WHERE id_empresa=$filtro_emp LIMIT 1"));
    $empresa_filtrada_nombre = $r_ef['nombre_empresa'] ?? '';
}

include("sidebar-admin.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Reclutadores</h1>
            <p class="text-gray-500 text-sm mt-0.5"><?= $total_res ?> reclutador(es) encontrado(s)</p>
        </div>
        <button onclick="abrirModalCrear()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo reclutador
        </button>
    </div>

    <!-- Toasts PHP -->
    <?php if ($ok_msg): ?><div id="php-ok-msg" class="hidden"><?= htmlspecialchars($ok_msg)  ?></div><?php endif; ?>
    <?php if ($err_msg): ?><div id="php-err-msg" class="hidden"><?= htmlspecialchars($err_msg) ?></div><?php endif; ?>

    <!-- Pills estado -->
    <div class="flex flex-wrap gap-2 mb-5">
        <?php
        $pills = [
            'activo'   => ['label' => 'Activos',  'count' => $cnt_activos],
            'inactivo' => ['label' => 'De baja',  'count' => $cnt_inactivos],
            'todos'    => ['label' => 'Todos',     'count' => $cnt_todos],
        ];
        foreach ($pills as $val => $p):
            $active = ($filtro_est === $val);
            $cls = $active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50';
            $qs  = http_build_query(array_filter(['q' => $buscar, 'id_empresa' => $filtro_emp ?: null, 'estado' => $val, 'pag' => 1]));
        ?>
            <a href="admin-reclutadores.php?<?= $qs ?>"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium transition <?= $cls ?>">
                <?= $p['label'] ?>
                <span class="<?= $active ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' ?> text-xs px-1.5 py-0.5 rounded-full font-semibold">
                    <?= $p['count'] ?>
                </span>
            </a>
        <?php endforeach; ?>

        <?php if ($empresa_filtrada_nombre): ?>
            <a href="admin-reclutadores.php"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <?= htmlspecialchars($empresa_filtrada_nombre) ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 mb-5">
        <input type="hidden" name="estado" value="<?= htmlspecialchars($filtro_est) ?>">
        <?php if ($filtro_emp): ?>
            <input type="hidden" name="id_empresa" value="<?= $filtro_emp ?>">
        <?php endif; ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>"
                    placeholder="Buscar por nombre, apellido o email..."
                    class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>
            <select name="id_empresa" class="px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="0">Todas las empresas</option>
                <?php mysqli_data_seek($res_empresas, 0);
                while ($emp = mysqli_fetch_assoc($res_empresas)): ?>
                    <option value="<?= $emp['id_empresa'] ?>" <?= $filtro_emp == $emp['id_empresa'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['nombre_empresa']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="flex gap-2 mt-3">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">Buscar</button>
            <a href="admin-reclutadores.php" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition">Limpiar</a>
        </div>
    </form>

    <!-- Tabla -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden min-h-[450px] flex flex-col">
        <div class="overflow-x-auto flex-grow">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Reclutador</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Empresa</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Teléfono</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Alta</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php if (!$res || mysqli_num_rows($res) === 0): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-gray-400 text-sm">No se encontraron reclutadores</p>
                                <?php if ($buscar || $filtro_emp): ?>
                                    <a href="admin-reclutadores.php" class="text-xs text-indigo-600 hover:underline mt-1 inline-block">Limpiar filtros</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: while ($rec = mysqli_fetch_assoc($res)):
                        $nombre_rec = htmlspecialchars(ucwords(strtolower(trim(($rec['nombre'] ?? '') . ' ' . ($rec['apellido'] ?? '')))));
                        $initials   = strtoupper(substr($rec['nombre'] ?? 'R', 0, 1) . substr($rec['apellido'] ?? '?', 0, 1));
                        $activo     = ($rec['estado'] === 'activo');
                    ?>
                        <tr class="hover:bg-gray-50 transition <?= !$activo ? 'opacity-60 bg-gray-50/50' : '' ?>">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 border border-indigo-200">
                                        <span class="text-xs font-bold text-indigo-600"><?= $initials ?></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-800 truncate"><?= $nombre_rec ?: 'Sin nombre' ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($rec['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell">
                                <div class="flex items-center gap-2">
                                    <?php if ($rec['logo_empresa']): ?>
                                        <img src="<?= htmlspecialchars($rec['logo_empresa']) ?>" class="w-6 h-6 rounded object-contain border border-gray-200 flex-shrink-0" alt="">
                                    <?php else: ?>
                                        <div class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center text-gray-500 text-[10px] font-bold flex-shrink-0">
                                            <?= strtoupper(substr($rec['nombre_empresa'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-sm text-gray-700 truncate"><?= htmlspecialchars($rec['nombre_empresa']) ?></span>
                                </div>
                            </td>
                            <td class="px-5 py-4 hidden lg:table-cell text-gray-500 text-sm">
                                <?= !empty($rec['telefono']) ? htmlspecialchars($rec['telefono']) : '<span class="text-gray-300">—</span>' ?>
                            </td>
                            <td class="px-4 py-4 text-center text-xs text-gray-500 hidden md:table-cell">
                                <?= !empty($rec['fecha_alta']) ? date('d/m/Y', strtotime($rec['fecha_alta'])) : '—' ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <?php if ($activo): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Activo
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-500 border border-gray-300 rounded-full text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>De baja
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="relative inline-block text-left">
                                    <button type="button"
                                        onclick="toggleDropdown(event, 'drop-rec-<?= $rec['id_usuario'] ?>')"
                                        class="flex items-center justify-center w-8 h-8 text-gray-500 rounded-full hover:bg-gray-100 focus:outline-none transition mx-auto">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>

                                    <div id="drop-rec-<?= $rec['id_usuario'] ?>"
                                        class="hidden absolute right-0 z-[100] mt-2 w-44 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <form method="POST" class="block w-full">
                                                <input type="hidden" name="accion" value="toggle_estado">
                                                <input type="hidden" name="id_usuario" value="<?= $rec['id_usuario'] ?>">
                                                <input type="hidden" name="estado_actual" value="<?= $rec['estado'] ?>">
                                                <button type="submit"
                                                    class="flex w-full px-4 py-2 text-sm text-left transition <?= $activo ? 'text-orange-700 hover:bg-orange-50' : 'text-green-700 hover:bg-green-50' ?>">
                                                    <?= $activo ? 'Dar de baja' : 'Activar usuario' ?>
                                                </button>
                                            </form>
                                            <button type="button"
                                                onclick="abrirModalEditar(<?= $rec['id_usuario'] ?>, '<?= addslashes($rec['nombre'] ?? '') ?>', '<?= addslashes($rec['apellido'] ?? '') ?>', '<?= addslashes($rec['telefono'] ?? '') ?>')"
                                                class="flex w-full px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition text-left">
                                                Editar datos
                                            </button>
                                        </div>
                                        <div class="py-1">
                                            <button type="button"
                                                onclick="confirmarEliminar(<?= $rec['id_usuario'] ?>, '<?= htmlspecialchars($nombre_rec, ENT_QUOTES) ?>')"
                                                class="flex w-full px-4 py-2 text-sm text-red-700 hover:bg-red-50 transition font-medium text-left">
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-gray-500">
                    Página <span class="font-semibold text-gray-700"><?= $page_num ?></span> de <span class="font-semibold text-gray-700"><?= $total_pages ?></span>
                </p>
                <nav class="flex items-center gap-1">
                    <?php
                    $base_qs = array_filter(['q' => $buscar, 'id_empresa' => $filtro_emp ?: null, 'estado' => $filtro_est]);
                    if ($page_num > 1): ?>
                        <a href="?<?= http_build_query(array_merge($base_qs, ['pag' => $page_num - 1])) ?>"
                           class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php
                    $start = max(1, $page_num - 2);
                    $end   = min($total_pages, $page_num + 2);
                    for ($i = $start; $i <= $end; $i++):
                        $is_active = ($i === $page_num);
                    ?>
                        <a href="?<?= http_build_query(array_merge($base_qs, ['pag' => $i])) ?>"
                           class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium transition
                           <?= $is_active ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100 border border-transparent' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page_num < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($base_qs, ['pag' => $page_num + 1])) ?>"
                           class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL CREAR RECLUTADOR
═══════════════════════════════════════════════════════════════════════════ -->
<div id="modalCrear" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between rounded-t-2xl">
            <h3 class="font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Nuevo reclutador
            </h3>
            <button onclick="cerrarModalCrear()" class="text-white hover:text-indigo-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="crear_reclutador">
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Empresa <span class="text-red-500">*</span></label>
                    <select name="id_empresa_rec" id="crear-empresa" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccioná una empresa</option>
                        <?php mysqli_data_seek($res_empresas, 0);
                        while ($emp = mysqli_fetch_assoc($res_empresas)): ?>
                            <option value="<?= $emp['id_empresa'] ?>" <?= $filtro_emp == $emp['id_empresa'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['nombre_empresa']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
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
                        <input type="password" name="password" id="crear-pass" required placeholder="Mínimo 6 caracteres"
                            class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <button type="button" onclick="togglePass('crear-pass')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">El reclutador podrá cambiarla desde su perfil.</p>
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

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL EDITAR RECLUTADOR
═══════════════════════════════════════════════════════════════════════════ -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between rounded-t-2xl flex-shrink-0">
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
        <form method="POST" class="overflow-y-auto flex-1">
            <input type="hidden" name="accion" value="editar_reclutador">
            <input type="hidden" name="id_usuario" id="editar-id">
            <div class="p-6 space-y-4">

                <!-- Datos personales -->
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Datos personales</p>
                    <div class="space-y-3">
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
                    </div>
                </div>

                <!-- Credenciales de acceso -->
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Credenciales de acceso</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email de acceso</label>
                            <input type="email" name="email_usuario" id="editar-email-usuario"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                placeholder="Dejá vacío para no modificar">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nueva contraseña</label>
                                <div class="relative">
                                    <input type="password" name="password_nuevo" id="editar-pass"
                                        class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                        placeholder="Dejá vacío para no modificar">
                                    <button type="button" onclick="togglePass('editar-pass')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirmar contraseña</label>
                                <div class="relative">
                                    <input type="password" name="password_confirmar" id="editar-pass-confirm"
                                        class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                        placeholder="Repetí la nueva">
                                    <button type="button" onclick="togglePass('editar-pass-confirm')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400">Dejá los campos vacíos si no querés modificar las credenciales.</p>
                    </div>
                </div>

            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3 flex-shrink-0">
                <button type="button" onclick="cerrarModalEditar()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
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

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL CONFIRMAR ELIMINAR
═══════════════════════════════════════════════════════════════════════════ -->
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
            <form method="POST" id="form-eliminar">
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

</main>
</div>

<!-- ── MODAL CERRAR SESIÓN ─────────────────────────────────────────────────── -->
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

<!-- Toast container -->
<div id="toast-container" class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 items-end pointer-events-none [&>*]:pointer-events-auto"></div>

<script>
    function toggleDropdown(event, id) {
        event.stopPropagation();
        document.querySelectorAll('[id^="drop-rec-"]').forEach(el => {
            if (el.id !== id) el.classList.add('hidden');
        });
        const dropdown = document.getElementById(id);
        dropdown.classList.toggle('hidden');
    }

    window.onclick = function(event) {
        if (!event.target.closest('button')) {
            document.querySelectorAll('[id^="drop-rec-"]').forEach(el => {
                el.classList.add('hidden');
            });
        }
    }

    // ── Modales ────────────────────────────────────────────────────────────────
    function abrirModalCrear() {
        document.getElementById('modalCrear').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function cerrarModalCrear() {
        document.getElementById('modalCrear').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    function cerrarModalEditar() {
        document.getElementById('modalEditar').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    function cerrarEliminar() {
        document.getElementById('modalEliminar').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    function abrirModalSesion() {
        document.getElementById('modalCerrarSesion').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function cerrarModalSesion() {
        document.getElementById('modalCerrarSesion').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function abrirModalEditar(id, nombre, apellido, telefono) {
        document.getElementById('editar-id').value      = id;
        document.getElementById('editar-nombre').value  = nombre;
        document.getElementById('editar-apellido').value = apellido;
        document.getElementById('editar-telefono').value = telefono;

        // Limpiar credenciales siempre al abrir
        document.getElementById('editar-email-usuario').value = '';
        document.getElementById('editar-pass').value          = '';
        document.getElementById('editar-pass-confirm').value  = '';

        document.getElementById('modalEditar').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function confirmarEliminar(id, nombre) {
        document.getElementById('eliminar-id').value      = id;
        document.getElementById('eliminar-nombre').textContent = nombre;
        document.getElementById('modalEliminar').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // ── Clic exterior + Escape ─────────────────────────────────────────────────
    ['modalCrear', 'modalEditar', 'modalEliminar', 'modalCerrarSesion'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            ['modalCrear', 'modalEditar', 'modalEliminar', 'modalCerrarSesion'].forEach(id => {
                document.getElementById(id)?.classList.add('hidden');
            });
            document.body.style.overflow = 'auto';
        }
    });

    // ── Toggle password ────────────────────────────────────────────────────────
    function togglePass(id) {
        const i = document.getElementById(id);
        i.type = i.type === 'password' ? 'text' : 'password';
    }

    // ── Toasts ─────────────────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const container = document.getElementById('toast-container');
        const id = 'toast-' + Date.now();
        const icons = {
            success: `<svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
            error:   `<svg class="w-5 h-5 text-red-400 flex-shrink-0"   fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        };
        const borders = { success: 'border-green-200', error: 'border-red-200' };
        const bars    = { success: 'bg-green-500',     error: 'bg-red-400'     };

        const toast = document.createElement('div');
        toast.id = id;
        toast.className = `flex items-center gap-3 bg-white border ${borders[type]} rounded-2xl shadow-lg px-4 py-3.5 min-w-[280px] max-w-sm
                           translate-x-full opacity-0 transition-all duration-300 ease-out relative overflow-hidden`;
        toast.innerHTML = `${icons[type]}<p class="text-sm font-medium text-gray-800 flex-1">${msg}</p>
        <button onclick="removeToast('${id}')" class="text-gray-400 hover:text-gray-600 ml-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="absolute bottom-0 left-0 h-0.5 w-full ${bars[type]} origin-left" id="bar-${id}"></div>`;
        container.appendChild(toast);
        requestAnimationFrame(() => requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        }));
        const bar = document.getElementById('bar-' + id);
        bar.style.transition = 'transform 4s linear';
        setTimeout(() => { bar.style.transform = 'scaleX(0)'; }, 50);
        setTimeout(() => removeToast(id), 4200);
    }

    function removeToast(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => el.remove(), 300);
    }

    window.addEventListener('DOMContentLoaded', () => {
        const ok  = document.getElementById('php-ok-msg');
        const err = document.getElementById('php-err-msg');
        if (ok  && ok.textContent.trim())  showToast(ok.textContent.trim(),  'success');
        if (err && err.textContent.trim()) showToast(err.textContent.trim(), 'error');

        <?php if ($err_msg && ($_POST['accion'] ?? '') === 'crear_reclutador'): ?>
            abrirModalCrear();
        <?php endif; ?>
        <?php if ($err_msg && ($_POST['accion'] ?? '') === 'editar_reclutador'): ?>
            abrirModalEditar(
                <?= intval($_POST['id_usuario'] ?? 0) ?>,
                '<?= addslashes($_POST['nombre']   ?? '') ?>',
                '<?= addslashes($_POST['apellido'] ?? '') ?>',
                '<?= addslashes($_POST['telefono'] ?? '') ?>'
            );
        <?php endif; ?>
    });
</script>