<?php
include_once("conexion.php");

date_default_timezone_set('America/Argentina/Buenos_Aires');

$nombreCompleto = 'Usuario';
$tipo_usuario   = null;
$tipo_nombre    = 'Usuario';
$EmailUsuario   = '';
$foto_perfil    = './img/profile.png';
$notis_count    = 0;
$ultimas_notis  = [];

function getFotoPerfil($imagen_perfil) {
    if (!empty($imagen_perfil) && file_exists('uploads/perfil/' . $imagen_perfil)) {
        return 'uploads/perfil/' . $imagen_perfil;
    }
    return './img/profile.png';
}

if (isset($_SESSION['idusuario'])) {
    $id_usuario   = intval($_SESSION['idusuario']);
    $tipo_usuario = $_SESSION['tipo'];
    $tipo_nombre  = $_SESSION['tipo_nombre'] ?? 'Usuario';
    $EmailUsuario = $_SESSION['emailusuario'] ?? '';

    if ($tipo_usuario == 2) {
        $sql = "SELECT CONCAT(UPPER(p.nombre), ' ', UPPER(p.apellido)) as nombre_completo,
                       p.imagen_perfil
                FROM users u
                INNER JOIN persona p ON u.id_persona = p.id_persona
                WHERE u.id_usuario = $id_usuario";
        $resultado = mysqli_query($conexion, $sql);
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $datos          = mysqli_fetch_assoc($resultado);
            $nombreCompleto = $datos['nombre_completo'];
            $foto_perfil    = getFotoPerfil($datos['imagen_perfil']);
        }

        // ── Contar notificaciones no leídas ──────────────────────────────
        $res_count = mysqli_query($conexion,
            "SELECT COUNT(*) as total FROM notificaciones
             WHERE id_usuario = $id_usuario AND leida = 0"
        );
        if ($res_count) {
            $notis_count = intval(mysqli_fetch_assoc($res_count)['total']);
        }

        // ── Traer las últimas 5 notificaciones (leídas y no leídas) ──────
        $res_notis = mysqli_query($conexion,
            "SELECT id_notificacion, tipo, titulo, mensaje, leida, url_accion, fecha_creacion
             FROM notificaciones
             WHERE id_usuario = $id_usuario
             ORDER BY leida ASC, fecha_creacion DESC
             LIMIT 5"
        );
        if ($res_notis) {
            while ($n = mysqli_fetch_assoc($res_notis)) $ultimas_notis[] = $n;
        }

    } else {
        $nombreCompleto = strtoupper($_SESSION['nombreusuario'] ?? 'Usuario');
    }
}

// Helper tiempo relativo para el navbar — con timezone Argentina
function tiempoRelativoNav($fecha) {
    $tz       = new DateTimeZone('America/Argentina/Buenos_Aires');
    $ahora    = new DateTime('now', $tz);
    $dt_fecha = new DateTime($fecha, $tz);
    $diff     = $ahora->getTimestamp() - $dt_fecha->getTimestamp();

    if ($diff < 60)     return 'Ahora';
    if ($diff < 3600)   return floor($diff / 60) . ' min';
    if ($diff < 7200)   return '1 h';
    if ($diff < 86400)  return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' días';
    return (new DateTime($fecha, $tz))->format('d/m');
}

// Colores por tipo de notificación
function notiIconoNav($tipo, $titulo) {
    switch ($tipo) {
        case 'postulacion':
            if (stripos($titulo, 'aceptada') !== false)  return ['bg' => 'bg-green-100',  'color' => 'text-green-600',  'icon' => 'check-circle'];
            if (stripos($titulo, 'rechazada') !== false) return ['bg' => 'bg-red-100',    'color' => 'text-red-500',    'icon' => 'x-circle'];
            if (stripos($titulo, 'entrevista') !== false)return ['bg' => 'bg-purple-100', 'color' => 'text-purple-600', 'icon' => 'calendar'];
            return ['bg' => 'bg-blue-100', 'color' => 'text-blue-600', 'icon' => 'document'];
        case 'oferta':
            return ['bg' => 'bg-blue-100',   'color' => 'text-blue-600',   'icon' => 'briefcase'];
        default:
            return ['bg' => 'bg-amber-100',  'color' => 'text-amber-600',  'icon' => 'info'];
    }
}
?>

<header class="bg-white shadow-lg py-4 sticky top-0 z-50">
    <div class="container mx-auto flex items-center justify-between px-4">

        <!-- Logo -->
        <a href="index.php" class="flex items-center text-gray-800 hover:text-cyan-600">
            <svg class="h-8 w-8 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
            </svg>
            <span class="text-2xl font-bold">YoConstructor</span>
        </a>

        <!-- Mobile Menu Button -->
        <div class="md:hidden flex items-center gap-2">
            <?php if (isset($_SESSION['idusuario']) && $tipo_usuario == 2): ?>
            <a href="notificaciones.php" class="relative p-2 text-gray-500 hover:text-blue-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
                <?php if ($notis_count > 0): ?>
                <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none">
                    <?= $notis_count > 9 ? '9+' : $notis_count ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <button id="menu-toggle" class="text-gray-800 focus:outline-none">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
            </button>
        </div>

        <!-- NAVBAR Desktop -->
        <nav class="hidden md:block">
            <ul class="flex space-x-4 items-center">
                <li><a href="index.php" class="hover:text-cyan-600 transition-colors duration-300">Inicio</a></li>
                <li><a href="ofertas-laborales.php" class="hover:text-cyan-600 transition-colors duration-300">Ofertas laborales</a></li>
                <li><a href="nosotros.php" class="hover:text-cyan-600 transition-colors duration-300">Nosotros</a></li>

                <?php if (isset($_SESSION['idusuario']) && $tipo_usuario == 2): ?>
                <li class="relative">
                    <button id="btnCampanita" onclick="toggleNotisDropdown()"
                        class="relative p-2 text-gray-500 hover:text-blue-600 transition rounded-xl hover:bg-blue-50">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                        </svg>
                        <?php if ($notis_count > 0): ?>
                        <span id="notis-badge" class="absolute top-0.5 right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 leading-none">
                            <?= $notis_count > 9 ? '9+' : $notis_count ?>
                        </span>
                        <?php else: ?>
                        <span id="notis-badge" class="hidden absolute top-0.5 right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 leading-none"></span>
                        <?php endif; ?>
                    </button>

                    <div id="dropdownNotis"
                        class="hidden absolute right-0 top-full mt-2 w-96 bg-white border border-gray-200 rounded-2xl shadow-2xl z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-gray-800">Notificaciones</span>
                                <?php if ($notis_count > 0): ?>
                                <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                    <?= $notis_count ?> sin leer
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($notis_count > 0): ?>
                            <form method="POST" action="notificaciones.php">
                                <input type="hidden" name="marcar_todas" value="1">
                                <button type="submit" class="text-xs text-blue-600 hover:text-blue-700 font-medium transition">
                                    Marcar todas leídas
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($ultimas_notis)): ?>
                        <div class="px-4 py-10 text-center">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Sin notificaciones</p>
                            <p class="text-xs text-gray-400 mt-1">Cuando haya novedades aparecerán acá</p>
                        </div>
                        <?php else: ?>
                        <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                            <?php foreach ($ultimas_notis as $noti):
                                $cfg    = notiIconoNav($noti['tipo'], $noti['titulo']);
                                $leida  = (bool) $noti['leida'];
                                $bg     = $leida ? '' : 'bg-blue-50/40';
                                $target = !empty($noti['url_accion']) ? $noti['url_accion'] : 'notificaciones.php';
                            ?>
                            <li>
                                <a href="<?= htmlspecialchars($target) ?>"
                                   onclick="marcarLeidaYNavegar(event, <?= $noti['id_notificacion'] ?>, '<?= htmlspecialchars($target) ?>')"
                                   class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition cursor-pointer <?= $bg ?>">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="w-9 h-9 <?= $cfg['bg'] ?> rounded-xl flex items-center justify-center">
                                            <?php
                                            $svg_paths = [
                                                'check-circle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                                'x-circle'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                                'calendar'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                                                'document'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
                                                'briefcase'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                                                'info'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                            ];
                                            $path = $svg_paths[$cfg['icon']] ?? $svg_paths['info'];
                                            ?>
                                            <svg class="w-4 h-4 <?= $cfg['color'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <?= $path ?>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-xs font-semibold text-gray-900 truncate">
                                                <?= htmlspecialchars($noti['titulo']) ?>
                                            </p>
                                            <span class="text-[10px] text-gray-400 whitespace-nowrap flex-shrink-0">
                                                <?= tiempoRelativoNav($noti['fecha_creacion']) ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2 leading-relaxed">
                                            <?= htmlspecialchars($noti['mensaje']) ?>
                                        </p>
                                    </div>
                                    <?php if (!$leida): ?>
                                    <div class="flex-shrink-0 mt-2">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full block"></span>
                                    </div>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>

                        <div class="border-t border-gray-100 px-4 py-2.5 bg-gray-50">
                            <a href="notificaciones.php"
                               class="block text-center text-xs font-semibold text-blue-600 hover:text-blue-700 transition">
                                Ver todas las notificaciones →
                            </a>
                        </div>
                    </div>
                </li>
                <?php endif; ?>

                <li class="relative">
                    <?php if (isset($_SESSION['idusuario'])): ?>
                        <button id="dropdownAvatarNameButton" data-dropdown-toggle="dropdownAvatarName"
                            class="flex items-center text-base font-medium text-gray-700 hover:text-cyan-600 transition-colors duration-300" type="button">
                            <span class="sr-only">Open user menu</span>
                            <?= $nombreCompleto ?>
                            <svg class="w-4 h-4 ms-2" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                        </button>

                        <div id="dropdownAvatarName" class="z-50 hidden absolute right-0 top-full mt-2 bg-white border border-gray-200 rounded-lg shadow-xl w-72">
                            <div class="p-3">
                                <div class="flex items-center px-3 py-2.5 space-x-3 text-sm bg-gray-50 rounded-lg border border-gray-100">
                                    <img class="w-10 h-10 rounded-full object-cover flex-shrink-0 border-2 border-gray-200"
                                        src="<?= $foto_perfil ?>" alt="foto">
                                    <div class="flex-1 min-w-0">
                                        <div class="truncate text-gray-900 font-semibold"><?= $nombreCompleto ?></div>
                                        <div class="truncate text-gray-500 text-xs"><?= $tipo_nombre ?></div>
                                        <div class="truncate text-gray-500 text-xs"><?= $EmailUsuario ?></div>
                                    </div>
                                </div>
                            </div>
                            <ul class="px-2 pb-2 text-sm text-gray-700 font-medium">
                                <li><a href="perfil-trabajador.php" class="flex items-center w-full px-3 py-2.5 hover:bg-gray-100 rounded-lg transition-colors duration-300">
                                    <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M7 17v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3Zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    Mi perfil
                                </a></li>
                                <li><a href="mis-postulaciones.php" class="flex items-center w-full px-3 py-2.5 hover:bg-gray-100 rounded-lg transition-colors duration-300">
                                    <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3m-3 3h3m-3 3h3m-6 1c-.306-.613-.933-1-1.618-1H7.618c-.685 0-1.312.387-1.618 1M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm7 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
                                    Mis postulaciones
                                </a></li>
                                <li><a href="notificaciones.php" class="flex items-center w-full px-3 py-2.5 hover:bg-gray-100 rounded-lg transition-colors duration-300">
                                    <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5.365V3m0 2.365a5.338 5.338 0 0 1 5.133 5.368v1.8c0 2.386 1.867 2.982 1.867 4.175 0 .593 0 1.292-.538 1.292H5.538C5 18 5 17.301 5 16.708c0-1.193 1.867-1.789 1.867-4.175v-1.8A5.338 5.338 0 0 1 12 5.365ZM8.733 18c.094.852.306 1.54.944 2.112a3.48 3.48 0 0 0 4.646 0c.638-.572 1.236-1.26 1.33-2.112h-6.92Z"/></svg>
                                    <span class="flex-1">Notificaciones</span>
                                    <?php if ($notis_count > 0): ?>
                                    <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full ml-auto"><?= $notis_count ?></span>
                                    <?php endif; ?>
                                </a></li>
                                <li><a href="configuraciones-trabajador.php" class="flex items-center w-full px-3 py-2.5 hover:bg-gray-100 rounded-lg transition-colors duration-300">
                                    <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M20 6H10m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4m16 6h-2m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4m16 6H10m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4"/></svg>
                                    Configuraciones
                                </a></li>
                            </ul>
                            <div class="border-t border-gray-200 px-2 py-2">
                                <button onclick="abrirModalSesion()" class="flex items-center w-full px-3 py-2.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-300">
                                    <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2"/></svg>
                                    Cerrar sesión
                                </button>
                            </div>
                        </div>

                    <?php else: ?>
                        <button id="dropdownDividerButton" data-dropdown-toggle="dropdownDivider"
                            class="hover:text-cyan-600 transition-colors duration-300 inline-flex items-center" type="button">
                            Ingresar
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                        </button>
                        <div id="dropdownDivider" class="z-10 hidden absolute top-full mt-2 bg-white border border-gray-200 rounded-lg divide-y divide-gray-200 shadow-lg w-44">
                            <ul class="p-2 text-sm text-gray-700 font-medium">
                                <li><a href="login.php" class="block w-full p-2 hover:bg-gray-100 rounded transition-colors duration-300">Tengo cuenta!</a></li>
                                <li><a href="registrarme.php" class="block w-full p-2 hover:bg-gray-100 rounded transition-colors duration-300">Registrarme</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Mobile Menu -->
    <nav id="mobile-menu" class="hidden md:hidden bg-gray-50 border-t border-gray-200">
        <ul class="px-4 py-2">
            <li><a href="index.php" class="block py-2 hover:text-cyan-600 transition-colors duration-300">Inicio</a></li>
            <li><a href="ofertas-laborales.php" class="block py-2 hover:text-cyan-600 transition-colors duration-300">Ofertas laborales</a></li>
            <li><a href="nosotros.php" class="block py-2 hover:text-cyan-600 transition-colors duration-300">Nosotros</a></li>
            <li class="mt-2">
                <?php if (isset($_SESSION['idusuario'])): ?>
                    <button id="mobile-user-toggle" class="w-full flex items-center justify-between py-2 hover:text-cyan-600 transition-colors duration-300">
                        <div class="flex items-center gap-2">
                            <img class="w-7 h-7 rounded-full object-cover border border-gray-200" src="<?= $foto_perfil ?>" alt="foto">
                            <span class="font-medium"><?= $nombreCompleto ?></span>
                            <?php if ($notis_count > 0): ?>
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                <?= $notis_count > 9 ? '9+' : $notis_count ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <svg id="mobile-user-icon" class="w-4 h-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="mobile-user-dropdown" class="hidden mt-2 bg-white border border-gray-200 rounded-lg shadow-lg">
                        <div class="p-3 border-b border-gray-200">
                            <div class="flex items-center space-x-3">
                                <img class="w-10 h-10 rounded-full object-cover flex-shrink-0 border-2 border-gray-200" src="<?= $foto_perfil ?>" alt="foto">
                                <div class="flex-1 min-w-0">
                                    <div class="truncate text-gray-900 font-semibold text-sm"><?= $nombreCompleto ?></div>
                                    <div class="truncate text-gray-500 text-xs"><?= $tipo_nombre ?></div>
                                    <div class="truncate text-gray-500 text-xs"><?= $EmailUsuario ?></div>
                                </div>
                            </div>
                        </div>
                        <ul class="py-2 text-sm text-gray-700 font-medium">
                            <li><a href="perfil-trabajador.php" class="flex items-center px-4 py-2.5 hover:bg-gray-100 transition-colors duration-300">
                                <svg class="w-5 h-5 mr-3 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M7 17v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3Zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                Mi perfil
                            </a></li>
                            <li><a href="mis-postulaciones.php" class="flex items-center px-4 py-2.5 hover:bg-gray-100 transition-colors duration-300">
                                <svg class="w-5 h-5 mr-3 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3m-3 3h3m-3 3h3m-6 1c-.306-.613-.933-1-1.618-1H7.618c-.685 0-1.312.387-1.618 1M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm7 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
                                Mis postulaciones
                            </a></li>
                            <li><a href="notificaciones.php" class="flex items-center px-4 py-2.5 hover:bg-gray-100 transition-colors duration-300">
                                <svg class="w-5 h-5 mr-3 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5.365V3m0 2.365a5.338 5.338 0 0 1 5.133 5.368v1.8c0 2.386 1.867 2.982 1.867 4.175 0 .593 0 1.292-.538 1.292H5.538C5 18 5 17.301 5 16.708c0-1.193 1.867-1.789 1.867-4.175v-1.8A5.338 5.338 0 0 1 12 5.365ZM8.733 18c.094.852.306 1.54.944 2.112a3.48 3.48 0 0 0 4.646 0c.638-.572 1.236-1.26 1.33-2.112h-6.92Z"/></svg>
                                <span class="flex-1">Notificaciones</span>
                                <?php if ($notis_count > 0): ?>
                                <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $notis_count ?></span>
                                <?php endif; ?>
                            </a></li>
                            <li><a href="configuraciones-trabajador.php" class="flex items-center px-4 py-2.5 hover:bg-gray-100 transition-colors duration-300">
                                <svg class="w-5 h-5 mr-3 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M20 6H10m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4m16 6h-2m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4m16 6H10m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4"/></svg>
                                Configuraciones
                            </a></li>
                        </ul>
                        <div class="border-t border-gray-200 py-2">
                            <button onclick="abrirModalSesion()" class="flex items-center w-full px-4 py-2.5 text-red-600 hover:bg-red-50 transition-colors duration-300">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2"/></svg>
                                Cerrar sesión
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <button id="mobile-dropdown-toggle" class="w-full text-left py-2 hover:text-cyan-600 transition-colors duration-300 flex items-center justify-between">
                        Ingresar
                        <svg id="mobile-dropdown-icon" class="w-4 h-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul id="mobile-dropdown" class="hidden pl-4 space-y-1 mt-2">
                        <li><a href="login.php" class="block py-2 px-3 hover:bg-gray-100 rounded hover:text-cyan-600 transition-colors duration-300">Tengo cuenta!</a></li>
                        <li><a href="registrarme.php" class="block py-2 px-3 hover:bg-gray-100 rounded hover:text-cyan-600 transition-colors duration-300">Registrarme</a></li>
                    </ul>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
</header>

<!-- MODAL CERRAR SESIÓN -->
<div id="modalCerrarSesion" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
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
                class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">
                Cancelar
            </button>
            <a href="cerrar-session.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sí, cerrar sesión
            </a>
        </div>
    </div>
</div>

<script>
function toggleNotisDropdown() {
    const dropdown = document.getElementById('dropdownNotis');
    const isHidden = dropdown.classList.contains('hidden');
    document.getElementById('dropdownAvatarName')?.classList.add('hidden');
    document.getElementById('dropdownDivider')?.classList.add('hidden');
    dropdown.classList.toggle('hidden', !isHidden);
}

document.addEventListener('click', function(e) {
    const btn      = document.getElementById('btnCampanita');
    const dropdown = document.getElementById('dropdownNotis');
    if (!btn || !dropdown) return;
    if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

function marcarLeidaYNavegar(e, idNoti, url) {
    e.preventDefault();
    fetch('marcar_notificacion.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_notificacion=' + idNoti
    }).finally(() => {
        const badge = document.getElementById('notis-badge');
        if (badge) {
            const actual = parseInt(badge.textContent) || 0;
            const nuevo  = Math.max(0, actual - 1);
            if (nuevo === 0) {
                badge.classList.add('hidden');
            } else {
                badge.textContent = nuevo > 9 ? '9+' : nuevo;
            }
        }
        window.location.href = url;
    });
}

function abrirModalSesion() {
    document.getElementById('dropdownAvatarName')?.classList.add('hidden');
    document.getElementById('mobile-user-dropdown')?.classList.add('hidden');
    document.getElementById('dropdownNotis')?.classList.add('hidden');
    document.getElementById('modalCerrarSesion').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalSesion() {
    document.getElementById('modalCerrarSesion').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
document.getElementById('modalCerrarSesion').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalSesion();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalSesion();
        document.getElementById('dropdownNotis')?.classList.add('hidden');
    }
});

const menuToggle = document.getElementById('menu-toggle');
const mobileMenu = document.getElementById('mobile-menu');
if (menuToggle) {
    menuToggle.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
}

const dropdownButton = document.getElementById('dropdownDividerButton');
const dropdownMenu   = document.getElementById('dropdownDivider');
if (dropdownButton) {
    dropdownButton.addEventListener('click', () => dropdownMenu.classList.toggle('hidden'));
    document.addEventListener('click', (e) => {
        if (!dropdownButton.contains(e.target) && !dropdownMenu?.contains(e.target))
            dropdownMenu?.classList.add('hidden');
    });
}

const mobileUserToggle   = document.getElementById('mobile-user-toggle');
const mobileUserDropdown = document.getElementById('mobile-user-dropdown');
const mobileUserIcon     = document.getElementById('mobile-user-icon');
if (mobileUserToggle) {
    mobileUserToggle.addEventListener('click', (e) => {
        e.preventDefault();
        mobileUserDropdown.classList.toggle('hidden');
        mobileUserIcon.classList.toggle('rotate-180');
    });
}

const mobileDropdownToggle = document.getElementById('mobile-dropdown-toggle');
const mobileDropdown       = document.getElementById('mobile-dropdown');
const mobileDropdownIcon   = document.getElementById('mobile-dropdown-icon');
if (mobileDropdownToggle) {
    mobileDropdownToggle.addEventListener('click', (e) => {
        e.preventDefault();
        mobileDropdown.classList.toggle('hidden');
        mobileDropdownIcon.classList.toggle('rotate-180');
    });
}

document.querySelectorAll('#mobile-menu a').forEach(link => {
    link.addEventListener('click', () => {
        if (link.getAttribute('href') && link.getAttribute('href') !== '#')
            mobileMenu.classList.add('hidden');
    });
});
</script>