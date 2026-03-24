<?php
$page      = 'auditoria';
$pageTitle = 'Auditoria';
include("conexion.php");

$id_empresa = $_SESSION['idempresa'] ?? null;
if (!$id_empresa) {
    header("Location: login.php");
    exit;
}

// --- FILTROS ---
$filtro_accion  = $_GET['accion']     ?? '';
$filtro_desde   = $_GET['desde']      ?? '';
$filtro_hasta   = $_GET['hasta']      ?? '';
$pagina         = max(1, intval($_GET['p'] ?? 1));
$por_pagina     = 20;
$offset         = ($pagina - 1) * $por_pagina;

// Acciones disponibles
$acciones_map = [
    'publicar_oferta'   => ['label' => 'Publicó oferta',       'color' => 'bg-green-100 text-green-700 border-green-200',  'icon' => 'M12 4v16m8-8H4'],
    'editar_oferta'     => ['label' => 'Editó oferta',         'color' => 'bg-blue-100 text-blue-700 border-blue-200',     'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
    'eliminar_oferta'   => ['label' => 'Eliminó oferta',       'color' => 'bg-red-100 text-red-700 border-red-200',        'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
    'aceptar_postulante'=> ['label' => 'Aceptó postulante',    'color' => 'bg-purple-100 text-purple-700 border-purple-200','icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    'rechazar_postulante'     => ['label' => 'Rechazó postulante',      'color' => 'bg-red-100 text-red-700 border-red-200',        'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
    'preseleccionar_postulante'=> ['label' => 'Preseleccionó postulante','color' => 'bg-purple-100 text-purple-700 border-purple-200', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
];

// --- WHERE dinámico ---
$where = ["a.id_empresa = $id_empresa"];

if ($filtro_accion && array_key_exists($filtro_accion, $acciones_map)) {
    $fa      = mysqli_real_escape_string($conexion, $filtro_accion);
    $where[] = "a.accion = '$fa'";
}
if ($filtro_desde) {
    $fd      = mysqli_real_escape_string($conexion, $filtro_desde);
    $where[] = "DATE(a.fecha) >= '$fd'";
}
if ($filtro_hasta) {
    $fh      = mysqli_real_escape_string($conexion, $filtro_hasta);
    $where[] = "DATE(a.fecha) <= '$fh'";
}

$where_sql = implode(' AND ', $where);

// --- TOTAL para paginación ---
$res_total = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM auditoria a WHERE $where_sql");
$total_rows = intval(mysqli_fetch_assoc($res_total)['total']);
$total_pags = max(1, ceil($total_rows / $por_pagina));
$pagina     = min($pagina, $total_pags);
$offset     = ($pagina - 1) * $por_pagina;

// --- QUERY principal ---
$sql = "SELECT a.id_auditoria, a.accion, a.entidad, a.id_entidad, a.detalle, a.fecha,
               u.usuario, u.tipo AS tipo_usuario,
               per.nombre AS nombre_persona, per.apellido AS apellido_persona
        FROM auditoria a
        INNER JOIN users u ON a.id_usuario = u.id_usuario
        LEFT JOIN persona per ON u.id_persona = per.id_persona
        WHERE $where_sql
        ORDER BY a.fecha DESC
        LIMIT $por_pagina OFFSET $offset";

$res     = mysqli_query($conexion, $sql);
$registros = [];
while ($r = mysqli_fetch_assoc($res)) $registros[] = $r;

// --- Helper: nombre del actor ---
function nombre_actor($row) {
    if (!empty($row['nombre_persona'])) {
        return ucwords(strtolower($row['nombre_persona'] . ' ' . $row['apellido_persona']));
    }
    return $row['usuario'] ?? 'Yo';
}

// --- Helper: query string para paginación manteniendo filtros ---
function qs($extra = []) {
    $params = [];
    foreach (['accion', 'desde', 'hasta'] as $k) {
        if (!empty($_GET[$k])) $params[$k] = $_GET[$k];
    }
    return http_build_query(array_merge($params, $extra));
}

include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-10">

    <!-- Encabezado -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Auditoria</h1>
        <p class="text-gray-500 mt-1 text-sm">Registro de todas las acciones realizadas en tu empresa</p>
    </div>

    <!-- Filtros -->
    <form method="GET" action="" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

            <!-- Tipo de acción -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Tipo de acción</label>
                <select name="accion"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                    <option value="">Todas las acciones</option>
                    <?php foreach ($acciones_map as $val => $cfg): ?>
                        <option value="<?= $val ?>" <?= $filtro_accion === $val ? 'selected' : '' ?>>
                            <?= $cfg['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Desde -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Desde</label>
                <input type="date" name="desde" value="<?= htmlspecialchars($filtro_desde) ?>"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
            </div>

            <!-- Hasta -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Hasta</label>
                <input type="date" name="hasta" value="<?= htmlspecialchars($filtro_hasta) ?>"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
            </div>

        </div>
        <div class="flex gap-3 mt-4">
            <button type="submit"
                class="bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                Filtrar
            </button>
            <a href="auditoria-empresa.php"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-5 py-2 rounded-lg transition">
                Limpiar
            </a>
        </div>
    </form>

    <!-- Resumen -->
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            <?= number_format($total_rows) ?> registro<?= $total_rows !== 1 ? 's' : '' ?> encontrado<?= $total_rows !== 1 ? 's' : '' ?>
            <?php if ($total_pags > 1): ?>
                · Página <?= $pagina ?> de <?= $total_pags ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Tabla -->
    <?php if (empty($registros)): ?>
        <div class="bg-white border border-dashed border-gray-300 rounded-xl p-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500 font-medium">No hay registros que coincidan con los filtros.</p>
        </div>

    <?php else: ?>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Acción</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Detalle</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Usuario</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Fecha y hora</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($registros as $r):
                            $cfg    = $acciones_map[$r['accion']] ?? ['label' => $r['accion'], 'color' => 'bg-gray-100 text-gray-600 border-gray-200', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
                            $actor  = nombre_actor($r);
                            $es_empresa = $r['tipo_usuario'] == 3;
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">

                                <!-- Acción badge -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border <?= $cfg['color'] ?>">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $cfg['icon'] ?>"/>
                                        </svg>
                                        <?= $cfg['label'] ?>
                                    </span>
                                </td>

                                <!-- Detalle -->
                                <td class="px-5 py-4 text-gray-700 max-w-xs">
                                    <span class="line-clamp-2"><?= htmlspecialchars($r['detalle'] ?: '—') ?></span>
                                </td>

                                <!-- Usuario -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                                            <?= $es_empresa ? 'bg-indigo-100 text-indigo-600' : 'bg-cyan-100 text-cyan-600' ?>">
                                            <?= strtoupper(substr($actor, 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($actor) ?></p>
                                            <p class="text-xs text-gray-400"><?= $es_empresa ? 'Empresa' : 'Reclutador' ?></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Fecha -->
                                <td class="px-5 py-4 whitespace-nowrap text-gray-500 text-xs">
                                    <p class="font-medium text-gray-700"><?= date('d/m/Y', strtotime($r['fecha'])) ?></p>
                                    <p><?= date('H:i', strtotime($r['fecha'])) ?>hs</p>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_pags > 1): ?>
                <div class="px-5 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-400">
                        Mostrando <?= $offset + 1 ?>–<?= min($offset + $por_pagina, $total_rows) ?> de <?= number_format($total_rows) ?>
                    </p>
                    <div class="flex items-center gap-1">

                        <!-- Anterior -->
                        <?php if ($pagina > 1): ?>
                            <a href="?<?= qs(['p' => $pagina - 1]) ?>"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Anterior
                            </a>
                        <?php endif; ?>

                        <!-- Números de página -->
                        <?php
                        $rango_inicio = max(1, $pagina - 2);
                        $rango_fin    = min($total_pags, $pagina + 2);
                        if ($rango_inicio > 1): ?>
                            <a href="?<?= qs(['p' => 1]) ?>" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">1</a>
                            <?php if ($rango_inicio > 2): ?>
                                <span class="px-2 text-gray-400 text-xs">…</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $rango_inicio; $i <= $rango_fin; $i++): ?>
                            <a href="?<?= qs(['p' => $i]) ?>"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition
                                <?= $i === $pagina
                                    ? 'bg-cyan-600 text-white border-cyan-600'
                                    : 'text-gray-600 bg-white border-gray-300 hover:bg-gray-100' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($rango_fin < $total_pags): ?>
                            <?php if ($rango_fin < $total_pags - 1): ?>
                                <span class="px-2 text-gray-400 text-xs">…</span>
                            <?php endif; ?>
                            <a href="?<?= qs(['p' => $total_pags]) ?>" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition"><?= $total_pags ?></a>
                        <?php endif; ?>

                        <!-- Siguiente -->
                        <?php if ($pagina < $total_pags): ?>
                            <a href="?<?= qs(['p' => $pagina + 1]) ?>"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                                Siguiente
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL CERRAR SESIÓN -->
<div id="modalCerrarSesion" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
            <a href="cerrar-session.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sí, cerrar sesión
            </a>
        </div>
    </div>
</div>

<script>
function abrirModalSesion()  { document.getElementById('modalCerrarSesion').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function cerrarModalSesion() { document.getElementById('modalCerrarSesion').classList.add('hidden');    document.body.style.overflow = 'auto';   }
document.getElementById('modalCerrarSesion').addEventListener('click', function(e) { if (e.target === this) cerrarModalSesion(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalSesion(); });
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