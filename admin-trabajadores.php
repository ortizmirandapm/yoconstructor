<?php
$page      = 'admin-trabajadores';
$pageTitle = 'Trabajadores';

include("conexion.php");

$ok_msg = '';
$err_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Crear trabajador ───────────────────────────────────────────────────────
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear_trabajador') {
        $nombre   = mysqli_real_escape_string($conexion, trim($_POST['nombre']        ?? ''));
        $apellido = mysqli_real_escape_string($conexion, trim($_POST['apellido']      ?? ''));
        $titulo   = mysqli_real_escape_string($conexion, trim($_POST['nombre_titulo'] ?? ''));
        $telefono = mysqli_real_escape_string($conexion, trim($_POST['telefono']      ?? ''));
        $dni      = mysqli_real_escape_string($conexion, trim($_POST['dni']           ?? ''));
        $id_prov  = intval($_POST['id_provincia_preferencia'] ?? 0);
        $email    = mysqli_real_escape_string($conexion, trim($_POST['email_usuario'] ?? ''));
        $pass     = trim($_POST['password_usuario'] ?? '');

        if (!$nombre || !$apellido) {
            $err_msg = 'Nombre y apellido son obligatorios.';
        } elseif (!$email) {
            $err_msg = 'El email es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err_msg = 'El email no tiene un formato válido.';
        } elseif (strlen($pass) < 6) {
            $err_msg = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $check = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_usuario FROM users WHERE email='$email' LIMIT 1"));
            if ($check) {
                $err_msg = 'Ya existe una cuenta con ese email.';
            } else {
                $sql_p = "INSERT INTO persona (nombre, apellido, nombre_titulo, telefono, dni, id_provincia_preferencia)
                          VALUES ('$nombre','$apellido','$titulo','$telefono','$dni'," . ($id_prov ?: 'NULL') . ")";
                if (mysqli_query($conexion, $sql_p)) {
                    $id_persona = mysqli_insert_id($conexion);
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $hash_esc = mysqli_real_escape_string($conexion, $hash);
                    $sql_u = "INSERT INTO users (email, contrasena, tipo, id_persona, estado, fecha_creacion)
                              VALUES ('$email','$hash_esc',2,$id_persona,'activo',NOW())";
                    if (mysqli_query($conexion, $sql_u)) {
                        $ok_msg = '✓ Trabajador creado correctamente.';
                    } else {
                        mysqli_query($conexion, "DELETE FROM persona WHERE id_persona=$id_persona");
                        $err_msg = 'Error al crear usuario: ' . mysqli_error($conexion);
                    }
                } else {
                    $err_msg = 'Error al crear perfil: ' . mysqli_error($conexion);
                }
            }
        }
    }

    if (isset($_POST['toggle_estado'])) {
        $id    = intval($_POST['id_usuario']);
        $nuevo = in_array($_POST['nuevo_estado'], ['activo', 'inactivo']) ? $_POST['nuevo_estado'] : 'inactivo';
        mysqli_query($conexion, "UPDATE users SET estado='$nuevo' WHERE id_usuario=$id AND tipo=2");
        $ok_msg = $nuevo === 'activo' ? 'Trabajador activado correctamente.' : 'Trabajador dado de baja correctamente.';
    }

    // ── Editar trabajador ──────────────────────────────────────────────────────
    if (isset($_POST['accion']) && $_POST['accion'] === 'editar_trabajador') {
        $id_persona = intval($_POST['id_persona']      ?? 0);
        $id_usuario = intval($_POST['id_usuario_edit'] ?? 0);
        $nombre     = mysqli_real_escape_string($conexion, trim($_POST['nombre']        ?? ''));
        $apellido   = mysqli_real_escape_string($conexion, trim($_POST['apellido']      ?? ''));
        $titulo     = mysqli_real_escape_string($conexion, trim($_POST['nombre_titulo'] ?? ''));
        $telefono   = mysqli_real_escape_string($conexion, trim($_POST['telefono']      ?? ''));
        $dni        = mysqli_real_escape_string($conexion, trim($_POST['dni']           ?? ''));
        $id_prov    = intval($_POST['id_provincia_preferencia'] ?? 0);

        if (!$nombre || !$apellido) {
            $err_msg = 'Nombre y apellido son obligatorios.';
        } elseif (!$id_persona) {
            $err_msg = 'No se encontró el perfil del trabajador.';
        } else {
            $sql = "UPDATE persona SET
                nombre        = '$nombre',
                apellido      = '$apellido',
                nombre_titulo = '$titulo',
                telefono      = '$telefono',
                dni           = '$dni',
                id_provincia_preferencia = " . ($id_prov ?: 'NULL') . "
                WHERE id_persona = $id_persona";
            if (mysqli_query($conexion, $sql)) {
                $ok_msg = '✓ Trabajador actualizado correctamente.';
            } else {
                $err_msg = 'Error al actualizar: ' . mysqli_error($conexion);
            }
        }

        // ── Actualizar credenciales si se enviaron ─────────────────────────────
        if (!$err_msg && $id_usuario) {
            $email_nuevo = mysqli_real_escape_string($conexion, trim($_POST['email_usuario']    ?? ''));
            $pass_nuevo  = trim($_POST['password_nuevo']                                        ?? '');
            $pass_conf   = trim($_POST['password_confirmar']                                    ?? '');

            if ($email_nuevo || $pass_nuevo) {
                $cred_err = [];

                if ($email_nuevo && !filter_var($email_nuevo, FILTER_VALIDATE_EMAIL))
                    $cred_err[] = 'Email no válido.';

                if ($email_nuevo) {
                    $chk = mysqli_query($conexion, "SELECT id_usuario FROM users WHERE email='$email_nuevo' AND id_usuario != $id_usuario LIMIT 1");
                    if ($chk && mysqli_num_rows($chk) > 0)
                        $cred_err[] = 'Ya existe un usuario con ese email.';
                }

                if ($pass_nuevo && strlen($pass_nuevo) < 6)
                    $cred_err[] = 'La contraseña debe tener al menos 6 caracteres.';

                if ($pass_nuevo && $pass_nuevo !== $pass_conf)
                    $cred_err[] = 'Las contraseñas no coinciden.';

                if ($cred_err) {
                    $err_msg = implode(' ', $cred_err);
                    $ok_msg  = '';
                } else {
                    $sets = [];
                    if ($email_nuevo) $sets[] = "email='$email_nuevo'";
                    if ($pass_nuevo)  $sets[] = "contrasena='" . mysqli_real_escape_string($conexion, password_hash($pass_nuevo, PASSWORD_DEFAULT)) . "'";
                    if ($sets) {
                        mysqli_query($conexion, "UPDATE users SET " . implode(',', $sets) . " WHERE id_usuario=$id_usuario AND tipo=2");
                        $ok_msg = '✓ Trabajador y credenciales actualizados correctamente.';
                    }
                }
            }
        }
    }

    // ── Eliminar trabajador ────────────────────────────────────────────────────
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $id         = intval($_POST['id_usuario']);
        $id_persona = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_persona FROM users WHERE id_usuario=$id LIMIT 1"))['id_persona'] ?? 0;
        if ($id_persona) mysqli_query($conexion, "DELETE FROM postulaciones WHERE id_persona=$id_persona");
        mysqli_query($conexion, "DELETE FROM notificaciones WHERE id_usuario=$id");
        mysqli_query($conexion, "DELETE FROM users WHERE id_usuario=$id AND tipo=2");
        if ($id_persona) mysqli_query($conexion, "DELETE FROM persona WHERE id_persona=$id_persona");
        $ok_msg = 'Trabajador eliminado permanentemente.';
    }
}

// ── Filtros ───────────────────────────────────────────────────────────────────
$buscar   = trim($_GET['q']              ?? '');
$estado   = $_GET['estado']              ?? 'activo';
$esp      = intval($_GET['especialidad'] ?? 0);
$con_cv   = isset($_GET['con_cv']);
$page_num = max(1, intval($_GET['pag']   ?? 1));
$per_page = 15;
$offset   = ($page_num - 1) * $per_page;

$where = ["u.tipo=2"];
if ($buscar) $where[] = "(CONCAT(p.nombre,' ',p.apellido) LIKE '%" . mysqli_real_escape_string($conexion, $buscar) . "%' OR u.email LIKE '%" . mysqli_real_escape_string($conexion, $buscar) . "%')";
if ($estado !== 'todos') $where[] = "u.estado='" . mysqli_real_escape_string($conexion, $estado) . "'";
if ($esp > 0) $where[] = "EXISTS (SELECT 1 FROM persona_especialidades pe WHERE pe.id_persona=p.id_persona AND pe.id_especialidad=$esp)";
if ($con_cv) $where[] = "(p.curriculum_pdf IS NOT NULL AND p.curriculum_pdf != '')";
$wsql = implode(' AND ', $where);

$total_res   = mysqli_fetch_assoc(mysqli_query(
    $conexion,
    "SELECT COUNT(*) c FROM users u
     LEFT JOIN persona p ON u.id_persona=p.id_persona
     WHERE $wsql"
))['c'] ?? 0;
$total_pages = ceil($total_res / $per_page);

$res = mysqli_query(
    $conexion,
    "SELECT u.id_usuario, u.id_persona, u.email, u.estado, u.fecha_creacion,
            COALESCE(p.nombre,'')         AS nombre,
            COALESCE(p.apellido,'')       AS apellido,
            COALESCE(p.nombre_titulo,'')  AS titulo_profesional,
            COALESCE(p.telefono,'')       AS telefono,
            COALESCE(p.dni,'')            AS dni,
            COALESCE(p.id_provincia_preferencia,0) AS id_provincia_preferencia,
            COALESCE(p.curriculum_pdf,'') AS cv,
            pr.nombre AS provincia_nombre,
            (SELECT esp.nombre_especialidad FROM persona_especialidades pe2
             LEFT JOIN especialidades esp ON pe2.id_especialidad=esp.id_especialidad
             WHERE pe2.id_persona=p.id_persona LIMIT 1) AS especialidad_nombre,
            (SELECT COUNT(*) FROM postulaciones po WHERE po.id_persona=p.id_persona) AS total_postulaciones
     FROM users u
     LEFT JOIN persona p ON u.id_persona=p.id_persona
     LEFT JOIN provincias pr ON p.id_provincia_preferencia=pr.id_provincia
     WHERE $wsql
     ORDER BY u.fecha_creacion DESC
     LIMIT $per_page OFFSET $offset"
);

$res_esp        = mysqli_query($conexion, "SELECT id_especialidad, nombre_especialidad AS nombre FROM especialidades ORDER BY nombre_especialidad");
$res_provincias = mysqli_query($conexion, "SELECT id_provincia, nombre FROM provincias WHERE estado=1 ORDER BY nombre");

$cnt_activos   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM users WHERE tipo=2 AND estado='activo'"))['c']   ?? 0;
$cnt_inactivos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM users WHERE tipo=2 AND estado='inactivo'"))['c'] ?? 0;

include("sidebar-admin.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Trabajadores</h1>
            <p class="text-gray-500 text-sm mt-0.5"><?= $total_res ?> trabajador(es) encontrado(s)</p>
        </div>
        <button type="button" onclick="abrirModalCrear()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo trabajador
        </button>
    </div>

    <?php if ($ok_msg): ?><div id="php-ok-msg" class="hidden"><?= htmlspecialchars($ok_msg) ?></div><?php endif; ?>
    <?php if ($err_msg): ?><div id="php-err-msg" class="hidden"><?= htmlspecialchars($err_msg) ?></div><?php endif; ?>

    <!-- Pills -->
    <div class="flex flex-wrap gap-2 mb-5">
        <?php foreach (['activo' => ['Activos', $cnt_activos], 'inactivo' => ['De baja', $cnt_inactivos], 'todos' => ['Todos', $cnt_activos + $cnt_inactivos]] as $val => [$label, $cnt]):
            $active = $estado === $val;
            $cls = $active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'; ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['estado' => $val, 'pag' => 1])) ?>"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium transition <?= $cls ?>">
                <?= $label ?>
                <span class="<?= $active ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' ?> text-xs px-1.5 py-0.5 rounded-full font-semibold"><?= $cnt ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 mb-5">
        <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar por nombre o email..."
                    class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>
            <select name="especialidad" class="px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="0">Todas las especialidades</option>
                <?php while ($e = mysqli_fetch_assoc($res_esp)): ?>
                    <option value="<?= $e['id_especialidad'] ?>" <?= $esp == $e['id_especialidad'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="flex items-center gap-4 mt-3">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="con_cv" <?= $con_cv ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600"> Solo con CV
            </label>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">Buscar</button>
            <a href="admin-trabajadores.php" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition">Limpiar</a>
        </div>
    </form>

    <!-- Tabla -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden min-h-[450px] flex flex-col">
        <div class="overflow-x-auto flex-grow">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Trabajador</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Especialidad</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Postulaciones</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">CV</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php if (!$res || mysqli_num_rows($res) === 0): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-gray-400 text-sm">No hay trabajadores<?= $estado === 'inactivo' ? ' de baja' : ($estado === 'activo' ? ' activos' : '') ?></p>
                                <?php if ($estado === 'inactivo'): ?>
                                    <a href="admin-trabajadores.php" class="text-xs text-indigo-600 hover:underline mt-1 inline-block">Ver activos</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: while ($t = mysqli_fetch_assoc($res)):
                        $nc = trim(($t['nombre'] ?? '') . ' ' . ($t['apellido'] ?? '')); ?>
                        <tr class="hover:bg-gray-50 transition <?= $t['estado'] === 'inactivo' ? 'opacity-60 bg-gray-50/50' : '' ?>">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-green-100 rounded-xl flex items-center justify-center text-green-700 text-sm font-bold flex-shrink-0 border border-green-200">
                                        <?= strtoupper(substr($nc ?: 'T', 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($nc ?: '(sin nombre)') ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($t['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell text-xs text-gray-600">
                                <?= htmlspecialchars($t['especialidad_nombre'] ?? '—') ?>
                            </td>
                            <td class="px-4 py-4 text-center hidden lg:table-cell font-semibold text-gray-700">
                                <?= $t['total_postulaciones'] ?>
                            </td>
                            <td class="px-4 py-4 text-center hidden lg:table-cell">
                                <?= $t['cv'] ? '<span class="text-[10px] bg-red-50 text-red-600 border border-red-200 px-2 py-0.5 rounded-full font-bold">PDF</span>' : '<span class="text-xs text-gray-300">—</span>' ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <?php if ($t['estado'] === 'activo'): ?>
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
                                        onclick="toggleDropdown(event, 'dropdown-<?= $t['id_usuario'] ?>')"
                                        class="flex items-center justify-center w-8 h-8 text-gray-500 rounded-full hover:bg-gray-100 focus:outline-none transition mx-auto">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>
                                    <div id="dropdown-<?= $t['id_usuario'] ?>"
                                        class="hidden absolute right-0 z-[100] mt-2 w-44 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <button type="button"
                                                onclick="abrirModalToggle(<?= $t['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($nc ?: 'este trabajador')) ?>', '<?= $t['estado'] ?>')"
                                                class="flex w-full px-4 py-2 text-sm text-left transition <?= $t['estado'] === 'activo' ? 'text-orange-700 hover:bg-orange-50' : 'text-green-700 hover:bg-green-50' ?>">
                                                <?= $t['estado'] === 'activo' ? 'Dar baja' : 'Activar' ?>
                                            </button>
                                            <button type="button"
                                                onclick="abrirModalEditar(<?= intval($t['id_persona'] ?? 0) ?>, <?= intval($t['id_usuario']) ?>, '<?= addslashes($t['nombre'] ?? '') ?>', '<?= addslashes($t['apellido'] ?? '') ?>', '<?= addslashes($t['titulo_profesional'] ?? '') ?>', '<?= addslashes($t['telefono'] ?? '') ?>', '<?= addslashes($t['dni'] ?? '') ?>', <?= intval($t['id_provincia_preferencia'] ?? 0) ?>)"
                                                class="flex w-full px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition text-left">
                                                Editar datos
                                            </button>
                                        </div>
                                        <div class="py-1">
                                            <button type="button"
                                                onclick="abrirModalEliminar(<?= $t['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($nc ?: 'este trabajador')) ?>')"
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
                <p class="text-xs text-gray-500 font-medium">
                    Mostrando página <span class="text-gray-800"><?= $page_num ?></span> de <span class="text-gray-800"><?= $total_pages ?></span>
                </p>
                <nav class="flex items-center gap-1">
                    <?php if ($page_num > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pag' => $page_num - 1])) ?>"
                           class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-white border border-transparent hover:border-gray-200 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php
                    $range = 2;
                    $start = max(1, $page_num - $range);
                    $end   = min($total_pages, $page_num + $range);
                    for ($i = $start; $i <= $end; $i++):
                        $active = ($i === $page_num); ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pag' => $i])) ?>"
                           class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-semibold transition
                           <?= $active ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:bg-white border border-transparent hover:border-gray-200' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page_num < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pag' => $page_num + 1])) ?>"
                           class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-white border border-transparent hover:border-gray-200 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL CREAR -->
<div id="modalCrear" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h3 class="font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Nuevo trabajador
            </h3>
            <button onclick="cerrarModalCrear()" class="text-white hover:text-indigo-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" class="overflow-y-auto flex-1">
            <input type="hidden" name="accion" value="crear_trabajador">
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">Cuenta de acceso</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email_usuario" id="crear-email" required placeholder="trabajador@email.com"
                                value="<?= ($_POST['accion'] ?? '') === 'crear_trabajador' ? htmlspecialchars($_POST['email_usuario'] ?? '') : '' ?>"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div class="relative">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
                            <input type="password" name="password_usuario" id="crear-pass" required placeholder="Mínimo 6 caracteres"
                                class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <button type="button" onclick="togglePassCrear()" class="absolute right-3 top-[34px] text-gray-400 hover:text-gray-600">
                                <svg id="eye-crear-show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="eye-crear-hide" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <hr class="border-gray-200">
                <div>
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">Datos personales</p>
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                                <input type="text" name="nombre" id="crear-nombre" required
                                    value="<?= ($_POST['accion'] ?? '') === 'crear_trabajador' ? htmlspecialchars($_POST['nombre'] ?? '') : '' ?>"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Apellido <span class="text-red-500">*</span></label>
                                <input type="text" name="apellido" id="crear-apellido" required
                                    value="<?= ($_POST['accion'] ?? '') === 'crear_trabajador' ? htmlspecialchars($_POST['apellido'] ?? '') : '' ?>"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Título / Profesión</label>
                            <input type="text" name="nombre_titulo" id="crear-titulo" placeholder="Ej: Albañil, Electricista"
                                value="<?= ($_POST['accion'] ?? '') === 'crear_trabajador' ? htmlspecialchars($_POST['nombre_titulo'] ?? '') : '' ?>"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">DNI</label>
                                <input type="text" name="dni" id="crear-dni" placeholder="12345678"
                                    value="<?= ($_POST['accion'] ?? '') === 'crear_trabajador' ? htmlspecialchars($_POST['dni'] ?? '') : '' ?>"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Teléfono</label>
                                <input type="text" name="telefono" id="crear-telefono" placeholder="3834000000"
                                    value="<?= ($_POST['accion'] ?? '') === 'crear_trabajador' ? htmlspecialchars($_POST['telefono'] ?? '') : '' ?>"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Provincia de preferencia</label>
                            <select name="id_provincia_preferencia" id="crear-provincia"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Sin especificar</option>
                                <?php
                                mysqli_data_seek($res_provincias, 0);
                                $prov_sel = ($_POST['accion'] ?? '') === 'crear_trabajador' ? intval($_POST['id_provincia_preferencia'] ?? 0) : 0;
                                while ($pv = mysqli_fetch_assoc($res_provincias)): ?>
                                    <option value="<?= $pv['id_provincia'] ?>" <?= $prov_sel == $pv['id_provincia'] ? 'selected' : '' ?>><?= htmlspecialchars($pv['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 flex-shrink-0">
                <button type="button" onclick="cerrarModalCrear()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Crear trabajador
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h3 class="font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar trabajador
            </h3>
            <button onclick="cerrarModalEditar()" class="text-white hover:text-indigo-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" class="overflow-y-auto flex-1">
            <input type="hidden" name="accion" value="editar_trabajador">
            <input type="hidden" name="id_persona" id="editar-id">
            <input type="hidden" name="id_usuario_edit" id="editar-id-usuario">
            <div class="p-6 space-y-5">

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
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Título / Profesión</label>
                            <input type="text" name="nombre_titulo" id="editar-titulo" placeholder="Ej: Albañil, Electricista"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">DNI</label>
                                <input type="text" name="dni" id="editar-dni" placeholder="12345678"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Teléfono</label>
                                <input type="text" name="telefono" id="editar-telefono" placeholder="3834000000"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Provincia de preferencia</label>
                            <select name="id_provincia_preferencia" id="editar-provincia"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Sin especificar</option>
                                <?php
                                mysqli_data_seek($res_provincias, 0);
                                while ($pv = mysqli_fetch_assoc($res_provincias)): ?>
                                    <option value="<?= $pv['id_provincia'] ?>"><?= htmlspecialchars($pv['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Credenciales de acceso -->
                <div class="border-t border-gray-100 pt-5">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Credenciales de acceso</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email de acceso</label>
                            <input type="email" name="email_usuario" id="editar-email-usuario"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                placeholder="Dejar vacío para no modificar">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nueva contraseña</label>
                                <div class="relative">
                                    <input type="password" name="password_nuevo" id="editar-pass"
                                        class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                        placeholder="Dejar vacío para no modificar">
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
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 flex-shrink-0">
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

<!-- MODAL TOGGLE -->
<div id="modalToggle" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div id="toggle-icon" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"></div>
            <div>
                <h3 id="toggle-titulo" class="font-bold text-gray-900"></h3>
                <p id="toggle-nombre" class="text-xs text-gray-400 mt-0.5"></p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p id="toggle-mensaje" class="text-sm text-gray-600"></p>
        </div>
        <form method="POST">
            <input type="hidden" name="toggle_estado" value="1">
            <input type="hidden" name="id_usuario" id="toggle-id">
            <input type="hidden" name="nuevo_estado" id="toggle-nuevo-estado">
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalToggle()" class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" id="toggle-btn" class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition"></button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ELIMINAR -->
<div id="modalEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Eliminar trabajador</h3>
                <p class="text-xs text-gray-400" id="modal-nombre"></p>
            </div>
        </div>
        <form method="POST" id="form-eliminar">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_usuario" id="hidden-id">
            <div class="px-6 py-5">
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-xs text-red-700 font-semibold mb-1">Esta acción es permanente:</p>
                    <ul class="text-xs text-red-600 space-y-0.5 ml-3 list-disc">
                        <li>Se eliminarán todas sus postulaciones</li>
                        <li>Se eliminarán todas sus notificaciones</li>
                        <li>Se eliminará su perfil y datos personales</li>
                        <li>Se eliminará su cuenta de usuario</li>
                    </ul>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEliminar()" class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eliminar permanentemente
                </button>
            </div>
        </form>
    </div>
</div>

</main>
</div>

<div id="toast-container" class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 items-end pointer-events-none [&>*]:pointer-events-auto"></div>

<!-- MODAL CERRAR SESIÓN -->
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

<script>
    function toggleDropdown(event, id) {
        event.stopPropagation();
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
            if (el.id !== id) el.classList.add('hidden');
        });
        document.getElementById(id).classList.toggle('hidden');
    }
    window.onclick = function(event) {
        if (!event.target.closest('button')) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
        }
    }

    function abrirModalSesion()  { document.getElementById('modalCerrarSesion').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function cerrarModalSesion() { document.getElementById('modalCerrarSesion').classList.add('hidden');    document.body.style.overflow = 'auto';   }
    document.getElementById('modalCerrarSesion').addEventListener('click', function(e) { if (e.target === this) cerrarModalSesion(); });

    function abrirModalCrear()  { document.getElementById('modalCrear').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function cerrarModalCrear() { document.getElementById('modalCrear').classList.add('hidden');    document.body.style.overflow = 'auto';   }
    document.getElementById('modalCrear').addEventListener('click', function(e) { if (e.target === this) cerrarModalCrear(); });

    function togglePassCrear() {
        const inp  = document.getElementById('crear-pass');
        const show = document.getElementById('eye-crear-show');
        const hide = document.getElementById('eye-crear-hide');
        if (inp.type === 'password') { inp.type = 'text';     show.classList.add('hidden');    hide.classList.remove('hidden'); }
        else                         { inp.type = 'password'; show.classList.remove('hidden'); hide.classList.add('hidden');    }
    }

    function togglePass(id) {
        const i = document.getElementById(id);
        i.type = i.type === 'password' ? 'text' : 'password';
    }

    // Ahora recibe idUsuario como segundo parámetro
    function abrirModalEditar(idPersona, idUsuario, nombre, apellido, titulo, telefono, dni, idProv) {
        document.getElementById('editar-id').value          = idPersona;
        document.getElementById('editar-id-usuario').value  = idUsuario;
        document.getElementById('editar-nombre').value      = nombre;
        document.getElementById('editar-apellido').value    = apellido;
        document.getElementById('editar-titulo').value      = titulo;
        document.getElementById('editar-telefono').value    = telefono;
        document.getElementById('editar-dni').value         = dni;
        const sel = document.getElementById('editar-provincia');
        if (sel) sel.value = idProv || '';

        // Limpiar credenciales siempre al abrir
        document.getElementById('editar-email-usuario').value = '';
        document.getElementById('editar-pass').value          = '';
        document.getElementById('editar-pass-confirm').value  = '';

        document.getElementById('modalEditar').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function cerrarModalEditar() { document.getElementById('modalEditar').classList.add('hidden'); document.body.style.overflow = 'auto'; }
    document.getElementById('modalEditar').addEventListener('click', function(e) { if (e.target === this) cerrarModalEditar(); });

    function abrirModalToggle(id, nombre, estado) {
        const esActivo = estado === 'activo';
        const icon = document.getElementById('toggle-icon');
        icon.className = 'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ' + (esActivo ? 'bg-orange-100' : 'bg-green-100');
        icon.innerHTML = esActivo ?
            `<svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>` :
            `<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
        document.getElementById('toggle-titulo').textContent  = esActivo ? 'Dar baja trabajador' : 'Activar trabajador';
        document.getElementById('toggle-nombre').textContent  = nombre;
        document.getElementById('toggle-mensaje').innerHTML   = esActivo ? `¿Estás seguro de que querés <strong>dar baja</strong> a este trabajador?` : `¿Estás seguro de que querés <strong>activar</strong> a este trabajador?`;
        const btn = document.getElementById('toggle-btn');
        btn.textContent = esActivo ? 'Sí, dar baja' : 'Sí, activar';
        btn.className   = 'px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition ' + (esActivo ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700');
        document.getElementById('toggle-id').value           = id;
        document.getElementById('toggle-nuevo-estado').value = esActivo ? 'inactivo' : 'activo';
        document.getElementById('modalToggle').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function cerrarModalToggle() { document.getElementById('modalToggle').classList.add('hidden'); document.body.style.overflow = 'auto'; }

    function abrirModalEliminar(id, nombre) {
        document.getElementById('modal-nombre').textContent = nombre;
        document.getElementById('hidden-id').value          = id;
        document.getElementById('modalEliminar').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function cerrarModalEliminar() { document.getElementById('modalEliminar').classList.add('hidden'); document.body.style.overflow = 'auto'; }

    document.getElementById('modalToggle').addEventListener('click',   e => { if (e.target === document.getElementById('modalToggle'))   cerrarModalToggle();   });
    document.getElementById('modalEliminar').addEventListener('click', e => { if (e.target === document.getElementById('modalEliminar')) cerrarModalEliminar(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { cerrarModalCrear(); cerrarModalEditar(); cerrarModalToggle(); cerrarModalEliminar(); cerrarModalSesion(); }
    });

    function showToast(msg, type = 'success') {
        const id  = 'toast-' + Date.now();
        const cfg = {
            success: { border: 'border-green-200', bar: 'bg-green-500', icon: `<svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` },
            error:   { border: 'border-red-200',   bar: 'bg-red-400',   icon: `<svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` }
        };
        const c = cfg[type];
        const t = document.createElement('div');
        t.id        = id;
        t.className = `flex items-center gap-3 bg-white border ${c.border} rounded-2xl shadow-lg px-4 py-3.5 min-w-[280px] max-w-sm translate-x-full opacity-0 transition-all duration-300 ease-out relative overflow-hidden`;
        t.innerHTML = `${c.icon}<p class="text-sm font-medium text-gray-800 flex-1">${msg}</p>
        <button onclick="removeToast('${id}')" class="text-gray-400 hover:text-gray-600 ml-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
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

    window.addEventListener('DOMContentLoaded', () => {
        const ok  = document.getElementById('php-ok-msg');
        const err = document.getElementById('php-err-msg');
        if (ok  && ok.textContent.trim())  showToast(ok.textContent.trim(),  'success');
        if (err && err.textContent.trim()) showToast(err.textContent.trim(), 'error');

        <?php if ($err_msg && ($_POST['accion'] ?? '') === 'crear_trabajador'): ?>
            abrirModalCrear();
        <?php endif; ?>
        <?php if ($err_msg && ($_POST['accion'] ?? '') === 'editar_trabajador'): ?>
            abrirModalEditar(
                <?= intval($_POST['id_persona'] ?? 0) ?>,
                <?= intval($_POST['id_usuario_edit'] ?? 0) ?>,
                '<?= addslashes($_POST['nombre']        ?? '') ?>',
                '<?= addslashes($_POST['apellido']      ?? '') ?>',
                '<?= addslashes($_POST['nombre_titulo'] ?? '') ?>',
                '<?= addslashes($_POST['telefono']      ?? '') ?>',
                '<?= addslashes($_POST['dni']           ?? '') ?>',
                <?= intval($_POST['id_provincia_preferencia'] ?? 0) ?>
            );
        <?php endif; ?>
    });
</script>