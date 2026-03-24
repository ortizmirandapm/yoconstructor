<?php
$page = 'ofertas-borradores';
$pageTitle = 'Ofertas en borrador';
include("conexion.php");

$id_empresa = $_SESSION['idempresa'] ?? null;
$id_usuario = $_SESSION['idusuario'] ?? null;

// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_empresa) {
    $accion    = $_POST['accion'] ?? '';
    $id_oferta = intval($_POST['id_oferta'] ?? 0);
    $toast     = '';

    if ($id_oferta > 0) {
        if ($accion === 'reactivar') {
            mysqli_query($conexion, "UPDATE ofertas_laborales SET estado = 'Activa' WHERE id_oferta = $id_oferta AND id_empresa = $id_empresa AND estado = 'Borrador'");
            if (mysqli_affected_rows($conexion) > 0) $toast = 'ok_reactivada';
        } elseif ($accion === 'eliminar_definitivo') {
            mysqli_query($conexion, "DELETE FROM ofertas_laborales WHERE id_oferta = $id_oferta AND id_empresa = $id_empresa AND estado = 'Borrador'");
            if (mysqli_affected_rows($conexion) > 0) $toast = 'ok_eliminada';
        }
    }

    $qs_params = $_GET;
    if ($toast) $qs_params['toast'] = $toast;
    $qs = http_build_query($qs_params);
    header('Location: ofertas-borradores.php' . ($qs ? '?' . $qs : ''));
    exit;
}

include("sidebar-empresa.php");

// --- FILTROS GET ---
$search           = trim($_GET['q'] ?? '');
$filtro_esp       = intval($_GET['especialidad'] ?? 0);
$filtro_provincia = intval($_GET['provincia'] ?? 0);
$filtro_orden     = $_GET['orden'] ?? 'desc';
$toast_msg        = $_GET['toast'] ?? '';

// --- DATOS PARA SELECTS ---
$especialidades = [];
$res_esp = mysqli_query($conexion, "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
while ($r = mysqli_fetch_assoc($res_esp)) $especialidades[] = $r;

$provincias = [];
$res_prov = mysqli_query($conexion, "SELECT id_provincia, nombre FROM provincias ORDER BY nombre ASC");
while ($r = mysqli_fetch_assoc($res_prov)) $provincias[] = $r;

// --- QUERY PRINCIPAL ---
$where = ["o.id_empresa = $id_empresa", "o.estado = 'Borrador'"];

if ($search !== '') {
    $s       = mysqli_real_escape_string($conexion, $search);
    $where[] = "(o.titulo LIKE '%$s%' OR o.descripcion LIKE '%$s%')";
}
if ($filtro_esp > 0)       $where[] = "o.id_especialidad = $filtro_esp";
if ($filtro_provincia > 0) $where[] = "o.id_provincia = $filtro_provincia";

$order     = $filtro_orden === 'asc' ? 'ASC' : 'DESC';
$where_sql = implode(' AND ', $where);

$sql = "SELECT o.*, e.nombre_especialidad, p.nombre AS nombre_provincia
        FROM ofertas_laborales o
        LEFT JOIN especialidades e ON o.id_especialidad = e.id_especialidad
        LEFT JOIN provincias p ON o.id_provincia = p.id_provincia
        WHERE $where_sql
        ORDER BY o.fecha_publicacion $order";

$result  = mysqli_query($conexion, $sql);
$ofertas = [];
while ($r = mysqli_fetch_assoc($result)) $ofertas[] = $r;

$ic = "w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition";
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-10">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Ofertas en borrador</h1>
                    <p class="text-gray-500 mt-0.5 text-sm">
                        <?= count($ofertas) ?> oferta<?= count($ofertas) !== 1 ? 's' : '' ?> en borrador
                    </p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="ofertas-publicadas.php"
               class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Ver publicadas
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" action="" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Buscar por título..."
                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
            </div>
            <select name="especialidad" class="<?= $ic ?>">
                <option value="">Todas las especialidades</option>
                <?php foreach ($especialidades as $esp): ?>
                    <option value="<?= $esp['id_especialidad'] ?>" <?= $filtro_esp == $esp['id_especialidad'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($esp['nombre_especialidad']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="provincia" class="<?= $ic ?>">
                <option value="">Todas las provincias</option>
                <?php foreach ($provincias as $prov): ?>
                    <option value="<?= $prov['id_provincia'] ?>" <?= $filtro_provincia == $prov['id_provincia'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prov['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="orden" class="<?= $ic ?>">
                <option value="desc" <?= $filtro_orden !== 'asc' ? 'selected' : '' ?>>Más recientes primero</option>
                <option value="asc"  <?= $filtro_orden === 'asc'  ? 'selected' : '' ?>>Más antiguas primero</option>
            </select>
        </div>
        <div class="flex gap-3 mt-4">
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">Filtrar</button>
            <a href="ofertas-borradores.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-5 py-2 rounded-lg transition">Limpiar</a>
        </div>
    </form>

    <!-- Cards -->
    <?php if (empty($ofertas)): ?>
        <div class="bg-white border border-dashed border-gray-300 rounded-xl p-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <p class="text-gray-500 font-medium">No hay ofertas en borrador.</p>
            <a href="nueva-oferta.php" class="inline-block mt-4 text-cyan-600 hover:underline text-sm font-medium">Crear una nueva oferta →</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($ofertas as $o): ?>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition flex flex-col opacity-90 hover:opacity-100">

                <!-- Header card -->
                <div class="p-5 border-b border-gray-100">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-800 text-sm leading-snug truncate" title="<?= htmlspecialchars($o['titulo']) ?>">
                                <?= htmlspecialchars($o['titulo']) ?>
                            </h3>
                            <p class="text-xs text-cyan-600 font-medium mt-0.5">
                                <?= htmlspecialchars($o['nombre_especialidad'] ?? '—') ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 flex-shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                            Borrador
                        </span>
                    </div>
                </div>

                <!-- Body card -->
                <div class="p-5 flex-1 space-y-3">
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-gray-500">
                        <?php if ($o['nombre_provincia']): ?>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <?= htmlspecialchars($o['nombre_provincia']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($o['tipo_contrato']): ?>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <?= htmlspecialchars($o['tipo_contrato']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($o['fecha_publicacion']): ?>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Guardado: <?= date('d/m/Y', strtotime($o['fecha_publicacion'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($o['salario_min'] || $o['salario_max']): ?>
                    <div class="text-xs text-gray-600 font-medium bg-gray-50 rounded-lg px-3 py-2">
                        💰
                        <?php if ($o['salario_min'] && $o['salario_max']): ?>
                            $<?= number_format($o['salario_min'], 0, ',', '.') ?> – $<?= number_format($o['salario_max'], 0, ',', '.') ?> ARS
                        <?php elseif ($o['salario_min']): ?>
                            Desde $<?= number_format($o['salario_min'], 0, ',', '.') ?> ARS
                        <?php else: ?>
                            Hasta $<?= number_format($o['salario_max'], 0, ',', '.') ?> ARS
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($o['descripcion']): ?>
                    <p class="text-xs text-gray-400 line-clamp-2 leading-relaxed">
                        <?= htmlspecialchars(mb_strimwidth($o['descripcion'], 0, 120, '…')) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Acciones -->
                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                    <div class="grid grid-cols-2 gap-2">

                        <form method="POST" action="">
                            <input type="hidden" name="accion" value="reactivar">
                            <input type="hidden" name="id_oferta" value="<?= $o['id_oferta'] ?>">
                            <button type="submit"
                                class="w-full flex items-center justify-center gap-1.5 text-xs font-semibold text-green-700 bg-green-50 border border-green-200 hover:bg-green-100 px-3 py-2.5 rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reactivar oferta
                            </button>
                        </form>

                        <button type="button"
                            onclick="abrirModalEliminar(<?= $o['id_oferta'] ?>, '<?= htmlspecialchars(addslashes($o['titulo'])) ?>')"
                            class="flex items-center justify-center gap-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 hover:bg-red-100 px-3 py-2 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar
                        </button>

                    </div>
                </div>

            </div>

        <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ===== MODAL ELIMINAR DEFINITIVO ===== -->
<div id="modalEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Eliminar oferta definitivamente</h3>
                <p class="text-xs text-gray-400 truncate max-w-xs" id="modal-elim-nombre"></p>
            </div>
        </div>
        <form method="POST" action="" id="form-eliminar-global">
            <input type="hidden" name="accion"    value="eliminar_definitivo">
            <input type="hidden" name="id_oferta" id="elim-hidden-id">
            <div class="px-6 py-5">
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-xs text-red-700 font-semibold mb-1">Esta acción no se puede deshacer:</p>
                    <ul class="text-xs text-red-600 space-y-0.5 ml-3 list-disc">
                        <li>La oferta será eliminada permanentemente</li>
                        <li>No podrás recuperarla desde ninguna sección</li>
                    </ul>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEliminar()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Sí, eliminar definitivamente
                </button>
            </div>
        </form>
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
                class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
            <a href="cerrar-session.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Sí, cerrar sesión
            </a>
        </div>
    </div>
</div>

<!-- Toast container — abajo a la derecha -->
<div id="toast-container" class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 items-end pointer-events-none [&>*]:pointer-events-auto"></div>

<script>
// ── Toast ─────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const id  = 'toast-' + Date.now();
    const cfg = {
        success: { border: 'border-green-200', bar: 'bg-green-500', icon: `<svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` },
        error:   { border: 'border-red-200',   bar: 'bg-red-400',   icon: `<svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` }
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

// ── Disparar toast desde query param ─────────────────────────────────────
const toastMap = {
    ok_reactivada: [' Oferta reactivada y publicada correctamente.', 'success'],
    ok_eliminada:  [' Oferta eliminada definitivamente.',            'success'],
};
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const key    = params.get('toast');
    if (key && toastMap[key]) {
        showToast(...toastMap[key]);
        params.delete('toast');
        const qs = params.toString();
        history.replaceState({}, '', 'ofertas-borradores.php' + (qs ? '?' + qs : ''));
    }
});

// ── Sesión ────────────────────────────────────────────────────────────────
function abrirModalSesion()  { document.getElementById('modalCerrarSesion').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function cerrarModalSesion() { document.getElementById('modalCerrarSesion').classList.add('hidden');    document.body.style.overflow = 'auto';   }
document.getElementById('modalCerrarSesion').addEventListener('click', function(e) { if (e.target === this) cerrarModalSesion(); });

// ── Eliminar ──────────────────────────────────────────────────────────────
function abrirModalEliminar(id, titulo) {
    document.getElementById('modal-elim-nombre').textContent = titulo;
    document.getElementById('elim-hidden-id').value          = id;
    document.getElementById('modalEliminar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalEliminar() {
    document.getElementById('modalEliminar').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
document.getElementById('modalEliminar').addEventListener('click', function(e) { if (e.target === this) cerrarModalEliminar(); });

// ── Escape global ─────────────────────────────────────────────────────────
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { cerrarModalSesion(); cerrarModalEliminar(); }
});
</script>
</body>
</html>

<?php
if (isset($_SESSION)) {
    echo "<script>console.group('🔐 Variables de Sesión');</script>";
    foreach ($_SESSION as $key => $value) {
        if (is_array($value) || is_object($value)) {
            $val = json_encode($value);
            echo "<script>console.log('{$key}:', {$val});</script>";
        } else {
            $val = $value !== null ? addslashes($value) : 'null';
            echo "<script>console.log('{$key}:', '{$val}');</script>";
        }
    }
    echo "<script>console.groupEnd();</script>";
}
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $mensaje = addslashes($error['message'] ?? '');
        $archivo = addslashes(basename($error['file'] ?? ''));
        $linea   = $error['line'] ?? 0;
        echo "<script>console.error('💥 Error Fatal:', '{$mensaje}');</script>";
        echo "<script>console.error('📁 Archivo: {$archivo} | Línea: {$linea}');</script>";
    }
});