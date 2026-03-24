<?php
include_once("conexion.php");
date_default_timezone_set('America/Argentina/Buenos_Aires');
// Verificar sesión
if (!isset($_SESSION['idusuario']) || $_SESSION['tipo'] != 2) {
    header("Location: login.php");
    exit;
}

$id_usuario = intval($_SESSION['idusuario']);

// ── Marcar como leída ──────────────────────────────────────────────────────
if (isset($_POST['marcar_leida']) && is_numeric($_POST['id_notificacion'])) {
    $id_noti = intval($_POST['id_notificacion']);
    mysqli_query($conexion, "UPDATE notificaciones SET leida = 1
                             WHERE id_notificacion = $id_noti AND id_usuario = $id_usuario");
}

// ── Marcar todas como leídas ───────────────────────────────────────────────
if (isset($_POST['marcar_todas'])) {
    mysqli_query($conexion, "UPDATE notificaciones SET leida = 1 WHERE id_usuario = $id_usuario");
}

// ── Filtro activo ──────────────────────────────────────────────────────────
$filtro = $_GET['filtro'] ?? 'todas';
$filtros_validos = ['todas', 'postulacion', 'oferta', 'sistema'];
if (!in_array($filtro, $filtros_validos)) $filtro = 'todas';

$where_tipo = ($filtro !== 'todas') ? "AND tipo = '$filtro'" : '';

// ── Traer notificaciones ───────────────────────────────────────────────────
$sql_notis = "SELECT * FROM notificaciones
              WHERE id_usuario = $id_usuario $where_tipo
              ORDER BY leida ASC, fecha_creacion DESC
              LIMIT 50";
$res_notis = mysqli_query($conexion, $sql_notis);
$notificaciones = [];
if ($res_notis) {
    while ($n = mysqli_fetch_assoc($res_notis)) $notificaciones[] = $n;
}

// ── Conteos por tipo ───────────────────────────────────────────────────────
$sql_counts = "SELECT tipo, COUNT(*) as total, SUM(leida = 0) as no_leidas
               FROM notificaciones WHERE id_usuario = $id_usuario GROUP BY tipo";
$res_counts = mysqli_query($conexion, $sql_counts);
$counts = ['todas' => ['total' => 0, 'no_leidas' => 0], 'postulacion' => ['total' => 0, 'no_leidas' => 0], 'oferta' => ['total' => 0, 'no_leidas' => 0], 'sistema' => ['total' => 0, 'no_leidas' => 0]];
if ($res_counts) {
    while ($c = mysqli_fetch_assoc($res_counts)) {
        $counts[$c['tipo']] = ['total' => $c['total'], 'no_leidas' => $c['no_leidas']];
        $counts['todas']['total']    += $c['total'];
        $counts['todas']['no_leidas'] += $c['no_leidas'];
    }
}

// Helper tiempo relativo
function tiempoRelativo($fecha) {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)  return 'Hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'Hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y', strtotime($fecha));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - YoConstructor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">

<?php include_once("navbar-trabajador.php"); ?>

<main class="min-h-screen py-8">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white shadow-sm border border-gray-200">
            <div class="lg:grid lg:grid-cols-12 lg:divide-x lg:divide-gray-100">

                <!-- SIDEBAR -->
                <aside class="py-6 lg:col-span-3 bg-gray-50 rounded-l-2xl">
                    <nav class="space-y-1 px-2">
                        <a href="perfil-trabajador.php"
                           class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate">Mi perfil</span>
                        </a>
                        <a href="mis-postulaciones.php"
                           class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            <span class="truncate">Mis postulaciones</span>
                        </a>
                        <!-- Activo -->
                        <a href="notificaciones.php"
                           class="bg-blue-50 border-blue-600 text-blue-700 group border-l-4 px-3 py-2 flex items-center text-sm font-semibold rounded-r-xl" aria-current="page">
                            <svg class="text-blue-600 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <span class="truncate">Notificaciones</span>
                          
                        </a>
                        <a href="configuraciones-trabajador.php"
                           class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate">Configuración</span>
                        </a>
                    </nav>
                </aside>

                <!-- CONTENIDO -->
                <div class="lg:col-span-9">
                    <div class="py-6 px-4 sm:p-6 lg:pb-8">

                        <!-- Header -->
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h2 class="text-2xl font-extrabold text-gray-900">Notificaciones</h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    <?php if ($counts['todas']['no_leidas'] > 0): ?>
                                        Tenés <span class="font-semibold text-blue-600"><?= $counts['todas']['no_leidas'] ?></span> sin leer
                                    <?php else: ?>
                                        Todo al día
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($counts['todas']['no_leidas'] > 0): ?>
                            <form method="POST">
                                <input type="hidden" name="marcar_todas" value="1">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Marcar todas como leídas
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>

                        <!-- Filtros por tipo -->
                        <div class="flex flex-wrap gap-2 mb-6">
                            <?php
                            $tabs = [
                                'todas'       => 'Todas',
                                'postulacion' => 'Postulaciones',
                                'oferta'      => 'Ofertas',
                                'sistema'     => 'Sistema',
                            ];
                            foreach ($tabs as $key => $label):
                                $activo    = $filtro === $key;
                                $no_leidas = $counts[$key]['no_leidas'] ?? 0;
                                $base      = $activo
                                    ? "bg-blue-600 text-white border-blue-600"
                                    : "bg-white text-gray-600 border-gray-200 hover:border-blue-400 hover:text-blue-600";
                            ?>
                            <a href="?filtro=<?= $key ?>"
                               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border rounded-xl transition <?= $base ?>">
                                <?= $label ?>
                                <?php if ($no_leidas > 0): ?>
                                    <span class="<?= $activo ? 'bg-white text-blue-700' : 'bg-red-500 text-white' ?> text-xs font-bold px-1.5 py-0.5 rounded-full">
                                        <?= $no_leidas ?>
                                    </span>
                                <?php elseif (($counts[$key]['total'] ?? 0) > 0): ?>
                                    <span class="<?= $activo ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-500' ?> text-xs font-semibold px-1.5 py-0.5 rounded-full">
                                        <?= $counts[$key]['total'] ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- Lista de notificaciones -->
                        <?php if (empty($notificaciones)): ?>
                            <div class="flex flex-col items-center justify-center py-20 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-700 mb-1">Sin notificaciones</h3>
                                <p class="text-sm text-gray-400">
                                    <?= $filtro !== 'todas' ? 'No tenés notificaciones de este tipo.' : 'Cuando haya novedades, las verás acá.' ?>
                                </p>
                            </div>

                        <?php else: ?>
                            <div class="space-y-3">
                            <?php foreach ($notificaciones as $noti):
                                $leida = (bool) $noti['leida'];

                                switch ($noti['tipo']) {
                                    case 'postulacion':
                                        $esAceptada  = stripos($noti['titulo'], 'aceptada') !== false;
                                        $esRechazada = stripos($noti['titulo'], 'rechazada') !== false;
                                        if ($esAceptada) {
                                            $icono_bg    = 'bg-green-100';
                                            $icono_color = 'text-green-600';
                                            $borde       = $leida ? '' : 'border-l-4 border-green-400';
                                            $icono_svg   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
                                        } elseif ($esRechazada) {
                                            $icono_bg    = 'bg-red-100';
                                            $icono_color = 'text-red-500';
                                            $borde       = $leida ? '' : 'border-l-4 border-red-400';
                                            $icono_svg   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
                                        } else {
                                            $icono_bg    = 'bg-blue-100';
                                            $icono_color = 'text-blue-600';
                                            $borde       = $leida ? '' : 'border-l-4 border-blue-400';
                                            $icono_svg   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>';
                                        }
                                        $tipo_badge = 'bg-blue-50 text-blue-700';
                                        $tipo_label = 'Postulación';
                                        break;

                                    case 'oferta':
                                        $icono_bg    = 'bg-blue-100';
                                        $icono_color = 'text-blue-600';
                                        $borde       = $leida ? '' : 'border-l-4 border-blue-400';
                                        $icono_svg   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>';
                                        $tipo_badge  = 'bg-blue-50 text-blue-700';
                                        $tipo_label  = 'Oferta';
                                        break;

                                    default: // sistema
                                        $icono_bg    = 'bg-amber-100';
                                        $icono_color = 'text-amber-600';
                                        $borde       = $leida ? '' : 'border-l-4 border-amber-400';
                                        $icono_svg   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
                                        $tipo_badge  = 'bg-amber-50 text-amber-700';
                                        $tipo_label  = 'Sistema';
                                        break;
                                }

                                $bg_card = $leida ? 'bg-gray-50' : 'bg-blue-50/30';
                            ?>

                            <div class="<?= $bg_card ?> <?= $borde ?> rounded-2xl border border-gray-200 p-4 flex gap-4 hover:shadow-sm transition group">

                                <!-- Ícono -->
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 <?= $icono_bg ?> rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 <?= $icono_color ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <?= $icono_svg ?>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Contenido -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $tipo_badge ?>">
                                                    <?= $tipo_label ?>
                                                </span>
                                                <?php if (!$leida): ?>
                                                    <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse" title="No leída"></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm font-semibold text-gray-900">
                                                <?= htmlspecialchars($noti['titulo']) ?>
                                            </p>
                                            <p class="text-sm text-gray-500 mt-0.5 leading-relaxed">
                                                <?= htmlspecialchars($noti['mensaje']) ?>
                                            </p>
                                        </div>

                                        <!-- Tiempo + acción -->
                                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                                <?= tiempoRelativo($noti['fecha_creacion']) ?>
                                            </span>
                                            <?php if (!$leida): ?>
                                            <form method="POST">
                                                <input type="hidden" name="marcar_leida" value="1">
                                                <input type="hidden" name="id_notificacion" value="<?= $noti['id_notificacion'] ?>">
                                                <button type="submit" title="Marcar como leída"
                                                    class="text-gray-300 hover:text-blue-500 transition opacity-0 group-hover:opacity-100">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Botón de acción si tiene URL -->
                                    <?php if (!empty($noti['url_accion'])): ?>
                                    <div class="mt-2">
                                        <a href="<?= htmlspecialchars($noti['url_accion']) ?>"
                                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition">
                                            Ver detalle
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- FOOTER — unificado igual al index -->
<footer class="bg-white border-t border-gray-200 text-gray-600 py-8 px-3 mt-8">
    <div class="container mx-auto flex flex-wrap items-center justify-between">
        <div class="w-full md:w-1/2 text-center md:text-left mb-4 md:mb-0">
            <p class="text-sm font-medium">Copyright 2026 &copy; YoConstructor</p>
        </div>
        <div class="w-full md:w-1/2">
            <ul class="flex justify-center md:justify-end gap-6 text-sm font-semibold">
                <li><a href="contacto.php" class="hover:text-blue-600">Contacto</a></li>
                <li><a href="#" class="hover:text-blue-600">Privacidad</a></li>
                <li><a href="#" class="hover:text-blue-600">Términos</a></li>
            </ul>
        </div>
    </div>
</footer>

</body>
</html>