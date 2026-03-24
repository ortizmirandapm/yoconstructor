<?php
if (!isset($page))      $page      = 'dashboard';
if (!isset($pageTitle)) $pageTitle = 'Dashboard';

function isActive($pageId, $currentPage)
{
    return $pageId === $currentPage ? 'bg-indigo-50 text-indigo-600 border-l-4 border-indigo-600 font-semibold' : '';
}
function isSubmenuActive($pageId, $currentPage)
{
    return $pageId === $currentPage ? 'bg-gray-100 text-indigo-600 font-medium' : '';
}
function iconActive($pageId, $currentPage)
{
    return $pageId === $currentPage ? 'text-indigo-600' : 'text-gray-500';
}
function submenuExpanded($pageIds, $currentPage)
{
    return in_array($currentPage, $pageIds) ? '' : 'hidden';
}
function arrowRotated($pageIds, $currentPage)
{
    return in_array($currentPage, $pageIds) ? 'rotate-180' : '';
}

$nombreUsuario     = 'Usuario';
$emailUsuario      = 'email@ejemplo.com';
$tipo_usuario      = null;
$tipo_nombre       = 'Usuario';
$reclutador_nombre = '';

$empresa = [
    'id_empresa'    => null,
    'nombre_empresa' => 'Mi Empresa',
    'razon_social'  => null,
    'cuit'          => null,
    'rubro'         => null,
    'provincia'     => null,
    'telefono'      => null,
    'email_contacto' => null,
    'logo'          => null,
    'id_rubro'      => null,
    'id_provincia'  => null,
    'fecha_ingreso' => null,
];

$stats = [
    'ofertas_activas' => 0,
    'ofertas_borradores' => 0,
    'ofertas_cerradas' => 0,
    'postulaciones_pendientes' => 0,
    'postulaciones_revisadas' => 0,
    'postulaciones_aceptadas' => 0,
    'postulaciones_total' => 0,
    'visitas_semana' => 0,
    'mensajes_nuevos' => 0,
];

if (isset($_SESSION['idusuario'])) {

    $id_usuario  = $_SESSION['idusuario'];
    $nombreUsuario = strtoupper($_SESSION['nombreusuario'] ?? 'Usuario');
    $emailUsuario  = $_SESSION['emailusuario'] ?? 'email@ejemplo.com';
    $tipo_usuario  = $_SESSION['tipo'];
    $tipo_nombre   = $_SESSION['tipo_nombre'] ?? 'Usuario';

    // ── EMPRESA (tipo 3) ────────────────────────────────────────────
    if ($tipo_usuario == 3) {

        $res = mysqli_query(
            $conexion,
            "SELECT e.*, r.nombre AS rubro_nombre, p.nombre AS provincia_nombre
             FROM users u
             INNER JOIN empresa e ON u.id_empresa = e.id_empresa
             LEFT JOIN rubros r   ON e.id_rubro   = r.id_rubro
             LEFT JOIN provincias p ON e.id_provincia = p.id_provincia
             WHERE u.id_usuario = '$id_usuario'"
        );

        if ($res && mysqli_num_rows($res) > 0) {
            $d = mysqli_fetch_assoc($res);
            $empresa = [
                'id_empresa'    => $d['id_empresa'],
                'nombre_empresa' => $d['nombre_empresa']   ?? 'Mi Empresa',
                'razon_social'  => $d['razon_social']     ?? null,
                'cuit'          => $d['cuit']             ?? null,
                'id_rubro'      => $d['id_rubro']         ?? null,
                'rubro'         => $d['rubro_nombre']     ?? null,
                'id_provincia'  => $d['id_provincia']     ?? null,
                'provincia'     => $d['provincia_nombre'] ?? null,
                'telefono'      => $d['telefono']         ?? null,
                'email_contacto' => $d['email_contacto']   ?? null,
                'logo'          => $d['logo']             ?? null,
                'fecha_ingreso' => $d['fecha_ingreso']    ?? null,
            ];
        }

        $id_emp = $empresa['id_empresa'];
        $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM ofertas_laborales WHERE id_empresa='$id_emp' AND estado='Activa'");
        if ($r) $stats['ofertas_activas'] = mysqli_fetch_assoc($r)['t'];
        $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM ofertas_laborales WHERE id_empresa='$id_emp' AND estado='Borrador'");
        if ($r) $stats['ofertas_borradores'] = mysqli_fetch_assoc($r)['t'];
        $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM postulaciones pos INNER JOIN ofertas_laborales o ON pos.id_oferta=o.id_oferta WHERE o.id_empresa='$id_emp' AND pos.estado='Pendiente'");
        if ($r) $stats['postulaciones_pendientes'] = mysqli_fetch_assoc($r)['t'];
        $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM postulaciones pos INNER JOIN ofertas_laborales o ON pos.id_oferta=o.id_oferta WHERE o.id_empresa='$id_emp' AND pos.estado='Revisada'");
        if ($r) $stats['postulaciones_revisadas'] = mysqli_fetch_assoc($r)['t'];
        $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM postulaciones pos INNER JOIN ofertas_laborales o ON pos.id_oferta=o.id_oferta WHERE o.id_empresa='$id_emp' AND pos.estado='Aceptada'");
        if ($r) $stats['postulaciones_aceptadas'] = mysqli_fetch_assoc($r)['t'];
        $stats['postulaciones_total'] = $stats['postulaciones_pendientes'] + $stats['postulaciones_revisadas'] + $stats['postulaciones_aceptadas'];
        $stats['visitas_semana'] = 234;

        // ── RECLUTADOR (tipo 4) ─────────────────────────────────────────
    } elseif ($tipo_usuario == 4) {

        $res = mysqli_query(
            $conexion,
            "SELECT rec.nombre, rec.apellido,
                    e.id_empresa, e.nombre_empresa, e.logo, e.email_contacto,
                    e.id_rubro, e.id_provincia, e.cuit, e.razon_social, e.telefono, e.fecha_ingreso
             FROM reclutadores rec
             INNER JOIN empresa e ON rec.id_empresa = e.id_empresa
             WHERE rec.id_usuario = '$id_usuario'
             LIMIT 1"
        );

        if ($res && mysqli_num_rows($res) > 0) {
            $d = mysqli_fetch_assoc($res);

            $reclutador_nombre = ucwords(strtolower(trim(($d['nombre'] ?? '') . ' ' . ($d['apellido'] ?? ''))));

            $empresa = [
                'id_empresa'    => $d['id_empresa'],
                'nombre_empresa' => $d['nombre_empresa'] ?? 'Mi Empresa',
                'razon_social'  => $d['razon_social']   ?? null,
                'cuit'          => $d['cuit']           ?? null,
                'id_rubro'      => $d['id_rubro']       ?? null,
                'rubro'         => null,
                'id_provincia'  => $d['id_provincia']   ?? null,
                'provincia'     => null,
                'telefono'      => $d['telefono']       ?? null,
                'email_contacto' => $d['email_contacto'] ?? null,
                'logo'          => $d['logo']           ?? null,
                'fecha_ingreso' => $d['fecha_ingreso']  ?? null,
            ];

            // Email del reclutador para el header
            $res_email = mysqli_query($conexion, "SELECT email FROM users WHERE id_usuario = '$id_usuario' LIMIT 1");
            if ($res_email) {
                $row_email = mysqli_fetch_assoc($res_email);
                $emailUsuario = $row_email['email'] ?? $emailUsuario;
            }

            // Estadísticas de la empresa del reclutador
            $id_emp = $empresa['id_empresa'];
            $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM ofertas_laborales WHERE id_empresa='$id_emp' AND estado='Activa'");
            if ($r) $stats['ofertas_activas'] = mysqli_fetch_assoc($r)['t'];
            $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM ofertas_laborales WHERE id_empresa='$id_emp' AND estado='Borrador'");
            if ($r) $stats['ofertas_borradores'] = mysqli_fetch_assoc($r)['t'];
            $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM postulaciones pos INNER JOIN ofertas_laborales o ON pos.id_oferta=o.id_oferta WHERE o.id_empresa='$id_emp' AND pos.estado='Pendiente'");
            if ($r) $stats['postulaciones_pendientes'] = mysqli_fetch_assoc($r)['t'];
            $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM postulaciones pos INNER JOIN ofertas_laborales o ON pos.id_oferta=o.id_oferta WHERE o.id_empresa='$id_emp' AND pos.estado='Revisada'");
            if ($r) $stats['postulaciones_revisadas'] = mysqli_fetch_assoc($r)['t'];
            $r = mysqli_query($conexion, "SELECT COUNT(*) as t FROM postulaciones pos INNER JOIN ofertas_laborales o ON pos.id_oferta=o.id_oferta WHERE o.id_empresa='$id_emp' AND pos.estado='Aceptada'");
            if ($r) $stats['postulaciones_aceptadas'] = mysqli_fetch_assoc($r)['t'];
            $stats['postulaciones_total'] = $stats['postulaciones_pendientes'] + $stats['postulaciones_revisadas'] + $stats['postulaciones_aceptadas'];

            // Asegurar id_empresa en sesión
            if (!isset($_SESSION['idempresa'])) $_SESSION['idempresa'] = $empresa['id_empresa'];
        }
    }
} else {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - YoConstructor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full md:translate-x-0 bg-white shadow-lg">
        <div class="h-full px-3 py-4 overflow-y-auto">

            <div class="mb-8 px-3">
                <h2 class="text-2xl font-bold text-indigo-600">YoConstructor</h2>
                <p class="text-xs text-gray-500 mt-1">
                    <?php if ($tipo_usuario == 4 && $reclutador_nombre): ?>
                        ¡Bienvenido, <?php echo htmlspecialchars($reclutador_nombre); ?>!
                    <?php else: ?>
                        ¡Bienvenido, <?php echo htmlspecialchars($empresa['nombre_empresa']); ?>!
                    <?php endif; ?>
                </p>
            </div>

            <ul class="space-y-2 font-medium">

                <!-- Dashboard -->
                <li>
                    <a href="index-empresa.php" class="flex items-center p-3 text-gray-900 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 group transition-all <?php echo isActive('dashboard', $page); ?>">
                        <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?php echo iconActive('dashboard', $page); ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                            <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                        </svg>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>

                <!-- Mi Empresa / Mi Perfil -->
                <li>
                    <?php if ($tipo_usuario == 3): ?>
                        <button type="button" onclick="toggleSubmenu('empresa')" class="flex items-center w-full p-3 text-gray-900 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 group transition-all">
                            <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?php echo in_array($page, ['perfil-empresa', 'reclutadores']) ? 'text-indigo-600' : 'text-gray-500'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="flex-1 ml-3 text-left">Mi Empresa</span>
                            <svg id="arrow-empresa" class="w-4 h-4 transition-transform <?php echo arrowRotated(['perfil-empresa', 'reclutadores'], $page); ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>

                        <ul id="submenu-empresa" class="<?php echo submenuExpanded(['perfil-empresa', 'reclutadores', 'auditoria'], $page); ?> py-2 space-y-2">
                            <li><a href="perfil-empresa.php" class="flex items-center w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?php echo isSubmenuActive('perfil-empresa', $page); ?>">Perfil</a></li>
                            <li><a href="reclutadores.php" class="flex items-center w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?php echo isSubmenuActive('reclutadores',   $page); ?>">Reclutadores</a></li>
                            <li><a href="auditoria-empresa.php" class="flex items-center w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?php echo isSubmenuActive('auditoria', $page); ?>">Auditoria</a></li>

                        </ul>

                    <?php elseif ($tipo_usuario == 4): ?>
                        <a href="perfil-reclutador.php" class="flex items-center p-3 text-gray-900 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 group transition-all <?php echo isActive('perfil-reclutador', $page); ?>">
                            <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?php echo iconActive('perfil-reclutador', $page); ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3">Mi Perfil</span>
                        </a>
                    <?php endif; ?>
                </li>

                <!-- Nueva Oferta -->
                <li>
                    <a href="nueva-oferta.php" class="flex items-center p-3 rounded-lg transition-all group <?php echo $page === 'nueva-oferta' ? 'bg-indigo-700' : 'bg-indigo-600 hover:bg-indigo-700'; ?> text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-3 font-semibold">Nueva Oferta</span>
                    </a>
                </li>

                <!-- Ofertas -->
                <li>
                    <button type="button" onclick="toggleSubmenu('ofertas')" class="flex items-center w-full p-3 text-gray-900 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 group transition-all">
                        <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?php echo in_array($page, ['ofertas-publicadas', 'ofertas-borradores', 'ofertas-cerradas']) ? 'text-indigo-600' : 'text-gray-500'; ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="flex-1 ml-3 text-left">Ofertas</span>
                        <svg id="arrow-ofertas" class="w-4 h-4 transition-transform <?php echo arrowRotated(['ofertas-publicadas', 'ofertas-borradores', 'ofertas-cerradas'], $page); ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <ul id="submenu-ofertas" class="<?php echo submenuExpanded(['ofertas-publicadas', 'ofertas-borradores', 'ofertas-cerradas'], $page); ?> py-2 space-y-2">
                        <li>
                            <a href="ofertas-publicadas.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?php echo isSubmenuActive('ofertas-publicadas', $page); ?>">
                                <span>Publicadas</span>
                            </a>
                        </li>
                        <li>
                            <a href="ofertas-borradores.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?php echo isSubmenuActive('ofertas-borradores', $page); ?>">
                                <span>Borradores</span>
                            </a>
                        </li>






                    </ul>
                </li>

                <!-- Postulantes -->
                <li>
                    <a href="postulantes-global.php" class="flex items-center p-3 text-gray-900 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 group transition-all <?php echo isActive('postulantes-global', $page); ?>">
                        <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?php echo iconActive('postulantes-global', $page); ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                        </svg>
                        <span class="ml-3">Postulantes</span>
                    </a>
                </li>

                <!-- Buscar Perfiles -->
                <li>
                    <a href="buscar-perfiles.php" class="flex items-center p-3 text-gray-900 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 group transition-all <?php echo isActive('buscar', $page); ?>">
                        <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?php echo iconActive('buscar', $page); ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-3">Buscar Perfiles</span>
                    </a>
                </li>



            </ul>

            <div class="my-4 border-t border-gray-200"></div>

            <ul class="space-y-2 font-medium">
                <?php if ($tipo_usuario == 3): ?>
                    <li>
                        <a href="configuracion-empresa.php" class="flex items-center p-3 text-gray-900 rounded-lg hover:bg-gray-100 group transition-all <?php echo isActive('configuracion-empresa', $page); ?>">
                            <svg class="w-5 h-5 transition duration-75 group-hover:text-gray-900 <?php echo iconActive('configuracion-empresa', $page); ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3">Configuración</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($tipo_usuario == 4): ?>
                    <li>
                        <a href="configuracion-reclutador.php" class="flex items-center p-3 text-gray-900 rounded-lg hover:bg-gray-100 group transition-all <?php echo isActive('configuracion-reclutador', $page); ?>">
                            <svg class="w-5 h-5 transition duration-75 group-hover:text-gray-900 <?php echo iconActive('configuracion-reclutador', $page); ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3">Configuración</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="#" onclick="abrirModalSesion(); return false;" class="flex items-center p-3 text-red-600 rounded-lg hover:bg-red-50 group transition-all">
                        <svg class="w-5 h-5 transition duration-75" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-3">Cerrar Sesión</span>
                    </a>
                </li>
            </ul>

        </div>





    </aside>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }

        function toggleSubmenu(id) {
            const s = document.getElementById('submenu-' + id);
            const a = document.getElementById('arrow-' + id);
            if (s) s.classList.toggle('hidden');
            if (a) a.classList.toggle('rotate-180');
        }
        document.addEventListener('click', function(e) {
            const sb = document.getElementById('sidebar');
            const btn = e.target.closest('button[onclick="toggleSidebar()"]');
            if (!sb.contains(e.target) && !btn && window.innerWidth < 768) sb.classList.add('-translate-x-full');
        });
    </script>

    <div class="md:ml-64">

        <nav class="bg-white border-b border-gray-200 px-4 py-3 md:hidden">
            <div class="flex items-center justify-between">
                <button onclick="toggleSidebar()" class="text-gray-500 hover:bg-gray-100 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <h1 class="text-xl font-bold text-indigo-600">YoConstructor</h1>
                <div class="w-10"></div>
            </div>
        </nav>

        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Panel de <?php echo $tipo_nombre; ?></h1>
                    <p class="text-sm text-gray-500 mt-1">




                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <?php if ($empresa['logo']): ?>
                        <img src="<?php echo htmlspecialchars($empresa['logo']); ?>" class="w-10 h-10 rounded-full object-cover border border-gray-200" alt="Logo">
                    <?php else: ?>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($empresa['nombre_empresa']); ?>&background=4f46e5&color=fff" class="w-10 h-10 rounded-full" alt="Logo">
                    <?php endif; ?>
                    <div class="hidden md:block">
                        <p class="text-sm font-semibold"><?php echo htmlspecialchars($empresa['nombre_empresa']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($emailUsuario); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-0">