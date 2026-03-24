<?php
$page = 'buscar';
$pageTitle = 'Buscar Perfiles';
include("conexion.php");

$id_empresa = $_SESSION['idempresa'] ?? null;
if (!$id_empresa) {
    header("Location: login.php");
    exit;
}

// --- FILTROS ---
$search           = trim($_GET['q'] ?? '');
$filtro_esp       = intval($_GET['especialidad'] ?? 0);
$filtro_provincia = intval($_GET['provincia'] ?? 0);
$filtro_exp       = intval($_GET['experiencia'] ?? 0);
$filtro_cv        = intval($_GET['con_cv'] ?? 0);

// --- LISTAS ---
$especialidades = [];
$res_esp = mysqli_query($conexion, "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
while ($r = mysqli_fetch_assoc($res_esp)) $especialidades[] = $r;

$provincias = [];
$res_prov = mysqli_query($conexion, "SELECT id_provincia, nombre FROM provincias ORDER BY nombre ASC");
while ($r = mysqli_fetch_assoc($res_prov)) $provincias[] = $r;

// --- QUERY ---
$col_check   = mysqli_query($conexion, "SHOW COLUMNS FROM users LIKE 'visible_busqueda'");
$tiene_visible = $col_check && mysqli_num_rows($col_check) > 0;

$estado_check = mysqli_query($conexion, "SHOW COLUMNS FROM users LIKE 'estado'");
$estado_col   = $estado_check ? mysqli_fetch_assoc($estado_check) : null;
$estado_where = "1=1";
if ($estado_col) {
    $tipo = $estado_col['Type'] ?? '';
    $estado_where = (strpos($tipo, 'int') !== false || strpos($tipo, 'tinyint') !== false)
        ? "u.estado = 1"
        : "u.estado != 'inactivo'";
}

$where = [$estado_where, "u.tipo = 2"];
if ($tiene_visible) $where[] = "(u.visible_busqueda = 1 OR u.visible_busqueda IS NULL)";

if ($search !== '') {
    $s = mysqli_real_escape_string($conexion, $search);
    $where[] = "(p.nombre LIKE '%$s%' OR p.apellido LIKE '%$s%' OR p.nombre_titulo LIKE '%$s%' OR p.descripcion_persona LIKE '%$s%')";
}
if ($filtro_esp > 0)       $where[] = "pe.id_especialidad = $filtro_esp";
if ($filtro_exp > 0)       $where[] = "p.anios_experiencia >= $filtro_exp";
if ($filtro_cv)            $where[] = "(p.curriculum_pdf IS NOT NULL AND p.curriculum_pdf != '')";

if ($filtro_provincia > 0) {
    $prov_col = mysqli_query($conexion, "SHOW COLUMNS FROM persona LIKE 'id_provincia_preferencia'");
    if ($prov_col && mysqli_num_rows($prov_col) > 0)
        $where[] = "p.id_provincia_preferencia = $filtro_provincia";
}

$col_prov   = mysqli_query($conexion, "SHOW COLUMNS FROM persona LIKE 'id_provincia_preferencia'");
$tiene_prov = $col_prov && mysqli_num_rows($col_prov) > 0;
$col_desc   = mysqli_query($conexion, "SHOW COLUMNS FROM persona LIKE 'descripcion_persona'");
$tiene_desc = $col_desc && mysqli_num_rows($col_desc) > 0;

$select_prov = $tiene_prov ? ", p.id_provincia_preferencia, prov.nombre AS nombre_provincia" : ", NULL AS nombre_provincia";
$join_prov   = $tiene_prov ? "LEFT JOIN provincias prov ON p.id_provincia_preferencia = prov.id_provincia" : "";
$select_desc = $tiene_desc ? ", p.descripcion_persona" : ", NULL AS descripcion_persona";

$where_sql = implode(' AND ', $where);

$sql = "SELECT p.id_persona, p.nombre, p.apellido, p.imagen_perfil,
               p.nombre_titulo, p.anios_experiencia, p.telefono, p.curriculum_pdf,
               u.email, u.id_usuario,
               e.nombre_especialidad
               $select_prov
               $select_desc,
               (SELECT COUNT(*) FROM postulaciones po
                INNER JOIN ofertas_laborales o ON po.id_oferta = o.id_oferta
                WHERE po.id_persona = p.id_persona AND o.id_empresa = $id_empresa) AS ya_contactado
        FROM users u
        INNER JOIN persona p ON u.id_persona = p.id_persona
        LEFT JOIN persona_especialidades pe ON pe.id_persona = p.id_persona
        LEFT JOIN especialidades e          ON pe.id_especialidad = e.id_especialidad
        $join_prov
        WHERE $where_sql
        GROUP BY p.id_persona
        ORDER BY p.anios_experiencia DESC, p.nombre ASC";

$res = mysqli_query($conexion, $sql);
if (!$res) die("<b>ERROR:</b> " . mysqli_error($conexion));

$perfiles = [];
while ($r = mysqli_fetch_assoc($res)) $perfiles[] = $r;
$total = count($perfiles);

$hay_filtros = $search || $filtro_esp || $filtro_provincia || $filtro_exp || $filtro_cv;

function foto_src_bp($img)
{
    if (!empty($img)) return 'uploads/perfil/' . $img;
    return './img/profile.png';
}

$ic = "w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition";

include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-8">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Buscar perfiles</h1>
            <p class="text-gray-500 mt-0.5 text-sm">
                <?= $total ?> perfil<?= $total != 1 ? 'es' : '' ?> encontrado<?= $total != 1 ? 's' : '' ?>
                <?= $hay_filtros ? '<span class="text-indigo-500 font-medium">con filtros aplicados</span>' : '' ?>
            </p>
        </div>
        <?php if ($hay_filtros): ?>
            <a href="buscar-perfiles.php"
                class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg transition self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Limpiar filtros
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtros en barra horizontal -->
    <form method="GET" action="" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            <!-- Búsqueda texto -->
            <div class="relative lg:col-span-2">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Nombre, título, descripción..."
                    class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>

            <!-- Especialidad -->
            <select name="especialidad" class="<?= $ic ?>">
                <option value="">Todas las especialidades</option>
                <?php foreach ($especialidades as $esp): ?>
                    <option value="<?= $esp['id_especialidad'] ?>" <?= $filtro_esp == $esp['id_especialidad'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($esp['nombre_especialidad']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Provincia -->
            <select name="provincia" class="<?= $ic ?>">
                <option value="">Todas las provincias</option>
                <?php foreach ($provincias as $prov): ?>
                    <option value="<?= $prov['id_provincia'] ?>" <?= $filtro_provincia == $prov['id_provincia'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prov['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Experiencia -->
            <select name="experiencia" class="<?= $ic ?>">
                <option value="0">Cualquier experiencia</option>
                <option value="1" <?= $filtro_exp == 1  ? 'selected' : '' ?>>1+ año</option>
                <option value="2" <?= $filtro_exp == 2  ? 'selected' : '' ?>>2+ años</option>
                <option value="3" <?= $filtro_exp == 3  ? 'selected' : '' ?>>3+ años</option>
                <option value="5" <?= $filtro_exp == 5  ? 'selected' : '' ?>>5+ años</option>
                <option value="10" <?= $filtro_exp == 10 ? 'selected' : '' ?>>10+ años</option>
            </select>

        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- CV checkbox -->
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="con_cv" id="chk_cv" value="1" <?= $filtro_cv ? 'checked' : '' ?>
                    class="w-4 h-4 accent-indigo-600 cursor-pointer rounded">
                <span class="text-sm text-gray-600 font-medium">Solo con CV adjunto</span>
            </label>

            <div class="flex gap-3 ml-auto">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Buscar
                </button>
                <?php if ($hay_filtros): ?>
                    <a href="buscar-perfiles.php"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-5 py-2 rounded-lg transition">
                        Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Tags filtros activos -->
    <?php if ($hay_filtros): ?>
        <div class="flex flex-wrap gap-2 mb-5">
            <span class="text-xs text-gray-400 self-center">Filtros:</span>
            <?php if ($search): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-full border border-indigo-200">
                    "<?= htmlspecialchars($search) ?>"
                    <a href="?<?= http_build_query(array_filter(['especialidad' => $filtro_esp, 'provincia' => $filtro_provincia, 'experiencia' => $filtro_exp, 'con_cv' => $filtro_cv])) ?>" class="ml-1 hover:text-indigo-900">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($filtro_esp): ?>
                <?php $esp_name = array_column($especialidades, 'nombre_especialidad', 'id_especialidad')[$filtro_esp] ?? ''; ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-50 text-orange-700 text-xs font-medium rounded-full border border-orange-200">
                    <?= htmlspecialchars($esp_name) ?>
                    <a href="?<?= http_build_query(array_filter(['q' => $search, 'provincia' => $filtro_provincia, 'experiencia' => $filtro_exp, 'con_cv' => $filtro_cv])) ?>" class="ml-1 hover:text-orange-900">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($filtro_provincia): ?>
                <?php $prov_name = array_column($provincias, 'nombre', 'id_provincia')[$filtro_provincia] ?? ''; ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full border border-gray-300">
                    <?= htmlspecialchars($prov_name) ?>
                    <a href="?<?= http_build_query(array_filter(['q' => $search, 'especialidad' => $filtro_esp, 'experiencia' => $filtro_exp, 'con_cv' => $filtro_cv])) ?>" class="ml-1">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($filtro_exp): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full border border-gray-300">
                    <?= $filtro_exp ?>+ años exp.
                    <a href="?<?= http_build_query(array_filter(['q' => $search, 'especialidad' => $filtro_esp, 'provincia' => $filtro_provincia, 'con_cv' => $filtro_cv])) ?>" class="ml-1">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($filtro_cv): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full border border-gray-300">
                    Con CV
                    <a href="?<?= http_build_query(array_filter(['q' => $search, 'especialidad' => $filtro_esp, 'provincia' => $filtro_provincia, 'experiencia' => $filtro_exp])) ?>" class="ml-1">✕</a>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Grid perfiles -->
    <?php if (empty($perfiles)): ?>
        <div class="bg-white border border-dashed border-gray-300 rounded-xl p-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-gray-500 font-medium">No se encontraron perfiles</p>
            <p class="text-gray-400 text-sm mt-1 mb-4">Probá ajustando los filtros de búsqueda</p>
            <?php if ($hay_filtros): ?>
                <a href="buscar-perfiles.php" class="inline-block text-sm text-indigo-600 hover:underline font-medium">Limpiar filtros →</a>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($perfiles as $p):
                $nombre   = htmlspecialchars(ucwords(strtolower($p['nombre'] . ' ' . $p['apellido'])));
                $foto     = foto_src_bp($p['imagen_perfil']);
                $cv_url   = !empty($p['curriculum_pdf'])
                    ? (str_starts_with($p['curriculum_pdf'], 'uploads/') ? $p['curriculum_pdf'] : 'uploads/cv/' . $p['curriculum_pdf'])
                    : null;
                $initials = strtoupper(substr($p['nombre'], 0, 1) . substr($p['apellido'], 0, 1));
            ?>
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition flex flex-col">

                    <!-- Header card -->
                    <div class="p-5 border-b border-gray-100">
                        <div class="flex items-start gap-4">

                            <!-- Avatar -->
                            <div class="relative flex-shrink-0">
                                <img src="<?= $foto ?>"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    class="w-12 h-12 rounded-xl object-cover border-2 border-gray-200" alt="foto">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 items-center justify-center text-white font-bold text-base" style="display:none;">
                                    <?= $initials ?>
                                </div>
                                <?php if ($p['ya_contactado'] > 0): ?>
                                    <div class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-white" title="Ya aplicó a una de tus ofertas"></div>
                                <?php endif; ?>
                            </div>

                            <!-- Info principal -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-gray-800 text-sm leading-snug truncate">
                                            <?= $nombre ?>
                                        </h3>
                                        <?php if ($p['nombre_titulo']): ?>
                                            <p class="text-xs text-indigo-600 font-medium mt-0.5 truncate">
                                                <?= htmlspecialchars($p['nombre_titulo']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-5 flex-1 space-y-3">

                        <!-- Chips -->
                        <div class="flex flex-wrap gap-1.5">
                            <?php if ($p['nombre_especialidad']): ?>
                                <span class="inline-flex items-center gap-1 text-xs bg-orange-50 text-orange-700 px-2.5 py-1 rounded-full border border-orange-200 font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Especialidad: <?= htmlspecialchars($p['nombre_especialidad']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($p['anios_experiencia']): ?>
                                <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?= intval($p['anios_experiencia']) ?> año<?= $p['anios_experiencia'] != 1 ? 's' : '' ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($p['nombre_provincia']): ?>
                                <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <?= htmlspecialchars($p['nombre_provincia']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($p['ya_contactado'] > 0): ?>
                                <span class="inline-flex items-center gap-1 text-xs bg-green-50 text-green-700 px-2.5 py-1 rounded-full border border-green-200 font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Ya postulado
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Descripción -->
                        <?php if ($p['descripcion_persona']): ?>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">
                                <?= htmlspecialchars($p['descripcion_persona']) ?>
                            </p>
                        <?php endif; ?>

                    </div>

                    <!-- Footer acciones -->
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl grid grid-cols-2 gap-2">
                        <button onclick="abrirContacto('<?= htmlspecialchars($p['email'], ENT_QUOTES) ?>','<?= htmlspecialchars($p['telefono'] ?? '', ENT_QUOTES) ?>','<?= htmlspecialchars($nombre, ENT_QUOTES) ?>')"
                            class="flex items-center justify-center gap-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 px-3 py-2.5 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Contactar
                        </button>
                       <a href="ver-perfil-trabajador.php?id=<?= $p['id_persona'] ?>&from=buscar-perfiles"
                            class="flex items-center justify-center gap-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 px-3 py-2.5 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Ver perfil
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL CONTACTAR -->
<div id="modalContacto" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Datos de contacto</h3>
            <button onclick="cerrarContacto()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <p id="contacto-nombre" class="font-semibold text-gray-800 text-base"></p>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-400">Email</p>
                        <a id="contacto-email" href="#" class="text-sm font-medium text-indigo-600 hover:underline truncate block"></a>
                    </div>
                    <button onclick="copiar('contacto-email-val')" class="text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition" title="Copiar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <span id="contacto-email-val" class="hidden"></span>
                </div>
                <div id="contacto-tel-wrap" class="hidden items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-400">Teléfono</p>
                        <p id="contacto-tel" class="text-sm font-medium text-gray-800"></p>
                    </div>
                    <button onclick="copiar('contacto-tel')" class="text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition" title="Copiar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end">
            <button onclick="cerrarContacto()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-100 transition">Cerrar</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-5 right-5 z-50 bg-gray-800 text-white text-sm px-5 py-3 rounded-xl shadow-lg"></div>

</main>
</div>

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

    function abrirContacto(email, tel, nombre) {
        document.getElementById('contacto-nombre').textContent = nombre;
        document.getElementById('contacto-email').textContent = email;
        document.getElementById('contacto-email').href = 'mailto:' + email;
        document.getElementById('contacto-email-val').textContent = email;
        const telWrap = document.getElementById('contacto-tel-wrap');
        if (tel) {
            document.getElementById('contacto-tel').textContent = tel;
            telWrap.classList.remove('hidden');
            telWrap.classList.add('flex');
        } else {
            telWrap.classList.add('hidden');
            telWrap.classList.remove('flex');
        }
        document.getElementById('modalContacto').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarContacto() {
        document.getElementById('modalContacto').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function copiar(id) {
        const txt = document.getElementById(id).textContent;
        navigator.clipboard.writeText(txt).then(() => mostrarToast('Copiado al portapapeles ✓'));
    }

    function mostrarToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 2500);
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') cerrarContacto();
    });
    document.getElementById('modalContacto').addEventListener('click', function(e) {
        if (e.target === this) cerrarContacto();
    });
</script>