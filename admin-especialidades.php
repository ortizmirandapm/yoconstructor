<?php
$page      = 'admin-especialidades';
$pageTitle = 'Especialidades';

include("conexion.php");

$ok_msg = '';
$err_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim(mysqli_real_escape_string($conexion, $_POST['nombre'] ?? ''));
        $desc   = trim(mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? ''));
        $estado = ($_POST['estado'] ?? '1') === '1' ? 1 : 0;
        if (!$nombre) {
            $err_msg = 'El nombre es obligatorio.';
        } else {
            mysqli_query($conexion, "INSERT INTO especialidades (nombre_especialidad, descripcion, estado) VALUES ('$nombre','$desc',$estado)");
            $ok_msg = 'Especialidad creado correctamente.';
        }
    }

    if ($accion === 'editar') {
        $id     = intval($_POST['id']);
        $nombre = trim(mysqli_real_escape_string($conexion, $_POST['nombre'] ?? ''));
        $desc   = trim(mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? ''));
        $estado = ($_POST['estado'] ?? '1') === '1' ? 1 : 0;
        if (!$nombre) {
            $err_msg = 'El nombre es obligatorio.';
        } else {
            mysqli_query($conexion, "UPDATE especialidades SET nombre_especialidad='$nombre', descripcion='$desc', estado=$estado WHERE id_especialidad=$id");
            $ok_msg = 'Especialidad actualizado correctamente.';
        }
    }

    if ($accion === 'toggle') {
        $id     = intval($_POST['id']);
        $nuevo  = intval($_POST['nuevo_estado']);
        mysqli_query($conexion, "UPDATE especialidades SET estado=$nuevo WHERE id_especialidad=$id");
        $ok_msg = $nuevo ? 'Especialidad activado.' : 'Especialidad desactivado.';
    }

    if ($accion === 'eliminar') {
        $id  = intval($_POST['id']);
        $uso = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM persona_especialidades WHERE id_especialidad=$id"))['c'] ?? 0;
        if ($uso > 0) {
            $err_msg = "No se puede eliminar: $uso trabajador(es) tienen esta especialidad.";
        } else {
            mysqli_query($conexion, "DELETE FROM especialidades WHERE id_especialidad=$id");
            $ok_msg = 'Especialidad eliminado correctamente.';
        }
    }
}

// Filtros
$buscar   = trim($_GET['q']      ?? '');
$estado   = $_GET['estado']      ?? 'activo';
$page_num = max(1, intval($_GET['pag'] ?? 1));
$per_page = 15;
$offset   = ($page_num - 1) * $per_page;

$where = ["1=1"];
if ($buscar)           $where[] = "nombre_especialidad LIKE '%" . mysqli_real_escape_string($conexion, $buscar) . "%'";
if ($estado === 'activo')   $where[] = "estado=1";
if ($estado === 'inactivo') $where[] = "estado=0";
$wsql = implode(' AND ', $where);

$total_res   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM especialidades WHERE $wsql"))['c'] ?? 0;
$total_pages = ceil($total_res / $per_page);
$res         = mysqli_query(
    $conexion,
    "SELECT r.*, (SELECT COUNT(*) FROM persona_especialidades WHERE id_especialidad=r.id_especialidad) AS uso
     FROM especialidades r WHERE $wsql ORDER BY nombre_especialidad ASC LIMIT $per_page OFFSET $offset"
);

$cnt_activos   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM especialidades WHERE estado=1"))['c'] ?? 0;
$cnt_inactivos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM especialidades WHERE estado=0"))['c'] ?? 0;

include("sidebar-admin.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Especialidades</h1>
            <p class="text-gray-500 text-sm mt-0.5"><?= $total_res ?> especialidad(es) encontrada(s)</p>
        </div>
        <button onclick="abrirModalForm('crear')"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva especialidad
        </button>
    </div>

    <?php if ($ok_msg): ?><div id="php-ok-msg" class="hidden"><?= htmlspecialchars($ok_msg) ?></div><?php endif; ?>
    <?php if ($err_msg): ?><div id="php-err-msg" class="hidden"><?= htmlspecialchars($err_msg) ?></div><?php endif; ?>

    <!-- Pills -->
    <div class="flex flex-wrap gap-2 mb-5">
        <?php foreach (['activo' => ['Activos', $cnt_activos], 'inactivo' => ['Inactivos', $cnt_inactivos], 'todos' => ['Todos', $cnt_activos + $cnt_inactivos]] as $val => [$label, $cnt]):
            $active = $estado === $val;
            $cls = $active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'; ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['estado' => $val, 'pag' => 1])) ?>"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium transition <?= $cls ?>">
                <?= $label ?>
                <span class="<?= $active ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' ?> text-xs px-1.5 py-0.5 rounded-full font-semibold"><?= $cnt ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Búsqueda -->
    <form method="GET" class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 mb-5">
        <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
        <div class="flex gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar por nombre..."
                    class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">Buscar</button>
            <?php if ($buscar): ?>
                <a href="?estado=<?= $estado ?>" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-600 text-sm rounded-xl hover:bg-gray-50 transition">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabla -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden min-h-[450px] flex flex-col">
        <div class="overflow-x-auto flex-grow">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Descripción</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Trabajadores</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php if (!$res || mysqli_num_rows($res) === 0): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="text-gray-400 text-sm">No hay especialidades<?= $estado === 'inactivo' ? ' inactivas' : ($estado === 'activo' ? ' activas' : '') ?></p>
                                <?php if ($buscar): ?>
                                    <a href="?estado=<?= $estado ?>" class="text-xs text-indigo-600 hover:underline mt-2 inline-block font-medium">Limpiar búsqueda</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: while ($r = mysqli_fetch_assoc($res)): ?>
                            <tr class="hover:bg-gray-50 transition <?= $r['estado'] ? '' : 'opacity-60 bg-gray-50/50' ?>">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($r['nombre_especialidad']) ?></p>
                                </td>
                                <td class="px-5 py-4 hidden md:table-cell">
                                    <p class="text-xs text-gray-500 max-w-xs line-clamp-1">
                                        <?= htmlspecialchars($r['descripcion'] ?: 'Sin descripción') ?>
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-center font-semibold text-gray-700"><?= $r['uso'] ?></td>
                                <td class="px-4 py-4 text-center">
                                    <?php if ($r['estado']): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-xs font-medium">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-500 border border-gray-300 rounded-full text-xs font-medium">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>Inactiva
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="relative inline-block text-left">
                                        <button type="button"
                                            onclick="toggleDropdown(event, 'drop-esp-<?= $r['id_especialidad'] ?>')"
                                            class="flex items-center justify-center w-8 h-8 text-gray-500 rounded-full hover:bg-gray-100 focus:outline-none transition mx-auto">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>

                                        <div id="drop-esp-<?= $r['id_especialidad'] ?>"
                                            class="hidden absolute right-0 z-[100] mt-2 w-44 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">

                                            <div class="py-1">
                                                <button type="button"
                                                    onclick="abrirModalToggle(<?= $r['id_especialidad'] ?>, '<?= htmlspecialchars(addslashes($r['nombre_especialidad'])) ?>', <?= $r['estado'] ?>)"
                                                    class="flex w-full px-4 py-2 text-sm text-left transition <?= $r['estado'] ? 'text-orange-700 hover:bg-orange-50' : 'text-green-700 hover:bg-green-50' ?>">
                                                    <?= $r['estado'] ? 'Desactivar especialidad' : 'Activar especialidad' ?>
                                                </button>

                                                <button type="button"
                                                    onclick="abrirModalForm('editar', <?= $r['id_especialidad'] ?>, '<?= htmlspecialchars(addslashes($r['nombre_especialidad'])) ?>', '<?= htmlspecialchars(addslashes($r['descripcion'] ?? '')) ?>', <?= $r['estado'] ?>)"
                                                    class="flex w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                                    Editar detalles
                                                </button>
                                            </div>

                                            <div class="py-1">
                                                <button type="button"
                                                    onclick="abrirModalEliminar(<?= $r['id_especialidad'] ?>, '<?= htmlspecialchars(addslashes($r['nombre_especialidad'])) ?>', <?= $r['uso'] ?>)"
                                                    class="flex w-full px-4 py-2 text-sm text-left text-red-700 hover:bg-red-50 transition font-medium">
                                                    Eliminar especialidad
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                    <?php endwhile;
                    endif; ?>
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
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
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
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL CREAR / EDITAR -->
<div id="modalForm" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <h3 id="mf-titulo" class="font-bold text-gray-900 text-lg"></h3>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" id="mf-accion">
            <input type="hidden" name="id" id="mf-id">
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" id="mf-nombre" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                        placeholder="Nombre de la especialidad...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Descripción</label>
                    <textarea name="descripcion" id="mf-desc" rows="3"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none"
                        placeholder="Descripción opcional..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Estado</label>
                    <select name="estado" id="mf-estado" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalForm()" class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="mf-btn-txt">Guardar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TOGGLE -->
<div id="modalToggle" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div id="mt-icon" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"></div>
            <div>
                <h3 id="mt-titulo" class="font-bold text-gray-900"></h3>
                <p id="mt-nombre" class="text-xs text-gray-400 mt-0.5"></p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p id="mt-mensaje" class="text-sm text-gray-600"></p>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="toggle">
            <input type="hidden" name="id" id="mt-id">
            <input type="hidden" name="nuevo_estado" id="mt-nuevo">
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalToggle()" class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" id="mt-btn" class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition"></button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ELIMINAR -->
<div id="modalEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Eliminar especialidad</h3>
                <p class="text-xs text-gray-400" id="me-nombre"></p>
            </div>
        </div>
        <form method="POST" id="form-eliminar">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" id="me-id">
            <div class="px-6 py-5">
                <p id="me-mensaje" class="text-sm text-gray-600"></p>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEliminar()" class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" id="me-btn" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">Eliminar</button>
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

    function abrirModalForm(modo, id = '', nombre = '', desc = '', estado = 1) {
        document.getElementById('mf-titulo').textContent = modo === 'crear' ? 'Nueva especialidad' : 'Editar especialidad';
        document.getElementById('mf-accion').value = modo;
        document.getElementById('mf-id').value = id;
        document.getElementById('mf-nombre').value = nombre;
        document.getElementById('mf-desc').value = desc;
        document.getElementById('mf-estado').value = estado ? '1' : '0';
        document.getElementById('mf-btn-txt').textContent = modo === 'crear' ? 'Crear' : 'Guardar cambios';
        document.getElementById('modalForm').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('mf-nombre').focus(), 100);
    }

    function cerrarModalForm() {
        document.getElementById('modalForm').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function abrirModalToggle(id, nombre, estadoActual) {
        const activo = estadoActual == 1;
        const icon = document.getElementById('mt-icon');
        icon.className = 'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ' + (activo ? 'bg-orange-100' : 'bg-green-100');
        icon.innerHTML = activo ?
            `<svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>` :
            `<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
        document.getElementById('mt-titulo').textContent = activo ? 'Desactivar especialidad' : 'Activar especialidad';
        document.getElementById('mt-nombre').textContent = nombre;
        document.getElementById('mt-mensaje').innerHTML = activo ?
            `¿Estás seguro de que querés <strong>desactivar</strong> esta especialidad?` :
            `¿Estás seguro de que querés <strong>activar</strong> esta especialidad?`;
        const btn = document.getElementById('mt-btn');
        btn.textContent = activo ? 'Sí, desactivar' : 'Sí, activar';
        btn.className = 'px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition ' + (activo ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700');
        document.getElementById('mt-id').value = id;
        document.getElementById('mt-nuevo').value = activo ? '0' : '1';
        document.getElementById('modalToggle').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalToggle() {
        document.getElementById('modalToggle').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function abrirModalEliminar(id, nombre, uso) {
        document.getElementById('me-nombre').textContent = nombre;
        document.getElementById('me-id').value = id;
        document.getElementById('me-mensaje').innerHTML = uso > 0 ?
            `<span class="text-red-600 font-semibold">No se puede eliminar:</span> esta especialidad está siendo usado por <strong>${uso} empresa(s)</strong>.` :
            `¿Estás seguro de que querés eliminar <strong>${nombre}</strong>? Esta acción no se puede deshacer.`;
        const btn = document.getElementById('me-btn');
        btn.disabled = uso > 0;
        btn.className = uso > 0 ?
            'px-5 py-2.5 bg-gray-200 text-gray-400 text-sm font-semibold rounded-xl cursor-not-allowed' :
            'inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition';
        document.getElementById('modalEliminar').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalEliminar() {
        document.getElementById('modalEliminar').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        const nombre = btn.dataset.nombre;
        if (action === 'toggle') abrirModalToggle(id, nombre, btn.dataset.estado);
        if (action === 'editar') abrirModalForm('editar', id, nombre, btn.dataset.desc, btn.dataset.estado);
        if (action === 'eliminar') abrirModalEliminar(id, nombre, btn.dataset.uso);
    });

    ['modalForm', 'modalToggle', 'modalEliminar'].forEach(id => {
        document.getElementById(id).addEventListener('click', e => {
            if (e.target === document.getElementById(id)) window['cerrarModal' + id.replace('modal', '')] && window['cerrarModal' + id.replace('modal', '')]();
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            cerrarModalForm();
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