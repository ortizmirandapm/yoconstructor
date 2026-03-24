<?php
$page      = 'admin-dashboard';
$pageTitle = 'Dashboard';
include("conexion.php");
include("sidebar-admin.php");

date_default_timezone_set('America/Argentina/Buenos_Aires');

// ── Métricas ──────────────────────────────────────────────────────────────────
$m = [];
$m['empresas_activas']      = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM empresa WHERE estado='activo'"))['c']   ?? 0;
$m['empresas_inactivas']    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM empresa WHERE estado='inactivo'"))['c'] ?? 0;
$m['trabajadores_activos']  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM users WHERE tipo=2 AND estado='activo'"))['c']   ?? 0;
$m['trabajadores_inactivos']= mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM users WHERE tipo=2 AND estado='inactivo'"))['c'] ?? 0;
$m['ofertas_activas']       = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM ofertas_laborales WHERE estado='Activa'"))['c']   ?? 0;
$m['ofertas_borradores']    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM ofertas_laborales WHERE estado='Borrador'"))['c']  ?? 0;
$m['ofertas_cerradas']      = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM ofertas_laborales WHERE estado='Cerrada'"))['c']   ?? 0;
$m['postulaciones_total']   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM postulaciones"))['c'] ?? 0;
$m['postulaciones_hoy']     = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM postulaciones WHERE DATE(fecha_postulacion)=CURDATE()"))['c'] ?? 0;
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS reportes (id_reporte INT AUTO_INCREMENT PRIMARY KEY, tipo ENUM('empresa','trabajador','oferta') NOT NULL, id_referencia INT NOT NULL, motivo VARCHAR(100) NOT NULL, descripcion TEXT, id_usuario_reporta INT, estado ENUM('pendiente','revisado','resuelto','descartado') DEFAULT 'pendiente', accion_tomada TEXT, fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP, fecha_revision DATETIME)");
$m['reportes_pendientes']   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM reportes WHERE estado='pendiente'"))['c'] ?? 0;

// Empresas recientes
$res_emps = mysqli_query($conexion,
    "SELECT e.nombre_empresa, e.logo, e.fecha_ingreso, u.email, e.estado
     FROM empresa e INNER JOIN users u ON u.id_empresa=e.id_empresa AND u.tipo=3
     ORDER BY e.fecha_ingreso DESC LIMIT 5");

// Ofertas recientes
$res_ofs = mysqli_query($conexion,
    "SELECT o.titulo, o.estado, o.fecha_publicacion, e.nombre_empresa
     FROM ofertas_laborales o INNER JOIN empresa e ON o.id_empresa=e.id_empresa
     ORDER BY o.fecha_publicacion DESC LIMIT 5");

// Grafico postulaciones 7 dias
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $chart_data[$fecha] = ['label' => date('d/m', strtotime($fecha)), 'val' => 0];
}
$res_chart = mysqli_query($conexion,
    "SELECT DATE(CONVERT_TZ(fecha_postulacion,'+00:00','-03:00')) dia, COUNT(*) total
     FROM postulaciones
     WHERE fecha_postulacion >= DATE_SUB(NOW() - INTERVAL 3 HOUR, INTERVAL 6 DAY)
     GROUP BY DATE(CONVERT_TZ(fecha_postulacion,'+00:00','-03:00'))");
while ($r = mysqli_fetch_assoc($res_chart)) {
    if (isset($chart_data[$r['dia']])) $chart_data[$r['dia']]['val'] = (int)$r['total'];
}
$chart_labels = array_column(array_values($chart_data), 'label');
$chart_values = array_column(array_values($chart_data), 'val');
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-8">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-500 text-sm mt-0.5">Vista general de la plataforma &middot; <?= date('d/m/Y') ?></p>
    </div>

    <!-- Metricas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <a href="admin-empresas.php" class="text-xs text-indigo-600 hover:underline font-medium">Ver todas</a>
            </div>
            <p class="text-3xl font-bold text-gray-800"><?= $m['empresas_activas'] ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Empresas activas</p>
            <p class="text-xs text-gray-400 mt-1"><?= $m['empresas_inactivas'] ?> de baja</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <a href="admin-trabajadores.php" class="text-xs text-indigo-600 hover:underline font-medium">Ver todos</a>
            </div>
            <p class="text-3xl font-bold text-gray-800"><?= $m['trabajadores_activos'] ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Trabajadores activos</p>
            <p class="text-xs text-gray-400 mt-1"><?= $m['trabajadores_inactivos'] ?> de baja</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <a href="admin-ofertas.php" class="text-xs text-indigo-600 hover:underline font-medium">Ver todas</a>
            </div>
            <p class="text-3xl font-bold text-gray-800"><?= $m['ofertas_activas'] ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Ofertas activas</p>
            <p class="text-xs text-gray-400 mt-1"><?= $m['ofertas_borradores'] ?> borradores &middot; <?= $m['ofertas_cerradas'] ?> cerradas</p>
        </div>

     

    </div>

    <!-- Grafico + resumen -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <p class="text-sm font-semibold text-gray-800 mb-1">Postulaciones &mdash; ultimos 7 dias</p>
            <p class="text-xs text-gray-400 mb-5">Actividad diaria de la plataforma</p>
            <canvas id="chartPost" height="110"></canvas>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <p class="text-sm font-semibold text-gray-800 mb-4">Accesos rapidos</p>
            <div class="space-y-2">
                <a href="admin-empresas.php?estado=inactivo" class="flex items-center justify-between px-3 py-2.5 bg-red-50 hover:bg-red-100 border border-red-100 rounded-xl transition">
                    <span class="text-xs font-medium text-red-700">Empresas de baja</span>
                    <span class="text-xs font-bold text-red-600"><?= $m['empresas_inactivas'] ?></span>
                </a>
                <a href="admin-trabajadores.php?estado=inactivo" class="flex items-center justify-between px-3 py-2.5 bg-orange-50 hover:bg-orange-100 border border-orange-100 rounded-xl transition">
                    <span class="text-xs font-medium text-orange-700">Trabajadores de baja</span>
                    <span class="text-xs font-bold text-orange-600"><?= $m['trabajadores_inactivos'] ?></span>
                </a>
             
                <a href="admin-ofertas.php?estado=Cerrada" class="flex items-center justify-between px-3 py-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition">
                    <span class="text-xs font-medium text-gray-600">Ofertas cerradas</span>
                    <span class="text-xs font-bold text-gray-500"><?= $m['ofertas_cerradas'] ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Tablas recientes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-800">Empresas recientes</p>
                <a href="admin-empresas.php" class="text-xs text-indigo-600 hover:underline font-medium">Ver todas</a>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (!$res_emps || mysqli_num_rows($res_emps) === 0): ?>
                <p class="px-6 py-8 text-center text-sm text-gray-400">Sin datos</p>
                <?php else: while ($e = mysqli_fetch_assoc($res_emps)): ?>
                <div class="flex items-center gap-3 px-6 py-3.5 hover:bg-gray-50 transition">
                    <?php if ($e['logo']): ?>
                    <img src="<?= htmlspecialchars($e['logo']) ?>" class="w-8 h-8 rounded-lg object-contain border border-gray-200 flex-shrink-0" alt="">
                    <?php else: ?>
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-xs font-bold flex-shrink-0"><?= strtoupper(substr($e['nombre_empresa'],0,1)) ?></div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($e['nombre_empresa']) ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($e['email']) ?></p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full border font-medium flex-shrink-0 <?= $e['estado']==='activo' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
                        <?= ucfirst($e['estado']) ?>
                    </span>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-800">Ofertas recientes</p>
                <a href="admin-ofertas.php" class="text-xs text-indigo-600 hover:underline font-medium">Ver todas</a>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (!$res_ofs || mysqli_num_rows($res_ofs) === 0): ?>
                <p class="px-6 py-8 text-center text-sm text-gray-400">Sin datos</p>
                <?php else: while ($o = mysqli_fetch_assoc($res_ofs)): ?>
                <?php $ob = match($o['estado']) {
                    'Activa'  => 'bg-green-50 text-green-700 border-green-200',
                    'Borrador'=> 'bg-amber-50 text-amber-700 border-amber-200',
                    'Cerrada' => 'bg-gray-100 text-gray-500 border-gray-200',
                    default   => 'bg-gray-100 text-gray-500 border-gray-200'
                }; ?>
                <div class="flex items-center gap-3 px-6 py-3.5 hover:bg-gray-50 transition">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($o['titulo']) ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($o['nombre_empresa']) ?></p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full border font-medium flex-shrink-0 <?= $ob ?>"><?= $o['estado'] ?></span>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>

    </div>
</div>

        </main>
    </div>

<!-- MODAL CERRAR SESION -->
<div id="modalCerrarSesion" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Cerrar sesion</h3>
                <p class="text-xs text-gray-400 mt-0.5">Esta accion cerrara tu cuenta</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-600">Estas seguro que queres cerrar sesion?</p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
            <button type="button" onclick="cerrarModalSesion()"
                class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">
                Cancelar
            </button>
            <a href="cerrar-session.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Si, cerrar sesion
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartPost').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{ label: 'Postulaciones', data: <?= json_encode($chart_values) ?>,
            backgroundColor: 'rgba(99,102,241,0.12)', borderColor: 'rgba(99,102,241,0.7)',
            borderWidth: 2, borderRadius: 6, borderSkipped: false }]
    },
    options: {
        responsive: true, plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision:0, color:'#9ca3af', font:{size:11} }, grid: { color:'#f3f4f6' } },
            x: { ticks: { color:'#9ca3af', font:{size:11} }, grid: { display:false } }
        }
    }
});

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
</script>