<?php
$page      = 'dashboard';
$pageTitle = 'Dashboard';
date_default_timezone_set('America/Argentina/Buenos_Aires');
include("conexion.php");

$id_empresa = $_SESSION['idempresa'] ?? null;
if (!$id_empresa) { header("Location: login.php"); exit; }

// ── MÉTRICAS PRINCIPALES ──────────────────────────────────────────────────

// Ofertas
$r = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT
    COUNT(*) AS total,
    SUM(estado = 'Activa')   AS activas,
    SUM(estado = 'Pausada')  AS pausadas,
    SUM(estado = 'Borrador') AS borradores
    FROM ofertas_laborales WHERE id_empresa = $id_empresa"));
$ofertas_total     = intval($r['total']);
$ofertas_activas   = intval($r['activas']);
$ofertas_pausadas  = intval($r['pausadas']);
$ofertas_borradores = intval($r['borradores']);

// Postulantes totales
$r2 = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT
    COUNT(*) AS total,
    SUM(p.estado = 'Pendiente')  AS pendientes,
    SUM(p.estado = 'Revisada')   AS revisadas,
    SUM(p.estado = 'Entrevista') AS entrevistas,
    SUM(p.estado = 'Aceptada')   AS aceptadas,
    SUM(p.estado = 'Rechazada')  AS rechazadas
    FROM postulaciones p
    INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
    WHERE o.id_empresa = $id_empresa"));
$post_total      = intval($r2['total']);
$post_pendientes = intval($r2['pendientes']);
$post_entrevistas = intval($r2['entrevistas']);
$post_aceptadas  = intval($r2['aceptadas']);
$post_rechazadas = intval($r2['rechazadas']);

// Nuevos hoy
$r3 = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS hoy
    FROM postulaciones p
    INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
    WHERE o.id_empresa = $id_empresa AND DATE(p.fecha_postulacion) = CURDATE()"));
$post_hoy = intval($r3['hoy']);

// Nuevos esta semana
$r4 = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS semana
    FROM postulaciones p
    INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
    WHERE o.id_empresa = $id_empresa AND p.fecha_postulacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)"));
$post_semana = intval($r4['semana']);

// ── ÚLTIMAS 5 POSTULACIONES ───────────────────────────────────────────────
$sql_recientes = "SELECT
    p.id_postulacion, p.estado, p.fecha_postulacion,
    per.nombre, per.apellido, per.imagen_perfil,
    o.id_oferta, o.titulo AS oferta_titulo,
    e.nombre_especialidad
    FROM postulaciones p
    INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
    INNER JOIN persona per ON p.id_persona = per.id_persona
    LEFT JOIN persona_especialidades pe ON pe.id_persona = per.id_persona
    LEFT JOIN especialidades e ON pe.id_especialidad = e.id_especialidad
    WHERE o.id_empresa = $id_empresa
    GROUP BY p.id_postulacion
    ORDER BY p.fecha_postulacion DESC
    LIMIT 5";
$res_rec = mysqli_query($conexion, $sql_recientes);
$recientes = [];
while ($r = mysqli_fetch_assoc($res_rec)) $recientes[] = $r;

// ── TOP 5 OFERTAS CON MÁS POSTULANTES ────────────────────────────────────
$sql_top = "SELECT o.id_oferta, o.titulo, o.estado,
    COUNT(p.id_postulacion) AS total_post,
    SUM(p.estado = 'Pendiente') AS pendientes
    FROM ofertas_laborales o
    LEFT JOIN postulaciones p ON p.id_oferta = o.id_oferta
    WHERE o.id_empresa = $id_empresa AND o.estado != 'Borrador'
    GROUP BY o.id_oferta
    ORDER BY total_post DESC
    LIMIT 5";
$res_top = mysqli_query($conexion, $sql_top);
$top_ofertas = [];
while ($r = mysqli_fetch_assoc($res_top)) $top_ofertas[] = $r;

// ── POSTULACIONES POR DÍA (últimos 7 días) ────────────────────────────────
$sql_chart = "SELECT DATE(p.fecha_postulacion) AS dia, COUNT(*) AS total
    FROM postulaciones p
    INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
    WHERE o.id_empresa = $id_empresa AND p.fecha_postulacion >= DATE_SUB(NOW(), INTERVAL 6 DAY)
    GROUP BY DATE(p.fecha_postulacion)
    ORDER BY dia ASC";
$res_chart = mysqli_query($conexion, $sql_chart);
$chart_raw = [];
while ($r = mysqli_fetch_assoc($res_chart)) $chart_raw[$r['dia']] = intval($r['total']);

// Rellenar días sin postulaciones
$chart_labels = [];
$chart_data   = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($fecha));
    $chart_data[]   = $chart_raw[$fecha] ?? 0;
}

// ── OFERTAS POR VENCER (próximos 7 días) ──────────────────────────────────
$sql_vencer = "SELECT id_oferta, titulo, fecha_vencimiento,
    DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes
    FROM ofertas_laborales
    WHERE id_empresa = $id_empresa AND estado = 'Activa'
    AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY fecha_vencimiento ASC";
$res_vencer = mysqli_query($conexion, $sql_vencer);
$por_vencer = [];
while ($r = mysqli_fetch_assoc($res_vencer)) $por_vencer[] = $r;

function tr_dash($fecha) {
    $d = time() - strtotime($fecha);
    if ($d < 3600)   return 'Hace ' . floor($d/60) . ' min';
    if ($d < 86400)  return 'Hace ' . floor($d/3600) . ' h';
    if ($d < 172800) return 'Ayer';
    if ($d < 604800) return 'Hace ' . floor($d/86400) . ' días';
    return date('d/m/Y', strtotime($fecha));
}

$estado_cfg = [
    'Pendiente'  => ['bg'=>'bg-yellow-100','text'=>'text-yellow-800','dot'=>'bg-yellow-400','label'=>'Pendiente'],
    'Revisada'   => ['bg'=>'bg-blue-100',  'text'=>'text-blue-800',  'dot'=>'bg-blue-400',  'label'=>'Revisada'],
    'Entrevista' => ['bg'=>'bg-purple-100','text'=>'text-purple-800','dot'=>'bg-purple-500','label'=>'Entrevista'],
    'Aceptada'   => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'dot'=>'bg-green-500', 'label'=>'Aceptada'],
    'Rechazada'  => ['bg'=>'bg-red-100',   'text'=>'text-red-800',   'dot'=>'bg-red-400',   'label'=>'Rechazada'],
];

include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-10">

    <!-- Encabezado -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-500 mt-1 text-sm">Resumen de actividad de tu empresa</p>
    </div>

    <!-- ── MÉTRICAS TOP ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        <!-- Postulantes nuevos hoy -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <?php if ($post_hoy > 0): ?>
                <span class="text-xs font-semibold bg-cyan-50 text-cyan-600 px-2 py-0.5 rounded-full">Hoy</span>
                <?php endif; ?>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= $post_hoy ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Postulante<?= $post_hoy !== 1 ? 's' : '' ?> hoy</p>
            <p class="text-xs text-gray-400 mt-1"><?= $post_semana ?> esta semana</p>
        </div>

        <!-- Total postulantes -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <?php if ($post_pendientes > 0): ?>
                <span class="text-xs font-semibold bg-yellow-50 text-yellow-600 px-2 py-0.5 rounded-full"><?= $post_pendientes ?> sin revisar</span>
                <?php endif; ?>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= $post_total ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Total postulantes</p>
            <p class="text-xs text-gray-400 mt-1"><?= $post_aceptadas ?> aceptado<?= $post_aceptadas !== 1 ? 's' : '' ?></p>
        </div>

        <!-- Ofertas activas -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <a href="ofertas-publicadas.php" class="text-xs text-gray-400 hover:text-cyan-600 transition">Ver todas →</a>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= $ofertas_activas ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Oferta<?= $ofertas_activas !== 1 ? 's' : '' ?> activa<?= $ofertas_activas !== 1 ? 's' : '' ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= $ofertas_pausadas ?> pausada<?= $ofertas_pausadas !== 1 ? 's' : '' ?> · <?= $ofertas_borradores ?> borrador<?= $ofertas_borradores !== 1 ? 'es' : '' ?></p>
        </div>

        <!-- En entrevista -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= $post_entrevistas ?></p>
            <p class="text-sm text-gray-500 mt-0.5">En entrevista</p>
            <p class="text-xs text-gray-400 mt-1"><?= $post_rechazadas ?> rechazado<?= $post_rechazadas !== 1 ? 's' : '' ?></p>
        </div>

    </div>

    <!-- ── FILA CENTRAL: Gráfico + Top ofertas ── -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-6">

        <!-- Gráfico postulaciones últimos 7 días -->
        <div class="lg:col-span-3 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Postulaciones</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Últimos 7 días</p>
                </div>
                <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-medium"><?= array_sum($chart_data) ?> total</span>
            </div>
            <div class="relative h-40">
                <canvas id="chartPostulaciones"></canvas>
            </div>
        </div>

        <!-- Top ofertas -->
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-bold text-gray-800">Top ofertas</h2>
                <a href="ofertas-publicadas.php" class="text-xs text-cyan-600 hover:underline font-medium">Ver todas</a>
            </div>
            <?php if (empty($top_ofertas)): ?>
            <p class="text-sm text-gray-400 text-center py-8">Sin datos aún</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php
                $max_post = max(array_column($top_ofertas, 'total_post')) ?: 1;
                foreach ($top_ofertas as $i => $to):
                    $pct = round(($to['total_post'] / $max_post) * 100);
                    $estado_color = $to['estado'] === 'Activa' ? 'text-green-600' : 'text-yellow-600';
                ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xs font-bold text-gray-400 w-4 flex-shrink-0"><?= $i+1 ?></span>
                            <a href="postulantes.php?id=<?= $to['id_oferta'] ?>" class="text-xs font-medium text-gray-700 hover:text-cyan-600 truncate transition">
                                <?= htmlspecialchars($to['titulo']) ?>
                            </a>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                            <?php if ($to['pendientes'] > 0): ?>
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded font-medium"><?= $to['pendientes'] ?> sin ver</span>
                            <?php endif; ?>
                            <span class="text-xs font-bold text-gray-600"><?= $to['total_post'] ?></span>
                        </div>
                    </div>
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-cyan-500 rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── FILA INFERIOR: Últimas postulaciones + Ofertas por vencer ── -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Últimas postulaciones -->
        <div class="lg:col-span-3 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800">Últimas postulaciones</h2>
                <?php if ($post_pendientes > 0): ?>
                <span class="text-xs bg-yellow-100 text-yellow-700 font-semibold px-2.5 py-1 rounded-full"><?= $post_pendientes ?> pendiente<?= $post_pendientes !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
            <?php if (empty($recientes)): ?>
            <div class="px-6 py-12 text-center">
                <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm text-gray-400">Aún no hay postulaciones</p>
            </div>
            <?php else: ?>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($recientes as $rec):
                    $cfg    = $estado_cfg[$rec['estado']] ?? $estado_cfg['Pendiente'];
                    $foto   = !empty($rec['imagen_perfil']) ? 'uploads/perfil/' . $rec['imagen_perfil'] : './img/profile.png';
                    $nombre = htmlspecialchars(ucwords(strtolower($rec['nombre'] . ' ' . $rec['apellido'])));
                ?>
                <li class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3">
                        <img src="<?= $foto ?>" class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 flex-shrink-0" alt="foto">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-800 truncate"><?= $nombre ?></p>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium <?= $cfg['bg'] ?> <?= $cfg['text'] ?> flex-shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $cfg['dot'] ?>"></span>
                                    <?= $cfg['label'] ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-0.5">
                                <a href="postulantes.php?id=<?= $rec['id_oferta'] ?>" class="text-xs text-cyan-600 hover:underline truncate">
                                    <?= htmlspecialchars($rec['oferta_titulo']) ?>
                                </a>
                                <span class="text-xs text-gray-400 flex-shrink-0 ml-2"><?= tr_dash($rec['fecha_postulacion']) ?></span>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Ofertas por vencer + pipeline -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Pipeline de estados -->
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-800 mb-4">Pipeline de postulantes</h2>
                <?php
                $pipeline = [
                    ['label'=>'Pendientes',  'val'=>$post_pendientes,  'bg'=>'bg-yellow-400', 'text'=>'text-yellow-700', 'light'=>'bg-yellow-50'],
                    ['label'=>'Revisados',   'val'=>intval($r2['revisadas']), 'bg'=>'bg-blue-400',   'text'=>'text-blue-700',   'light'=>'bg-blue-50'],
                    ['label'=>'Entrevistas', 'val'=>$post_entrevistas, 'bg'=>'bg-purple-500', 'text'=>'text-purple-700', 'light'=>'bg-purple-50'],
                    ['label'=>'Aceptados',   'val'=>$post_aceptadas,   'bg'=>'bg-green-500',  'text'=>'text-green-700',  'light'=>'bg-green-50'],
                    ['label'=>'Rechazados',  'val'=>$post_rechazadas,  'bg'=>'bg-red-400',    'text'=>'text-red-700',    'light'=>'bg-red-50'],
                ];
                $max_pipeline = max(array_column($pipeline, 'val')) ?: 1;
                foreach ($pipeline as $pl):
                    $pct = round(($pl['val'] / $max_pipeline) * 100);
                ?>
                <div class="flex items-center gap-3 mb-2.5">
                    <span class="text-xs text-gray-500 w-20 flex-shrink-0"><?= $pl['label'] ?></span>
                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full <?= $pl['bg'] ?> rounded-full" style="width: <?= $pct ?>%"></div>
                    </div>
                    <span class="text-xs font-bold <?= $pl['text'] ?> w-6 text-right"><?= $pl['val'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Ofertas por vencer -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-800">Por vencer</h2>
                    <span class="text-xs text-gray-400">próximos 7 días</span>
                </div>
                <?php if (empty($por_vencer)): ?>
                <div class="px-6 py-8 text-center">
                    <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-gray-400">Sin ofertas próximas a vencer</p>
                </div>
                <?php else: ?>
                <ul class="divide-y divide-gray-100">
                    <?php foreach ($por_vencer as $pv):
                        $urgente = $pv['dias_restantes'] <= 2;
                    ?>
                    <li class="px-6 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                        <div class="min-w-0">
                            <a href="postulantes.php?id=<?= $pv['id_oferta'] ?>" class="text-sm font-medium text-gray-700 hover:text-cyan-600 truncate block transition">
                                <?= htmlspecialchars($pv['titulo']) ?>
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5"><?= date('d/m/Y', strtotime($pv['fecha_vencimiento'])) ?></p>
                        </div>
                        <span class="flex-shrink-0 text-xs font-bold px-2.5 py-1 rounded-full <?= $urgente ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600' ?>">
                            <?= $pv['dias_restantes'] === '0' ? 'Hoy' : $pv['dias_restantes'] . 'd' ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>

<!-- MODAL CERRAR SESIÓN -->
<div id="modalCerrarSesion" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
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
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Sí, cerrar sesión
            </a>
        </div>
    </div>
</div>




<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
   
 function abrirModalSesion() {
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
    if (e.key === 'Escape') cerrarModalSesion();
});  

const ctx = document.getElementById('chartPostulaciones').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Postulaciones',
            data:  <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(6,182,212,0.15)',
            borderColor:     'rgba(6,182,212,1)',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y + ' postulaci' + (ctx.parsed.y === 1 ? 'ón' : 'ones')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, font: { size: 11 } },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                ticks: { font: { size: 11 } },
                grid: { display: false }
            }
        }
    }
});
</script>

<?php
if (isset($_SESSION)) {
    echo "<script>console.group('🔐 Variables de Sesión');</script>";
    foreach ($_SESSION as $key => $value) {
        $val = is_array($value) || is_object($value) ? json_encode($value) : "'" . addslashes($value ?? '') . "'";
        echo "<script>console.log('{$key}:', {$val});</script>";
    }
    echo "<script>console.groupEnd();</script>";
}
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $m = addslashes($error['message'] ?? '');
        $f = addslashes(basename($error['file'] ?? ''));
        echo "<script>console.error('💥 Error Fatal: {$m} — {$f} línea {$error['line']}');</script>";
    }
});
?>