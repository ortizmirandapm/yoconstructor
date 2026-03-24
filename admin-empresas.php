<?php
$page      = 'admin-empresas';
$pageTitle = 'Empresas';
include("conexion.php");
include("sidebar-admin.php");

$ok_msg = '';
$err_msg = '';

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc($c, $v)
{
    return mysqli_real_escape_string($c, trim($v ?? ''));
}

// ── ACCIONES POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Crear empresa ──────────────────────────────────────────────────────────
    if (($_POST['accion'] ?? '') === 'crear_empresa') {
        $nombre      = esc($conexion, $_POST['nombre_empresa']  ?? '');
        $razon       = esc($conexion, $_POST['razon_social']    ?? '');
        $cuit        = esc($conexion, $_POST['cuit']            ?? '');
        $id_rubro    = intval($_POST['id_rubro']    ?? 0);
        $id_prov     = intval($_POST['id_provincia'] ?? 0);
        $telefono    = esc($conexion, $_POST['telefono']        ?? '');
        $email_cont  = esc($conexion, $_POST['email_contacto']  ?? '');
        $domicilio   = esc($conexion, $_POST['domicilio']       ?? '');
        $descripcion = esc($conexion, $_POST['descripcion_empresa'] ?? '');
        $email_user  = esc($conexion, $_POST['email_usuario']   ?? '');
        $password_p  = trim($_POST['password_usuario'] ?? '');

        $errores = [];
        if (!$nombre)     $errores[] = 'El nombre de empresa es obligatorio.';
        if (!$cuit)       $errores[] = 'El CUIT es obligatorio.';
        if (!$email_user) $errores[] = 'El email de acceso es obligatorio.';
        if (!filter_var($email_user, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email de acceso no válido.';
        if (!$password_p || strlen($password_p) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';

        if (!$errores) {
            $chk = mysqli_query($conexion, "SELECT id_usuario FROM users WHERE email='$email_user' LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) $errores[] = 'Ya existe un usuario con ese email.';
        }

        if ($errores) {
            $err_msg = implode(' ', $errores);
        } else {
            $sql_emp = "INSERT INTO empresa
                (nombre_empresa, razon_social, cuit, id_rubro, id_provincia,
                 telefono, email_contacto, domicilio, descripcion_empresa, estado, fecha_ingreso)
                VALUES ('$nombre','$razon','$cuit'," . ($id_rubro ?: 'NULL') . "," . ($id_prov ?: 'NULL') . ",
                        '$telefono','$email_cont','$domicilio','$descripcion','activo',CURDATE())";

            if (mysqli_query($conexion, $sql_emp)) {
                $id_empresa_new = mysqli_insert_id($conexion);
                $hash = password_hash($password_p, PASSWORD_DEFAULT);
                $sql_usr = "INSERT INTO users (email, contrasena, tipo, id_empresa, estado)
                            VALUES ('$email_user','$hash',3,$id_empresa_new,'activo')";
                if (mysqli_query($conexion, $sql_usr)) {
                    $ok_msg = '✓ Empresa creada correctamente.';
                } else {
                    mysqli_query($conexion, "DELETE FROM empresa WHERE id_empresa=$id_empresa_new");
                    $err_msg = 'Error al crear usuario: ' . mysqli_error($conexion);
                }
            } else {
                $err_msg = 'Error al crear empresa: ' . mysqli_error($conexion);
            }
        }
    }

    // ── Editar empresa ─────────────────────────────────────────────────────────
    if (($_POST['accion'] ?? '') === 'editar_empresa') {
        $id_emp      = intval($_POST['id_empresa'] ?? 0);
        $nombre      = esc($conexion, $_POST['nombre_empresa']  ?? '');
        $razon       = esc($conexion, $_POST['razon_social']    ?? '');
        $cuit        = esc($conexion, $_POST['cuit']            ?? '');
        $id_rubro    = intval($_POST['id_rubro']    ?? 0);
        $id_prov     = intval($_POST['id_provincia'] ?? 0);
        $telefono    = esc($conexion, $_POST['telefono']        ?? '');
        $email_cont  = esc($conexion, $_POST['email_contacto']  ?? '');
        $domicilio   = esc($conexion, $_POST['domicilio']       ?? '');
        $descripcion = esc($conexion, $_POST['descripcion_empresa'] ?? '');

        if (!$nombre || !$cuit) {
            $err_msg = 'Nombre y CUIT son obligatorios.';
        } else {
            $sql_upd = "UPDATE empresa SET
                nombre_empresa='$nombre', razon_social='$razon', cuit='$cuit',
                id_rubro=" . ($id_rubro ?: 'NULL') . ", id_provincia=" . ($id_prov ?: 'NULL') . ",
                telefono='$telefono', email_contacto='$email_cont',
                domicilio='$domicilio', descripcion_empresa='$descripcion'
                WHERE id_empresa=$id_emp";

            if (mysqli_query($conexion, $sql_upd)) {
                $ok_msg = '✓ Empresa actualizada correctamente.';
            } else {
                $err_msg = 'Error al actualizar: ' . mysqli_error($conexion);
            }
        }

        // ── Actualizar credenciales si se enviaron ─────────────────────────────
        if (!$err_msg) {
            $email_nuevo = esc($conexion, $_POST['email_usuario']    ?? '');
            $pass_nuevo  = trim($_POST['password_nuevo']             ?? '');
            $pass_conf   = trim($_POST['password_confirmar']         ?? '');

            if ($email_nuevo || $pass_nuevo) {
                $credenciales_err = [];

                if ($email_nuevo && !filter_var($email_nuevo, FILTER_VALIDATE_EMAIL))
                    $credenciales_err[] = 'Email de acceso no válido.';

                if ($email_nuevo) {
                    $chk = mysqli_query($conexion, "SELECT id_usuario FROM users WHERE email='$email_nuevo' AND id_empresa != $id_emp LIMIT 1");
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
                        mysqli_query($conexion, "UPDATE users SET " . implode(',', $sets) . " WHERE id_empresa=$id_emp AND tipo=3");
                        $ok_msg = '✓ Empresa y credenciales actualizadas correctamente.';
                    }
                }
            }
        }
    }

    // ── Toggle estado ──────────────────────────────────────────────────────────
    if (isset($_POST['toggle_estado'])) {
        $id    = intval($_POST['id_empresa']);
        $nuevo = in_array($_POST['nuevo_estado'], ['activo', 'inactivo']) ? $_POST['nuevo_estado'] : 'inactivo';
        mysqli_query($conexion, "UPDATE empresa SET estado='$nuevo' WHERE id_empresa=$id");
        mysqli_query($conexion, "UPDATE users SET estado='$nuevo' WHERE id_empresa=$id AND tipo=3");
        if ($nuevo === 'inactivo')
            mysqli_query($conexion, "UPDATE ofertas_laborales SET estado='Inactiva' WHERE id_empresa=$id");
        $ok_msg = $nuevo === 'activo' ? '✓ Empresa activada.' : '✓ Empresa desactivada y sus ofertas pasadas a inactivo.';
    }

    // ── Eliminar empresa ───────────────────────────────────────────────────────
    if (($_POST['accion'] ?? '') === 'eliminar') {
        $id = intval($_POST['id_empresa']);
        if (trim($_POST['confirmar_eliminar'] ?? '') !== 'ELIMINAR') {
            $err_msg = 'Texto de confirmación incorrecto. Escribí exactamente ELIMINAR.';
        } else {
            $row_usr = mysqli_fetch_assoc(mysqli_query(
                $conexion,
                "SELECT id_usuario FROM users WHERE id_empresa=$id AND tipo=3 LIMIT 1"
            ));
            $id_usr = $row_usr['id_usuario'] ?? 0;
            mysqli_query($conexion, "DELETE FROM postulaciones WHERE id_oferta IN (SELECT id_oferta FROM ofertas_laborales WHERE id_empresa=$id)");
            mysqli_query($conexion, "DELETE FROM ofertas_laborales WHERE id_empresa=$id");
            mysqli_query($conexion, "DELETE FROM reclutadores WHERE id_empresa=$id");
            if ($id_usr) mysqli_query($conexion, "DELETE FROM users WHERE id_usuario=$id_usr");
            mysqli_query($conexion, "DELETE FROM empresa WHERE id_empresa=$id");
            $ok_msg = '✓ Empresa eliminada permanentemente.';
        }
    }
}

// ── Filtros y paginación ───────────────────────────────────────────────────────
$buscar   = trim($_GET['q']       ?? '');
$estado   = $_GET['estado']       ?? 'activo';
$rubro    = intval($_GET['rubro'] ?? 0);
$page_num = max(1, intval($_GET['pag'] ?? 1));
$per_page = 15;
$offset   = ($page_num - 1) * $per_page;

$where = ["1=1"];
if ($buscar)             $where[] = "(e.nombre_empresa LIKE '%" . esc($conexion, $buscar) . "%' OR u.email LIKE '%" . esc($conexion, $buscar) . "%' OR e.cuit LIKE '%" . esc($conexion, $buscar) . "%')";
if ($estado !== 'todos') $where[] = "e.estado='" . esc($conexion, $estado) . "'";
if ($rubro > 0)          $where[] = "e.id_rubro=$rubro";
$wsql = implode(' AND ', $where);

$total_res   = mysqli_fetch_assoc(mysqli_query(
    $conexion,
    "SELECT COUNT(*) c FROM empresa e
     INNER JOIN users u ON u.id_empresa=e.id_empresa AND u.tipo=3
     WHERE $wsql"
))['c'] ?? 0;
$total_pages = ceil($total_res / $per_page);

$res = mysqli_query(
    $conexion,
    "SELECT e.*, u.email, u.estado AS user_estado, u.fecha_creacion,
            r.nombre AS rubro_nombre, p.nombre AS provincia_nombre,
            (SELECT COUNT(*) FROM ofertas_laborales ol WHERE ol.id_empresa=e.id_empresa AND ol.estado='Activa') AS ofertas_activas,
            (SELECT COUNT(*) FROM postulaciones po INNER JOIN ofertas_laborales ol2 ON po.id_oferta=ol2.id_oferta WHERE ol2.id_empresa=e.id_empresa) AS total_postulaciones,
            (SELECT COUNT(*) FROM reclutadores rc WHERE rc.id_empresa=e.id_empresa) AS total_reclutadores
     FROM empresa e
     INNER JOIN users u ON u.id_empresa=e.id_empresa AND u.tipo=3
     LEFT JOIN rubros r ON e.id_rubro=r.id_rubro
     LEFT JOIN provincias p ON e.id_provincia=p.id_provincia
     WHERE $wsql ORDER BY e.fecha_ingreso DESC LIMIT $per_page OFFSET $offset"
);

$res_rubros       = mysqli_query($conexion, "SELECT id_rubro, nombre FROM rubros ORDER BY nombre");
$res_rubros_modal = mysqli_query($conexion, "SELECT id_rubro, nombre FROM rubros WHERE estado=1 ORDER BY orden, nombre");
$res_provincias   = mysqli_query($conexion, "SELECT id_provincia, nombre FROM provincias WHERE estado=1 ORDER BY nombre");

$cnt_activas   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM empresa WHERE estado='activo'"))['c']   ?? 0;
$cnt_inactivas = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM empresa WHERE estado='inactivo'"))['c'] ?? 0;
$cnt_todas     = $cnt_activas + $cnt_inactivas;
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Empresas</h1>
            <p class="text-gray-500 text-sm mt-0.5"><?= $total_res ?> empresa(s) encontrada(s)</p>
        </div>
        <button onclick="abrirModalCrear()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva empresa
        </button>
    </div>

    <!-- Toasts PHP -->
    <?php if ($ok_msg): ?><div id="php-ok-msg" class="hidden"><?= htmlspecialchars($ok_msg)  ?></div><?php endif; ?>
    <?php if ($err_msg): ?><div id="php-err-msg" class="hidden"><?= htmlspecialchars($err_msg) ?></div><?php endif; ?>

    <!-- Pills estado -->
    <div class="flex flex-wrap gap-2 mb-5">
        <?php
        $pills = [
            'activo'   => ['label' => 'Activas',  'count' => $cnt_activas,   'color' => 'bg-green-100 text-green-700 border-green-300'],
            'inactivo' => ['label' => 'De baja',  'count' => $cnt_inactivas, 'color' => 'bg-red-100 text-red-700 border-red-300'],
            'todos'    => ['label' => 'Todas',     'count' => $cnt_todas,     'color' => 'bg-gray-100 text-gray-600 border-gray-300'],
        ];
        foreach ($pills as $val => $p):
            $active = ($estado === $val);
            $cls = $active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50';
        ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['estado' => $val, 'pag' => 1])) ?>"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium transition <?= $cls ?>">
                <?= $p['label'] ?>
                <span class="<?= $active ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' ?> text-xs px-1.5 py-0.5 rounded-full font-semibold">
                    <?= $p['count'] ?>
                </span>
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
                <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>"
                    placeholder="Buscar por nombre, email o CUIT..."
                    class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>
            <select name="rubro" class="px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="0">Todos los rubros</option>
                <?php
                mysqli_data_seek($res_rubros, 0);
                while ($rb = mysqli_fetch_assoc($res_rubros)): ?>
                    <option value="<?= $rb['id_rubro'] ?>" <?= $rubro == $rb['id_rubro'] ? 'selected' : '' ?>><?= htmlspecialchars($rb['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="flex gap-2 mt-3">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">Buscar</button>
            <a href="admin-empresas.php" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition">Limpiar</a>
        </div>
    </form>

    <!-- Tabla -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden min-h-[450px] flex flex-col">
        <div class="overflow-x-auto flex-grow">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Empresa</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Rubro</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Ofertas</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Postulaciones</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Reclutadores</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php if (!$res || mysqli_num_rows($res) === 0): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <p class="text-gray-400 text-sm">No hay empresas<?= $estado === 'inactivo' ? ' desactivadas' : ($estado === 'activo' ? ' activas' : '') ?></p>
                            </td>
                        </tr>
                    <?php else: while ($emp = mysqli_fetch_assoc($res)): ?>
                        <tr class="hover:bg-gray-50 transition <?= $emp['estado'] === 'inactivo' ? 'opacity-60 bg-gray-50/50' : '' ?>">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if ($emp['logo']): ?>
                                        <img src="<?= htmlspecialchars($emp['logo']) ?>" class="w-9 h-9 rounded-xl object-contain border border-gray-200 flex-shrink-0" alt="">
                                    <?php else: ?>
                                        <div class="w-9 h-9 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 text-sm font-bold flex-shrink-0">
                                            <?= strtoupper(substr($emp['nombre_empresa'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($emp['nombre_empresa']) ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($emp['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell text-xs text-gray-600"><?= htmlspecialchars($emp['rubro_nombre'] ?? '—') ?></td>
                            <td class="px-4 py-4 text-center hidden lg:table-cell font-semibold text-gray-700"><?= $emp['ofertas_activas'] ?></td>
                            <td class="px-4 py-4 text-center hidden lg:table-cell font-semibold text-gray-700"><?= $emp['total_postulaciones'] ?></td>
                            <td class="px-4 py-4 text-center hidden lg:table-cell font-semibold text-gray-700"><?= $emp['total_reclutadores'] ?></td>
                            <td class="px-4 py-4 text-center">
                                <?php if ($emp['estado'] === 'activo'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Activa
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
                                        onclick="toggleDropdown(event, 'drop-emp-<?= $emp['id_empresa'] ?>')"
                                        class="flex items-center justify-center w-8 h-8 text-gray-500 rounded-full hover:bg-gray-100 focus:outline-none transition mx-auto">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>
                                    <div id="drop-emp-<?= $emp['id_empresa'] ?>"
                                        class="hidden absolute right-0 z-[100] mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <button type="button"
                                                onclick="abrirModalToggle(<?= $emp['id_empresa'] ?>, '<?= htmlspecialchars(addslashes($emp['nombre_empresa'])) ?>', '<?= $emp['estado'] ?>')"
                                                class="flex w-full px-4 py-2 text-sm text-left transition <?= $emp['estado'] === 'activo' ? 'text-orange-700 hover:bg-orange-50' : 'text-green-700 hover:bg-green-50' ?>">
                                                <?= $emp['estado'] === 'activo' ? 'Dar de baja' : 'Activar empresa' ?>
                                            </button>
                                            <button type="button"
                                                onclick="abrirModalEditar(
                                                    <?= $emp['id_empresa'] ?>,
                                                    '<?= addslashes(htmlspecialchars($emp['nombre_empresa'])) ?>',
                                                    '<?= addslashes($emp['razon_social'] ?? '') ?>',
                                                    '<?= addslashes($emp['cuit'] ?? '') ?>',
                                                    <?= intval($emp['id_rubro'] ?? 0) ?>,
                                                    <?= intval($emp['id_provincia'] ?? 0) ?>,
                                                    '<?= addslashes($emp['telefono'] ?? '') ?>',
                                                    '<?= addslashes($emp['email_contacto'] ?? '') ?>',
                                                    '<?= addslashes($emp['domicilio'] ?? '') ?>',
                                                    '<?= addslashes($emp['descripcion_empresa'] ?? '') ?>'
                                                )"
                                                class="flex w-full px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition text-left">
                                                Editar detalles
                                            </button>
                                        </div>
                                        <div class="py-1">
                                            <button type="button"
                                                onclick="abrirModalEliminar(<?= $emp['id_empresa'] ?>, '<?= htmlspecialchars(addslashes($emp['nombre_empresa'])) ?>')"
                                                class="flex w-full px-4 py-2 text-sm text-red-700 hover:bg-red-50 transition font-medium text-left">
                                                Eliminar empresa
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
                    <?php if ($page_num > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pag' => $page_num - 1])) ?>"
                           class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php
                    $start = max(1, $page_num - 2);
                    $end   = min($total_pages, $page_num + 2);
                    for ($i = $start; $i <= $end; $i++):
                        $active = ($i === $page_num);
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pag' => $i])) ?>"
                           class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium transition
                           <?= $active ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100 border border-transparent' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page_num < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pag' => $page_num + 1])) ?>"
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
     MODAL CREAR EMPRESA
═══════════════════════════════════════════════════════════════════════════ -->
<div id="modalCrear" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nueva empresa
            </h3>
            <button onclick="cerrarModalCrear()" class="text-white hover:text-indigo-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" class="overflow-y-auto flex-1">
            <input type="hidden" name="accion" value="crear_empresa">
            <div class="p-6 space-y-5">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Cuenta de acceso</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email de acceso <span class="text-red-500">*</span></label>
                            <input type="email" name="email_usuario" required placeholder="empresa@ejemplo.com"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="password_usuario" id="crear-pass" required placeholder="Mínimo 6 caracteres"
                                    class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                <button type="button" onclick="togglePass('crear-pass')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100"></div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Datos de la empresa</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre de empresa <span class="text-red-500">*</span></label>
                            <input type="text" name="nombre_empresa" required placeholder="Constructora XYZ"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Razón social</label>
                            <input type="text" name="razon_social" placeholder="XYZ S.A."
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">CUIT <span class="text-red-500">*</span></label>
                            <input type="text" name="cuit" required placeholder="30-12345678-9" maxlength="13"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cuit-mask">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Rubro</label>
                            <select name="id_rubro" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Seleccioná un rubro</option>
                                <?php mysqli_data_seek($res_rubros_modal, 0);
                                while ($rb = mysqli_fetch_assoc($res_rubros_modal)): ?>
                                    <option value="<?= $rb['id_rubro'] ?>"><?= htmlspecialchars($rb['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Provincia</label>
                            <select name="id_provincia" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Seleccioná una provincia</option>
                                <?php mysqli_data_seek($res_provincias, 0);
                                while ($pv = mysqli_fetch_assoc($res_provincias)): ?>
                                    <option value="<?= $pv['id_provincia'] ?>"><?= htmlspecialchars($pv['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Teléfono</label>
                            <input type="text" name="telefono" placeholder="011-4567-8901"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email de contacto</label>
                            <input type="email" name="email_contacto" placeholder="contacto@empresa.com"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Domicilio</label>
                            <input type="text" name="domicilio" placeholder="Av. Siempre Viva 742"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Descripción</label>
                            <textarea name="descripcion_empresa" rows="3" placeholder="Breve descripción de la empresa..."
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none"></textarea>
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
                    Crear empresa
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL EDITAR EMPRESA
═══════════════════════════════════════════════════════════════════════════ -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar empresa
            </h3>
            <button onclick="cerrarModalEditar()" class="text-white hover:text-indigo-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" class="overflow-y-auto flex-1">
            <input type="hidden" name="accion" value="editar_empresa">
            <input type="hidden" name="id_empresa" id="editar-id">
            <div class="p-6 space-y-5">

                <!-- Datos de la empresa -->
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Datos de la empresa</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre de empresa <span class="text-red-500">*</span></label>
                            <input type="text" name="nombre_empresa" id="editar-nombre" required
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Razón social</label>
                            <input type="text" name="razon_social" id="editar-razon"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">CUIT <span class="text-red-500">*</span></label>
                            <input type="text" name="cuit" id="editar-cuit" required maxlength="13"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cuit-mask">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Rubro</label>
                            <select name="id_rubro" id="editar-rubro" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Seleccioná un rubro</option>
                                <?php mysqli_data_seek($res_rubros_modal, 0);
                                while ($rb = mysqli_fetch_assoc($res_rubros_modal)): ?>
                                    <option value="<?= $rb['id_rubro'] ?>"><?= htmlspecialchars($rb['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Provincia</label>
                            <select name="id_provincia" id="editar-provincia" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Seleccioná una provincia</option>
                                <?php mysqli_data_seek($res_provincias, 0);
                                while ($pv = mysqli_fetch_assoc($res_provincias)): ?>
                                    <option value="<?= $pv['id_provincia'] ?>"><?= htmlspecialchars($pv['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Teléfono</label>
                            <input type="text" name="telefono" id="editar-telefono"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email de contacto</label>
                            <input type="email" name="email_contacto" id="editar-email-contacto"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Domicilio</label>
                            <input type="text" name="domicilio" id="editar-domicilio"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Descripción</label>
                            <textarea name="descripcion_empresa" id="editar-descripcion" rows="3"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Credenciales de acceso -->
                <div class="border-t border-gray-100 pt-5">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Credenciales de acceso</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email de acceso</label>
                            <input type="email" name="email_usuario" id="editar-email-usuario"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                placeholder="Dejá vacío para no modificar">
                        </div>
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
                                    placeholder="Repetí la nueva contraseña">
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
                    <p class="text-xs text-gray-400 mt-2">Dejá los campos vacíos si no querés modificar las credenciales.</p>
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

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL TOGGLE ACTIVAR / DESACTIVAR
═══════════════════════════════════════════════════════════════════════════ -->
<div id="modalToggle" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div id="modal-toggle-icon" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"></div>
            <div>
                <h3 id="modal-toggle-titulo" class="font-bold text-gray-900"></h3>
                <p id="modal-toggle-empresa" class="text-xs text-gray-400 mt-0.5"></p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p id="modal-toggle-mensaje" class="text-sm text-gray-600"></p>
        </div>
        <form method="POST" id="form-toggle">
            <input type="hidden" name="toggle_estado" value="1">
            <input type="hidden" name="id_empresa" id="toggle-id-empresa">
            <input type="hidden" name="nuevo_estado" id="toggle-nuevo-estado">
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalToggle()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" id="modal-toggle-btn"
                    class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition"></button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL ELIMINAR
═══════════════════════════════════════════════════════════════════════════ -->
<div id="modalEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Eliminar empresa</h3>
                <p id="modal-empresa-nombre" class="text-xs text-gray-400"></p>
            </div>
        </div>
        <form method="POST" id="form-eliminar">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_empresa" id="hidden-id-empresa">
            <input type="hidden" name="confirmar_eliminar" id="hidden-confirmar">
            <div class="px-6 py-5">
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl mb-4">
                    <p class="text-xs text-red-700 font-semibold mb-1">Esta acción es permanente e irreversible:</p>
                    <ul class="text-xs text-red-600 space-y-0.5 ml-3 list-disc">
                        <li>Se eliminarán todas las ofertas laborales</li>
                        <li>Se eliminarán todas las postulaciones asociadas</li>
                        <li>Se eliminarán todos los reclutadores</li>
                        <li>Se eliminará el usuario y la cuenta de empresa</li>
                    </ul>
                </div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    Escribí <span class="font-mono bg-red-100 text-red-700 px-1 rounded">ELIMINAR</span> para confirmar
                </label>
                <input type="text" id="confirm-eliminar-input" autocomplete="off"
                    class="w-full px-3 py-2.5 border border-red-300 rounded-lg text-sm font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-red-400 transition"
                    placeholder="ELIMINAR">
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModal()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="button" onclick="ejecutarEliminar()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
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
        document.querySelectorAll('[id^="drop-emp-"]').forEach(el => {
            if (el.id !== id) el.classList.add('hidden');
        });
        const dropdown = document.getElementById(id);
        dropdown.classList.toggle('hidden');
    }

    window.onclick = function(event) {
        if (!event.target.closest('button')) {
            document.querySelectorAll('[id^="drop-emp-"]').forEach(el => {
                el.classList.add('hidden');
            });
        }
    }

    function abrirModalSesion() {
        document.getElementById('modalCerrarSesion').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalSesion() {
        document.getElementById('modalCerrarSesion').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
</script>
<script>
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
    function cerrarModalToggle() {
        document.getElementById('modalToggle').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    function cerrarModal() {
        document.getElementById('modalEliminar').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // ── Editar: pre-llenar campos ──────────────────────────────────────────────
    function abrirModalEditar(id, nombre, razon, cuit, idRubro, idProv, tel, emailCont, dom, desc) {
        document.getElementById('editar-id').value             = id;
        document.getElementById('editar-nombre').value         = nombre;
        document.getElementById('editar-razon').value          = razon;
        document.getElementById('editar-cuit').value           = cuit;
        document.getElementById('editar-telefono').value       = tel;
        document.getElementById('editar-email-contacto').value = emailCont;
        document.getElementById('editar-domicilio').value      = dom;
        document.getElementById('editar-descripcion').value    = desc;

        const selRubro = document.getElementById('editar-rubro');
        const selProv  = document.getElementById('editar-provincia');
        if (selRubro) selRubro.value = idRubro || '';
        if (selProv)  selProv.value  = idProv  || '';

        // Limpiar credenciales siempre al abrir
        document.getElementById('editar-email-usuario').value = '';
        document.getElementById('editar-pass').value          = '';
        document.getElementById('editar-pass-confirm').value  = '';

        document.getElementById('modalEditar').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // ── Toggle activar / desactivar ────────────────────────────────────────────
    function abrirModalToggle(id, nombre, estadoActual) {
        const esActivo    = estadoActual === 'activo';
        const nuevoEstado = esActivo ? 'inactivo' : 'activo';
        const iconEl      = document.getElementById('modal-toggle-icon');

        iconEl.className = `w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${esActivo ? 'bg-orange-100' : 'bg-green-100'}`;
        iconEl.innerHTML = esActivo ?
            `<svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>` :
            `<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;

        document.getElementById('modal-toggle-titulo').textContent  = esActivo ? 'Dar de baja empresa' : 'Activar empresa';
        document.getElementById('modal-toggle-empresa').textContent = nombre;
        document.getElementById('modal-toggle-mensaje').innerHTML   = esActivo ?
            `¿Estás seguro de que querés <strong>dar de baja</strong> esta empresa?<br><span class="text-xs text-gray-400 mt-1 block">Sus ofertas publicadas pasarán a inactivo.</span>` :
            `¿Estás seguro de que querés <strong>activar</strong> esta empresa?`;

        const btn = document.getElementById('modal-toggle-btn');
        btn.textContent = esActivo ? 'Sí, dar baja' : 'Sí, activar';
        btn.className   = esActivo ?
            'px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition' :
            'px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition';

        document.getElementById('toggle-id-empresa').value  = id;
        document.getElementById('toggle-nuevo-estado').value = nuevoEstado;
        document.getElementById('modalToggle').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // ── Eliminar ───────────────────────────────────────────────────────────────
    function abrirModalEliminar(id, nombre) {
        document.getElementById('modal-empresa-nombre').textContent = nombre;
        document.getElementById('hidden-id-empresa').value          = id;
        document.getElementById('confirm-eliminar-input').value     = '';
        document.getElementById('hidden-confirmar').value           = '';
        document.getElementById('modalEliminar').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('confirm-eliminar-input').focus(), 100);
    }

    function ejecutarEliminar() {
        const txt = document.getElementById('confirm-eliminar-input').value.trim();
        if (txt !== 'ELIMINAR') {
            document.getElementById('confirm-eliminar-input').classList.add('ring-2', 'ring-red-500', 'border-red-500');
            return;
        }
        document.getElementById('hidden-confirmar').value = txt;
        document.getElementById('form-eliminar').submit();
    }

    document.getElementById('confirm-eliminar-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); ejecutarEliminar(); }
        this.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
    });

    // ── Clic exterior + Escape ─────────────────────────────────────────────────
    ['modalCrear', 'modalEditar', 'modalToggle', 'modalEliminar', 'modalCerrarSesion'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            ['modalCrear', 'modalEditar', 'modalToggle', 'modalEliminar', 'modalCerrarSesion'].forEach(id => {
                document.getElementById(id)?.classList.add('hidden');
            });
            document.body.style.overflow = 'auto';
        }
    });

    // ── CUIT mask ──────────────────────────────────────────────────────────────
    document.querySelectorAll('.cuit-mask').forEach(input => {
        input.addEventListener('input', function() {
            let v = this.value.replace(/\D/g, '');
            if (v.length > 2)  v = v.slice(0, 2)  + '-' + v.slice(2);
            if (v.length > 11) v = v.slice(0, 11) + '-' + v.slice(11);
            this.value = v.slice(0, 13);
        });
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

        <?php if ($err_msg && ($_POST['accion'] ?? '') === 'crear_empresa'): ?>
            abrirModalCrear();
        <?php endif; ?>
        <?php if ($err_msg && ($_POST['accion'] ?? '') === 'editar_empresa'): ?>
            abrirModalEditar(
                <?= intval($_POST['id_empresa'] ?? 0) ?>,
                '<?= addslashes($_POST['nombre_empresa'] ?? '') ?>',
                '<?= addslashes($_POST['razon_social']   ?? '') ?>',
                '<?= addslashes($_POST['cuit']           ?? '') ?>',
                <?= intval($_POST['id_rubro']    ?? 0) ?>,
                <?= intval($_POST['id_provincia'] ?? 0) ?>,
                '<?= addslashes($_POST['telefono']       ?? '') ?>',
                '<?= addslashes($_POST['email_contacto'] ?? '') ?>',
                '<?= addslashes($_POST['domicilio']      ?? '') ?>',
                '<?= addslashes($_POST['descripcion_empresa'] ?? '') ?>'
            );
        <?php endif; ?>
    });
</script>