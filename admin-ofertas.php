<?php
$page      = 'admin-ofertas';
$pageTitle = 'Ofertas laborales';

include("conexion.php");

$ok_msg = '';
$err_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['toggle_estado'])) {
        $id    = intval($_POST['id_oferta']);
        $nuevo = in_array($_POST['nuevo_estado'], ['Activa', 'Pausada']) ? $_POST['nuevo_estado'] : 'Inactiva';
        mysqli_query($conexion, "UPDATE ofertas_laborales SET estado='$nuevo' WHERE id_oferta=$id");
        $ok_msg = $nuevo === 'Activa' ? 'Oferta activada correctamente.' : 'Oferta pausada correctamente.';
    }

    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $id = intval($_POST['id_oferta']);
        mysqli_query($conexion, "DELETE FROM postulaciones WHERE id_oferta=$id");
        mysqli_query($conexion, "DELETE FROM ofertas_laborales WHERE id_oferta=$id");
        $ok_msg = 'Oferta eliminada permanentemente.';
    }
}

// ── Filtros ───────────────────────────────────────────────────────────────────
$buscar   = trim($_GET['q']         ?? '');
$estado   = $_GET['estado']         ?? 'Activa';
$empresa  = intval($_GET['empresa'] ?? 0);
$page_num = max(1, intval($_GET['pag'] ?? 1));
$per_page = 15;
$offset   = ($page_num - 1) * $per_page;

$where = ["1=1"];
if ($buscar)             $where[] = "(o.titulo LIKE '%" . mysqli_real_escape_string($conexion, $buscar) . "%' OR e.nombre_empresa LIKE '%" . mysqli_real_escape_string($conexion, $buscar) . "%')";
if ($estado !== 'todos') $where[] = "o.estado='" . mysqli_real_escape_string($conexion, $estado) . "'";
if ($empresa > 0)        $where[] = "o.id_empresa=$empresa";
$wsql = implode(' AND ', $where);

$total_res   = mysqli_fetch_assoc(mysqli_query(
    $conexion,
    "SELECT COUNT(*) c FROM ofertas_laborales o
     INNER JOIN empresa e ON o.id_empresa=e.id_empresa
     WHERE $wsql"
))['c'] ?? 0;
$total_pages = ceil($total_res / $per_page);

$res = mysqli_query(
    $conexion,
    "SELECT o.*, e.nombre_empresa, e.logo,
            esp.nombre_especialidad,
            (SELECT COUNT(*) FROM postulaciones p WHERE p.id_oferta=o.id_oferta) AS total_postulaciones
     FROM ofertas_laborales o
     INNER JOIN empresa e ON o.id_empresa=e.id_empresa
     LEFT JOIN especialidades esp ON o.id_especialidad=esp.id_especialidad
     WHERE $wsql
     ORDER BY o.fecha_publicacion DESC
     LIMIT $per_page OFFSET $offset"
);

$res_emps_f = mysqli_query($conexion, "SELECT id_empresa, nombre_empresa FROM empresa ORDER BY nombre_empresa");

// Contadores pills
$cnt_pub  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM ofertas_laborales WHERE estado='Activa'"))['c']   ?? 0;
$cnt_pau  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM ofertas_laborales WHERE estado='Pausada'"))['c']  ?? 0;
$cnt_bor  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM ofertas_laborales WHERE estado='Borrador'"))['c']  ?? 0;
$cnt_todo = $cnt_pub + $cnt_pau + $cnt_bor;

include("sidebar-admin.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Ofertas laborales</h1>
        <p class="text-gray-500 text-sm mt-0.5"><?= $total_res ?> oferta(s) encontrada(s)</p>
    </div>

    <?php if ($ok_msg): ?><div id="php-ok-msg" class="hidden"><?= htmlspecialchars($ok_msg) ?></div><?php endif; ?>
    <?php if ($err_msg): ?><div id="php-err-msg" class="hidden"><?= htmlspecialchars($err_msg) ?></div><?php endif; ?>

    <!-- Pills -->
    <div class="flex flex-wrap gap-2 mb-5">
        <?php foreach (
            [
                'Activa'  => ['Activas',    $cnt_pub],
                'Pausada' => ['Pausadas',   $cnt_pau],
                'Borrador' => ['Borradores', $cnt_bor],
                'todos'   => ['Todas',      $cnt_todo],
            ] as $val => [$label, $cnt]
        ):
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
                <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>" placeholder="Título o empresa..."
                    class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>
            <select name="empresa" class="px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="0">Todas las empresas</option>
                <?php while ($ef = mysqli_fetch_assoc($res_emps_f)): ?>
                    <option value="<?= $ef['id_empresa'] ?>" <?= $empresa == $ef['id_empresa'] ? 'selected' : '' ?>><?= htmlspecialchars($ef['nombre_empresa']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="flex gap-2 mt-3">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">Buscar</button>
            <a href="admin-ofertas.php" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition">Limpiar</a>
        </div>
    </form>

    <!-- Tabla -->
   <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden min-h-[450px] flex flex-col">
    <div class="overflow-x-auto flex-grow">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Oferta</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Empresa</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Especialidad</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Publicación</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Postulaciones</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                <?php if (!$res || mysqli_num_rows($res) === 0): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center">
                            <p class="text-gray-400 text-sm">No hay ofertas<?= $estado === 'Pausada' ? ' pausadas' : ($estado === 'Activa' ? ' activas' : ($estado === 'Borrador' ? ' en borrador' : '')) ?></p>
                        </td>
                    </tr>
                <?php else: while ($of = mysqli_fetch_assoc($res)): 
                    // Configuración de Badges estilo "Empresa"
                    $badge_class = match ($of['estado']) {
                        'Activa'  => 'bg-green-50 text-green-700 border-green-200',
                        'Pausada' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'Borrador' => 'bg-blue-50 text-blue-700 border-blue-200',
                        default   => 'bg-gray-100 text-gray-500 border-gray-200'
                    };
                    $dot_class = match ($of['estado']) {
                        'Activa'  => 'bg-green-500',
                        'Pausada' => 'bg-amber-500',
                        'Borrador' => 'bg-blue-500',
                        default   => 'bg-gray-400'
                    };
                    $esp_oferta = $of['nombre_especialidad'] ?? '';
                ?>
                    <tr class="hover:bg-gray-50 transition <?= $of['estado'] === 'Borrador' ? 'opacity-60 bg-gray-50/50' : '' ?>">
                        <td class="px-5 py-4">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($of['titulo']) ?></p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    <?= htmlspecialchars($of['modalidad'] ?? '') ?>
                                    <?= !empty($of['tipo_contrato']) ? ' · ' . htmlspecialchars($of['tipo_contrato']) : '' ?>
                                </p>
                            </div>
                        </td>
                        <td class="px-5 py-4 hidden md:table-cell">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($of['logo'])): ?>
                                    <img src="<?= htmlspecialchars($of['logo']) ?>" class="w-8 h-8 rounded-lg object-contain border border-gray-100 flex-shrink-0" alt="">
                                <?php else: ?>
                                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs font-bold flex-shrink-0">
                                        <?= strtoupper(substr($of['nombre_empresa'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="text-sm text-gray-600 truncate max-w-[130px]"><?= htmlspecialchars($of['nombre_empresa']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 hidden lg:table-cell text-xs text-gray-600">
                            <?= $esp_oferta ? htmlspecialchars($esp_oferta) : '<span class="text-gray-300">—</span>' ?>
                        </td>
                        <td class="px-4 py-4 text-center hidden lg:table-cell text-xs text-gray-500">
                            <?= $of['fecha_publicacion'] ? date('d/m/Y', strtotime($of['fecha_publicacion'])) : '<span class="text-gray-300">—</span>' ?>
                        </td>
                        <td class="px-4 py-4 text-center hidden lg:table-cell font-semibold text-gray-700">
                            <?= $of['total_postulaciones'] ?>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 border rounded-full text-xs font-medium <?= $badge_class ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $dot_class ?>"></span>
                                <?= $of['estado'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="relative inline-block text-left">
                                <button type="button"
                                    onclick="toggleDropdown(event, 'drop-oferta-<?= $of['id_oferta'] ?>')"
                                    class="flex items-center justify-center w-8 h-8 text-gray-500 rounded-full hover:bg-gray-100 focus:outline-none transition mx-auto">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                    </svg>
                                </button>

                                <div id="drop-oferta-<?= $of['id_oferta'] ?>"
                                    class="hidden absolute right-0 z-[100] mt-2 w-44 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <button type="button"
                                            onclick="abrirModalToggle(<?= $of['id_oferta'] ?>, '<?= htmlspecialchars(addslashes($of['titulo'])) ?>', '<?= $of['estado'] ?>')"
                                            class="flex w-full px-4 py-2 text-sm text-left transition <?= $of['estado'] === 'Activa' ? 'text-orange-700 hover:bg-orange-50' : 'text-green-700 hover:bg-green-50' ?>">
                                            <?= $of['estado'] === 'Activa' ? 'Pausar oferta' : 'Activar oferta' ?>
                                        </button>
                                    </div>
                                    <div class="py-1">
                                        <button type="button"
                                            onclick="abrirModalEliminar(<?= $of['id_oferta'] ?>, '<?= htmlspecialchars(addslashes($of['titulo'])) ?>')"
                                            class="flex w-full px-4 py-2 text-sm text-left text-red-700 hover:bg-red-50 transition font-medium">
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
                <?php if ($page_num > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['pag' => $page_num - 1])) ?>" 
                       class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                <?php endif; ?>

                <?php 
                $start = max(1, $page_num - 2);
                $end = min($total_pages, $page_num + 2);
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

<!-- MODAL TOGGLE -->
<div id="modalToggle" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div id="toggle-icon" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"></div>
            <div>
                <h3 id="toggle-titulo" class="font-bold text-gray-900"></h3>
                <p id="toggle-nombre" class="text-xs text-gray-400 mt-0.5 truncate max-w-[220px]"></p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p id="toggle-mensaje" class="text-sm text-gray-600"></p>
        </div>
        <form method="POST">
            <input type="hidden" name="toggle_estado" value="1">
            <input type="hidden" name="id_oferta" id="toggle-id">
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
                <h3 class="font-bold text-gray-900">Eliminar oferta</h3>
                <p class="text-xs text-gray-400 truncate max-w-[260px]" id="modal-nombre"></p>
            </div>
        </div>
        <form method="POST" id="form-eliminar">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_oferta" id="hidden-id">
            <div class="px-6 py-5">
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-xs text-red-700 font-semibold mb-1">Esta acción es permanente:</p>
                    <ul class="text-xs text-red-600 space-y-0.5 ml-3 list-disc">
                        <li>Se eliminarán todas las postulaciones de esta oferta</li>
                        <li>Se eliminará la oferta permanentemente</li>
                    </ul>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEliminar()" class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Sí, eliminar
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
                class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">
                Cancelar
            </button>
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
        // Cerrar otros menús abiertos primero
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
            if (el.id !== id) el.classList.add('hidden');
        });
        // Alternar el actual
        const dropdown = document.getElementById(id);
        dropdown.classList.toggle('hidden');
    }

    // Cerrar si se hace click fuera
    window.onclick = function(event) {
        if (!event.target.closest('button')) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
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
    document.getElementById('modalCerrarSesion').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalSesion();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModalSesion();
    });

    function abrirModalToggle(id, titulo, estado) {
        const esActivo = estado === 'Activa';
        const icon = document.getElementById('toggle-icon');
        icon.className = 'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ' + (esActivo ? 'bg-orange-100' : 'bg-green-100');
        icon.innerHTML = esActivo ?
            `<svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>` :
            `<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
        document.getElementById('toggle-titulo').textContent = esActivo ? 'Pausar oferta' : 'Activar oferta';
        document.getElementById('toggle-nombre').textContent = titulo;
        document.getElementById('toggle-mensaje').innerHTML = esActivo ?
            `¿Estás seguro de que querés <strong>pausar</strong> esta oferta? Dejará de ser visible para los trabajadores.` :
            `¿Estás seguro de que querés <strong>activar</strong> esta oferta? Volverá a ser visible para los trabajadores.`;
        const btn = document.getElementById('toggle-btn');
        btn.textContent = esActivo ? 'Sí, pausar' : 'Sí, activar';
        btn.className = 'px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition ' + (esActivo ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700');
        document.getElementById('toggle-id').value = id;
        document.getElementById('toggle-nuevo-estado').value = esActivo ? 'Pausada' : 'Activa';
        document.getElementById('modalToggle').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalToggle() {
        document.getElementById('modalToggle').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function abrirModalEliminar(id, titulo) {
        document.getElementById('modal-nombre').textContent = titulo;
        document.getElementById('hidden-id').value = id;
        document.getElementById('modalEliminar').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalEliminar() {
        document.getElementById('modalEliminar').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('modalToggle').addEventListener('click', e => {
        if (e.target === document.getElementById('modalToggle')) cerrarModalToggle();
    });
    document.getElementById('modalEliminar').addEventListener('click', e => {
        if (e.target === document.getElementById('modalEliminar')) cerrarModalEliminar();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            cerrarModalToggle();
            cerrarModalEliminar();
        }
    });

    function showToast(msg, type = 'success') {
        const id = 'toast-' + Date.now();
        const cfg = {
            success: {
                border: 'border-green-200',
                bar: 'bg-green-500',
                icon: `<svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
            },
            error: {
                border: 'border-red-200',
                bar: 'bg-red-400',
                icon: `<svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
            }
        };
        const c = cfg[type] || cfg.success;
        const t = document.createElement('div');
        t.id = id;
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
        const ok = document.getElementById('php-ok-msg');
        const err = document.getElementById('php-err-msg');
        if (ok && ok.textContent.trim()) showToast(ok.textContent.trim(), 'success');
        if (err && err.textContent.trim()) showToast(err.textContent.trim(), 'error');
    });
</script>