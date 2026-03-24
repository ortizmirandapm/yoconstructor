<?php

$page = 'postulantes-global';
$pageTitle = 'Todos los postulantes';
include("conexion.php");
include_once("notificaciones_helper.php");

$id_empresa = $_SESSION['idempresa'] ?? null;
if (!$id_empresa) {
    header("Location: login.php");
    exit;
}

// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion         = $_POST['accion'] ?? '';
    $id_postulacion = intval($_POST['id_postulacion'] ?? 0);

    if ($id_postulacion > 0) {
        $check = mysqli_query($conexion, "SELECT p.id_postulacion FROM postulaciones p
            INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
            WHERE p.id_postulacion = $id_postulacion AND o.id_empresa = $id_empresa");

        if ($check && mysqli_num_rows($check) > 0) {
            if ($accion === 'cambiar_estado') {
                $nuevo = $_POST['nuevo_estado'] ?? '';
                $ok    = ['Pendiente', 'Revisada', 'Entrevista', 'Aceptada', 'Rechazada'];
                if (in_array($nuevo, $ok)) {
                    $stmt = mysqli_prepare($conexion, "UPDATE postulaciones SET estado = ?, fecha_actualizacion = NOW() WHERE id_postulacion = ?");
                    mysqli_stmt_bind_param($stmt, 'si', $nuevo, $id_postulacion);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    notificar_estado_postulacion($conexion, $id_postulacion, $nuevo);
                }
            } elseif ($accion === 'guardar_notas') {
                $notas = trim($_POST['notas'] ?? '');
                $stmt  = mysqli_prepare($conexion, "UPDATE postulaciones SET notas_empresa = ? WHERE id_postulacion = ?");
                mysqli_stmt_bind_param($stmt, 'si', $notas, $id_postulacion);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }

    $qs = http_build_query(array_filter([
        'estado' => $_GET['estado'] ?? '',
        'oferta' => $_GET['oferta'] ?? '',
        'q'      => $_GET['q']      ?? '',
    ]));
    header("Location: postulantes-global.php" . ($qs ? "?$qs" : ''));
    exit;
}

// --- FILTROS ---
$filtro_estado   = $_GET['estado'] ?? '';
$filtro_oferta   = intval($_GET['oferta'] ?? 0);
$filtro_q        = trim($_GET['q'] ?? '');
$estados_validos = ['Pendiente', 'Revisada', 'Entrevista', 'Aceptada', 'Rechazada'];

// --- CONTADORES POR ESTADO ---
$res_counts = mysqli_query($conexion, "SELECT p.estado, COUNT(*) AS total
    FROM postulaciones p
    INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
    WHERE o.id_empresa = $id_empresa
    GROUP BY p.estado");
$counts = ['Pendiente' => 0, 'Revisada' => 0, 'Entrevista' => 0, 'Aceptada' => 0, 'Rechazada' => 0];
while ($c = mysqli_fetch_assoc($res_counts)) $counts[$c['estado']] = intval($c['total']);
$total = array_sum($counts);

// --- OFERTAS PARA EL SELECT FILTRO ---
$res_of = mysqli_query($conexion, "SELECT o.id_oferta, o.titulo,
    COUNT(p.id_postulacion) AS total_post
    FROM ofertas_laborales o
    LEFT JOIN postulaciones p ON p.id_oferta = o.id_oferta
    WHERE o.id_empresa = $id_empresa AND o.estado != 'Borrador'
    GROUP BY o.id_oferta HAVING total_post > 0
    ORDER BY o.fecha_publicacion DESC");
$ofertas_select = [];
while ($r = mysqli_fetch_assoc($res_of)) $ofertas_select[] = $r;

// --- QUERY PRINCIPAL ---
$where = ["o.id_empresa = $id_empresa"];
if ($filtro_estado && in_array($filtro_estado, $estados_validos)) {
    $fe      = mysqli_real_escape_string($conexion, $filtro_estado);
    $where[] = "p.estado = '$fe'";
}
if ($filtro_oferta > 0) {
    $where[] = "p.id_oferta = $filtro_oferta";
}
if ($filtro_q !== '') {
    $fq      = mysqli_real_escape_string($conexion, $filtro_q);
    $where[] = "(per.nombre LIKE '%$fq%' OR per.apellido LIKE '%$fq%' OR o.titulo LIKE '%$fq%')";
}
$where_sql = implode(' AND ', $where);

$sql = "SELECT
            p.id_postulacion, p.estado, p.fecha_postulacion, p.notas_empresa, p.cv_adjunto,
            per.id_persona, per.nombre, per.apellido, per.anios_experiencia,
            per.descripcion_persona,
            per.imagen_perfil, per.telefono, per.nombre_titulo, per.curriculum_pdf,
            u.email, per.fecha_nacimiento,
            o.id_oferta, o.titulo AS oferta_titulo, o.descripcion AS oferta_desc,
            o.requisitos AS oferta_req, o.tipo_contrato, o.modalidad,
            o.salario_min, o.salario_max, o.experiencia_requerida,
            o.fecha_publicacion, o.fecha_vencimiento, o.estado AS oferta_estado,
            prov.nombre AS provincia, loc.nombre_localidad AS localidad
        FROM postulaciones p
        INNER JOIN ofertas_laborales o ON p.id_oferta  = o.id_oferta
        INNER JOIN persona per         ON p.id_persona = per.id_persona
        INNER JOIN users u             ON u.id_persona = per.id_persona
        LEFT JOIN provincias prov      ON o.id_provincia = prov.id_provincia
        LEFT JOIN localidades loc      ON o.id_localidad = loc.id_localidad
        WHERE $where_sql
        ORDER BY p.fecha_postulacion DESC";

$res = mysqli_query($conexion, $sql);
$postulantes = [];
while ($r = mysqli_fetch_assoc($res)) $postulantes[] = $r;

// --- ESPECIALIDADES AGRUPADAS POR PERSONA ---
$ids_personas = array_unique(array_column($postulantes, 'id_persona'));
$especialidades_map = [];
if (!empty($ids_personas)) {
    $ids_str = implode(',', array_map('intval', $ids_personas));
    $res_esp = mysqli_query($conexion, "SELECT pe.id_persona, e.nombre_especialidad,
            pe.nivel_experiencia, pe.es_principal
        FROM persona_especialidades pe
        INNER JOIN especialidades e ON pe.id_especialidad = e.id_especialidad
        WHERE pe.id_persona IN ($ids_str)
        ORDER BY pe.id_persona, pe.es_principal DESC, pe.fecha_agregado ASC");
    while ($e = mysqli_fetch_assoc($res_esp)) {
        $especialidades_map[$e['id_persona']][] = [
            'nombre'    => $e['nombre_especialidad'],
            'nivel'     => $e['nivel_experiencia'] ?? '',
            'principal' => (bool) $e['es_principal'],
        ];
    }
}

$estado_cfg = [
    'Pendiente'  => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'border' => 'border-yellow-300', 'dot' => 'bg-yellow-400', 'label' => 'Pendiente'],
    'Revisada'   => ['bg' => 'bg-blue-100',  'text' => 'text-blue-800',  'border' => 'border-blue-300',  'dot' => 'bg-blue-400',  'label' => 'Revisada'],
    'Entrevista' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'border' => 'border-purple-300', 'dot' => 'bg-purple-500', 'label' => 'Pre-seleccionado'],
    'Aceptada'   => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-300', 'dot' => 'bg-green-500', 'label' => 'Aceptada'],
    'Rechazada'  => ['bg' => 'bg-red-100',   'text' => 'text-red-800',   'border' => 'border-red-300',   'dot' => 'bg-red-400',   'label' => 'Rechazada'],
];

function nivel_badge_pg($nivel)
{
    $cfg = match ($nivel) {
        'Básico'     => 'bg-gray-100 text-gray-500 border-gray-300',
        'Intermedio' => 'bg-blue-50 text-blue-600 border-blue-200',
        'Avanzado'   => 'bg-purple-50 text-purple-600 border-purple-200',
        'Experto'    => 'bg-amber-50 text-amber-600 border-amber-200',
        default      => '',
    };
    if (!$cfg || !$nivel) return '';
    return "<span class=\"inline-flex items-center text-xs px-2 py-0.5 rounded-full border font-medium {$cfg}\">{$nivel}</span>";
}
function tr_pg($fecha)
{
    $d = time() - strtotime($fecha);
    if ($d < 3600)    return 'Hace ' . max(1, floor($d / 60)) . ' min';
    if ($d < 86400)   return 'Hace ' . floor($d / 3600) . ' h';
    if ($d < 172800)  return 'Ayer';
    if ($d < 604800)  return 'Hace ' . floor($d / 86400) . ' días';
    if ($d < 2592000) return 'Hace ' . floor($d / 604800) . ' semana' . (floor($d / 604800) > 1 ? 's' : '');
    return date('d/m/Y', strtotime($fecha));
}
function cv_url_pg($curriculum_pdf, $cv_adjunto)
{
    $f = !empty($curriculum_pdf) ? $curriculum_pdf : ($cv_adjunto ?? '');
    if (!$f) return null;
    return str_starts_with($f, 'uploads/') ? $f : 'uploads/cv/' . $f;
}

include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-10">

    <!-- Encabezado -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Postulantes</h1>
        <p class="text-gray-500 mt-1 text-sm"><?= $total ?> postulante<?= $total !== 1 ? 's' : '' ?> en total</p>
    </div>

    <!-- Pills de estado -->
    <div class="flex flex-wrap gap-2 mb-5">
        <?php
        $pills = [
            ''           => ['label' => 'Todos',           'cnt' => $total,                'active' => 'bg-teal-600 text-white border-teal-600',    'dot' => ''],
            'Pendiente'  => ['label' => 'Pendiente',        'cnt' => $counts['Pendiente'],  'active' => 'bg-yellow-500 text-white border-yellow-500', 'dot' => 'bg-yellow-400'],
            'Revisada'   => ['label' => 'Revisada',         'cnt' => $counts['Revisada'],   'active' => 'bg-blue-500 text-white border-blue-500',     'dot' => 'bg-blue-400'],
            'Entrevista' => ['label' => 'Pre-seleccionado', 'cnt' => $counts['Entrevista'], 'active' => 'bg-purple-500 text-white border-purple-500', 'dot' => 'bg-purple-500'],
            'Aceptada'   => ['label' => 'Aceptada',         'cnt' => $counts['Aceptada'],   'active' => 'bg-green-500 text-white border-green-500',   'dot' => 'bg-green-500'],
            'Rechazada'  => ['label' => 'Rechazada',        'cnt' => $counts['Rechazada'],  'active' => 'bg-red-500 text-white border-red-500',       'dot' => 'bg-red-400'],
        ];
        foreach ($pills as $val => $pi):
            $activo = $filtro_estado === $val;
            $params = array_filter(['estado' => $val, 'oferta' => $filtro_oferta ?: '', 'q' => $filtro_q]);
            $href   = 'postulantes-global.php' . ($params ? '?' . http_build_query($params) : '');
        ?>
            <a href="<?= $href ?>"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium border transition
                <?= $activo ? $pi['active'] : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' ?>">
                <?php if ($val): ?>
                    <span class="w-2 h-2 rounded-full flex-shrink-0 <?= $activo ? 'bg-white opacity-70' : $pi['dot'] ?>"></span>
                <?php endif; ?>
                <?= $pi['label'] ?>
                <span class="<?= $activo ? 'bg-white bg-opacity-25 text-white' : 'bg-gray-100 text-gray-600' ?> text-xs font-bold px-1.5 py-0.5 rounded-full"><?= $pi['cnt'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtros búsqueda + oferta -->
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <?php if ($filtro_estado): ?>
            <input type="hidden" name="estado" value="<?= htmlspecialchars($filtro_estado) ?>">
        <?php endif; ?>
        <div class="relative flex-1 min-w-48">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" name="q" value="<?= htmlspecialchars($filtro_q) ?>"
                placeholder="Buscar por nombre o puesto..."
                class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
        </div>
        <select name="oferta" class="px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition min-w-52">
            <option value="">Todas las ofertas</option>
            <?php foreach ($ofertas_select as $of): ?>
                <option value="<?= $of['id_oferta'] ?>" <?= $filtro_oferta == $of['id_oferta'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($of['titulo']) ?> (<?= $of['total_post'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-semibold transition">Filtrar</button>
        <?php if ($filtro_q || $filtro_oferta): ?>
            <a href="postulantes-global.php<?= $filtro_estado ? '?estado=' . $filtro_estado : '' ?>"
                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Limpiar</a>
        <?php endif; ?>
    </form>

    <p class="text-xs text-gray-400 mb-4"><?= count($postulantes) ?> resultado<?= count($postulantes) !== 1 ? 's' : '' ?></p>

    <!-- ── GRID DE CARDS ── -->
    <?php if (empty($postulantes)): ?>
        <div class="bg-white border border-dashed border-gray-300 rounded-xl p-16 text-center">
            <svg class="w-14 h-14 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-gray-400 font-medium text-lg mb-1">Sin resultados</p>
            <p class="text-gray-400 text-sm">Probá con otros filtros o esperá nuevas postulaciones</p>
        </div>

    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($postulantes as $p):
                $cfg        = $estado_cfg[$p['estado']] ?? $estado_cfg['Pendiente'];
                $foto       = !empty($p['imagen_perfil']) ? 'uploads/perfil/' . $p['imagen_perfil'] : './img/profile.png';
                $cv         = cv_url_pg($p['curriculum_pdf'], $p['cv_adjunto']);
                $nombre     = ucwords(strtolower($p['nombre'] . ' ' . $p['apellido']));
                $esps       = $especialidades_map[$p['id_persona']] ?? [];
                $esp_main   = $esps[0] ?? null;
                $otras_esps = array_slice($esps, 1);
                $dd_id      = 'ddg-' . $p['id_postulacion'];

                $oferta_data = htmlspecialchars(json_encode([
                    'titulo'      => $p['oferta_titulo'],
                    'descripcion' => $p['oferta_desc'] ?? '',
                    'requisitos'  => $p['oferta_req'] ?? '',
                    'tipo'        => $p['tipo_contrato'] ?? '',
                    'modalidad'   => $p['modalidad'] ?? '',
                    'sal_min'     => $p['salario_min'],
                    'sal_max'     => $p['salario_max'],
                    'experiencia' => $p['experiencia_requerida'],
                    'provincia'   => $p['provincia'] ?? '',
                    'localidad'   => $p['localidad'] ?? '',
                    'especialidad' => $esp_main ? $esp_main['nombre'] : '',
                    'publicacion' => $p['fecha_publicacion'] ? date('d/m/Y', strtotime($p['fecha_publicacion'])) : '',
                    'vencimiento' => $p['fecha_vencimiento']  ? date('d/m/Y', strtotime($p['fecha_vencimiento'])) : '',
                    'estado'      => $p['oferta_estado'],
                    'id_oferta'   => $p['id_oferta'],
                ]), ENT_QUOTES, 'UTF-8');
            ?>
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden">

                    <!-- ── FRANJA OFERTA ── -->
                    <div class="bg-indigo-50 border-b border-indigo-100 px-4 py-2.5 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <svg class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs text-indigo-400 font-medium flex-shrink-0">Se postuló a:</span>
                            <span class="text-xs font-semibold text-indigo-700 truncate"><?= htmlspecialchars($p['oferta_titulo']) ?></span>
                            <?php if ($p['localidad'] || $p['provincia']): ?>
                                <span class="hidden lg:inline text-xs text-indigo-400 flex-shrink-0">
                                    · <?= htmlspecialchars($p['localidad'] ? $p['localidad'] . ', ' . $p['provincia'] : $p['provincia']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <button onclick='abrirOferta(<?= $oferta_data ?>)'
                            class="flex-shrink-0 inline-flex items-center gap-1 text-xs px-2.5 py-1 bg-white border border-indigo-200 text-indigo-600 hover:bg-indigo-100 rounded-lg transition font-medium whitespace-nowrap">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Ver oferta
                        </button>
                    </div>

                    <!-- ── HEADER CARD ── -->
                    <div class="px-5 pt-4 pb-4 border-b border-gray-100">

                        <!-- Badge estado -->
                        <div class="flex justify-end mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $cfg['bg'] . ' ' . $cfg['text'] . ' border ' . $cfg['border'] ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $cfg['dot'] ?>"></span>
                                <?= $cfg['label'] ?>
                            </span>
                        </div>

                        <!-- Foto + nombre + CV -->
                        <div class="flex items-start gap-4">
                            <img src="<?= $foto ?>" class="w-14 h-14 rounded-full object-cover border-2 border-gray-200 flex-shrink-0" alt="foto">
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-bold text-gray-900 leading-tight"><?= htmlspecialchars($nombre) ?></h3>
                                <?php if ($p['nombre_titulo']): ?>
                                    <p class="text-xs text-gray-400 mt-0.5 truncate"><?= htmlspecialchars($p['nombre_titulo']) ?></p>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <?php if ($cv): ?>
                                        <a href="<?= $cv ?>" target="_blank"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-600 hover:text-cyan-700 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Ver CV
                                        </a>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Sin CV
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── CUERPO ── -->
                    <div class="px-5 py-4 flex-1 space-y-4">

                        <!-- Descripción -->
                        <?php if ($p['descripcion_persona']): ?>
                            <p class="text-sm text-gray-600 leading-relaxed line-clamp-3"><?= htmlspecialchars($p['descripcion_persona']) ?></p>
                        <?php else: ?>
                            <p class="text-sm text-gray-400 italic">Sin descripción</p>
                        <?php endif; ?>

                        <!-- Datos estructurados -->
                        <div class="space-y-2.5">

                            <div class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm">
                                    <span class="text-gray-400">Experiencia:</span>
                                    <span class="text-gray-700 font-medium ml-1">
                                        <?= $p['anios_experiencia'] ? intval($p['anios_experiencia']) . ' año' . (intval($p['anios_experiencia']) != 1 ? 's' : '') : 'No especificada' ?>
                                    </span>
                                </p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <p class="text-sm">
                                    <span class="text-gray-400">Edad:</span>
                                    <span class="text-gray-700 font-medium ml-1">
                                        <?php if (!empty($p['fecha_nacimiento'])):
                                            $edad = (int) date_diff(date_create($p['fecha_nacimiento']), date_create('today'))->y;
                                            echo $edad . ' años';
                                        else: ?>
                                            No especificada
                                        <?php endif; ?>
                                    </span>
                                </p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-sm text-gray-400">Especialidad principal:</span>
                                    <?php if ($esp_main): ?>
                                        <span class="text-sm text-gray-700 font-medium"><?= htmlspecialchars($esp_main['nombre']) ?></span>
                                        
                                    <?php else: ?>
                                        <span class="text-sm text-gray-700 font-medium">No especificada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                                    <!--     
                            <?php if (!empty($otras_esps)): ?>
                                <div class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <div class="flex flex-col gap-1.5">
                                        <span class="text-sm text-gray-400">Otras especialidades:</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <?php foreach ($otras_esps as $oe): ?>
                                                <span class="inline-flex items-center gap-1.5 text-xs bg-orange-50/70 text-orange-600 px-2.5 py-1 rounded-full border border-orange-200 font-medium">
                                                    <?= htmlspecialchars($oe['nombre']) ?>
                                                    <?php if ($oe['nivel']): ?>
                                                        <span class="<?= match ($oe['nivel']) {
                                                                            'Básico'     => 'bg-gray-200 text-gray-500',
                                                                            'Intermedio' => 'bg-blue-100 text-blue-600',
                                                                            'Avanzado'   => 'bg-purple-100 text-purple-600',
                                                                            'Experto'    => 'bg-amber-100 text-amber-600',
                                                                            default      => 'bg-gray-100 text-gray-500',
                                                                        } ?> text-xs px-1.5 py-0.5 rounded-full font-semibold"><?= htmlspecialchars($oe['nivel']) ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                               -->       

                            <div class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm">
                                    <span class="text-gray-400">Postulado:</span>
                                    <span class="text-gray-700 font-medium ml-1"><?= tr_pg($p['fecha_postulacion']) ?></span>
                                </p>
                            </div>

                        </div>

                        <!-- Nota interna -->
                        <?php if ($p['notas_empresa']): ?>
                            <div class="px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-start gap-2">
                                <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span class="line-clamp-2"><?= htmlspecialchars($p['notas_empresa']) ?></span>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- ── FOOTER CARD ── -->
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center gap-2">

                        <!-- Dropdown Acción -->
                        <div class="relative flex-1">
                            <button onclick="toggleDropdown('<?= $dd_id ?>')"
                                class="w-full inline-flex items-center justify-between gap-2 text-sm font-medium px-3 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                    Acción
                                </span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div id="<?= $dd_id ?>"
                                class="hidden absolute bottom-full left-0 mb-1 w-52 bg-white border border-gray-200 rounded-xl shadow-xl z-30 py-1">
                                <?php
                                $opciones = [
                                    'Revisada'   => ['label' => 'Marcar como visto',  'color' => 'text-blue-600',   'hover' => 'hover:bg-blue-50',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>'],
                                    'Entrevista' => ['label' => 'Pre-seleccionar',     'color' => 'text-purple-600', 'hover' => 'hover:bg-purple-50', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                                    'Aceptada'   => ['label' => 'Aceptar postulante',  'color' => 'text-green-600',  'hover' => 'hover:bg-green-50',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                                    'Rechazada'  => ['label' => 'Rechazar postulante', 'color' => 'text-red-600',    'hover' => 'hover:bg-red-50',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                                    'Pendiente'  => ['label' => 'Volver a Pendiente',  'color' => 'text-yellow-600', 'hover' => 'hover:bg-yellow-50', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>'],
                                ];
                                foreach ($opciones as $est_val => $op):
                                    if ($est_val === $p['estado']) continue;
                                ?>
                                    <form method="POST">
                                        <input type="hidden" name="accion" value="cambiar_estado">
                                        <input type="hidden" name="id_postulacion" value="<?= $p['id_postulacion'] ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?= $est_val ?>">
                                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm <?= $op['color'] . ' ' . $op['hover'] ?> transition">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $op['icon'] ?></svg>
                                            <?= $op['label'] ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                                <div class="border-t border-gray-100 mt-1 pt-1">
                                    <button onclick="abrirNotas(<?= $p['id_postulacion'] ?>, `<?= htmlspecialchars(addslashes($p['notas_empresa'] ?? ''), ENT_QUOTES) ?>`); cerrarDropdown('<?= $dd_id ?>')"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Agregar / editar nota
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Ver perfil -->
                        <a href="ver-perfil-trabajador.php?id=<?= $p['id_persona'] ?>&from=postulantes-global"
                            class="inline-flex items-center gap-1.5 text-sm px-3 py-2 border border-gray-300 text-gray-600 bg-white hover:bg-gray-50 rounded-lg transition font-medium whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Perfil
                        </a>          
                        <!-- Contactar -->
                        <button onclick="abrirContacto('<?= htmlspecialchars($p['email']) ?>','<?= htmlspecialchars($p['telefono'] ?? '') ?>','<?= htmlspecialchars($nombre, ENT_QUOTES) ?>')"
                            class="inline-flex items-center gap-1.5 text-sm px-3 py-2 border border-cyan-300 text-cyan-700 bg-white hover:bg-cyan-50 rounded-lg transition font-medium whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Contactar
                        </button>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== MODAL OFERTA ===== -->
<div id="modalOferta" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 id="mo-titulo" class="text-lg font-bold text-gray-900 leading-tight"></h3>
                    <div id="mo-estado-oferta" class="mt-0.5"></div>
                </div>
            </div>
            <button onclick="cerrarOferta()" class="text-gray-400 hover:text-gray-600 transition ml-4 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">
            <div id="mo-chips" class="flex flex-wrap gap-2"></div>
            <div id="mo-salario-wrap" class="hidden bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span id="mo-salario" class="text-green-800 font-semibold text-sm"></span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Descripción</p>
                <p id="mo-desc" class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"></p>
            </div>
            <div id="mo-req-wrap" class="hidden">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Requisitos</p>
                <p id="mo-req" class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"></p>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-between items-center flex-shrink-0">
            <span class="text-xs text-gray-400">Publicada el <span id="mo-fecha-pub"></span></span>
            <div class="flex gap-3">
                <button onclick="cerrarOferta()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-100 transition">Cerrar</button>
                <a id="mo-link" href="#"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Ver postulantes
                </a>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONTACTAR -->
<div id="modalContacto" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Datos de contacto</h3>
            <button onclick="cerrarContacto()" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <p id="contacto-nombre" class="font-semibold text-gray-800"></p>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-400">Email</p><a id="contacto-email" href="#" class="text-sm font-medium text-cyan-600 hover:underline truncate block"></a>
                    </div>
                    <button onclick="copiar('contacto-email-txt')" class="flex-shrink-0 text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg></button>
                    <span id="contacto-email-txt" class="hidden"></span>
                </div>
                <div id="contacto-tel-wrap" class="hidden items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-400">Teléfono</p>
                        <p id="contacto-tel" class="text-sm font-medium text-gray-800"></p>
                    </div>
                    <button onclick="copiar('contacto-tel')" class="flex-shrink-0 text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg></button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end">
            <button onclick="cerrarContacto()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-100 transition">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL NOTAS -->
<div id="modalNotas" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Notas internas</h3>
            <button onclick="cerrarNotas()" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="guardar_notas">
            <input type="hidden" name="id_postulacion" id="notas-id">
            <div class="px-6 py-5">
                <p class="text-xs text-gray-400 mb-3">Solo visibles para tu empresa.</p>
                <textarea name="notas" id="notas-texto" rows="5"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 resize-none transition"
                    placeholder="Ej: Buen perfil técnico, pendiente entrevista..."></textarea>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                <button type="button" onclick="cerrarNotas()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<div id="toast" class="hidden fixed bottom-5 right-5 z-50 bg-gray-800 text-white text-sm px-5 py-3 rounded-xl shadow-lg"></div>

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
            <button type="button" onclick="cerrarModalSesion()" class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
            <a href="cerrar-session.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Sí, cerrar sesión
            </a>
        </div>
    </div>
</div>

<script>
    // ── Dropdowns ────────────────────────────────────────────────────────────
    function toggleDropdown(id) {
        const el = document.getElementById(id);
        const isHidden = el.classList.contains('hidden');
        document.querySelectorAll('[id^="ddg-"]').forEach(d => d.classList.add('hidden'));
        if (isHidden) el.classList.remove('hidden');
    }

    function cerrarDropdown(id) {
        document.getElementById(id)?.classList.add('hidden');
    }
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[id^="ddg-"]') && !e.target.closest('button[onclick^="toggleDropdown"]')) {
            document.querySelectorAll('[id^="ddg-"]').forEach(d => d.classList.add('hidden'));
        }
    });

    // ── Modal Sesión ──────────────────────────────────────────────────────────
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

    // ── Modal Oferta ──────────────────────────────────────────────────────────
    function abrirOferta(o) {
        document.getElementById('mo-titulo').textContent = o.titulo;
        document.getElementById('mo-fecha-pub').textContent = o.publicacion || '—';
        document.getElementById('mo-link').href = 'postulantes.php?id=' + o.id_oferta;

        const estadoColors = {
            'Activa': 'bg-green-100 text-green-700',
            'Pausada': 'bg-yellow-100 text-yellow-700',
            'Cerrada': 'bg-red-100 text-red-700'
        };
        const sc = estadoColors[o.estado] || 'bg-gray-100 text-gray-600';
        document.getElementById('mo-estado-oferta').innerHTML =
            `<span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full ${sc}">${o.estado}</span>`;

        const chips = document.getElementById('mo-chips');
        chips.innerHTML = '';
        const addChip = (txt, cls) => {
            if (!txt) return;
            const s = document.createElement('span');
            s.className = 'inline-flex items-center text-xs px-2.5 py-1 rounded-full border font-medium ' + cls;
            s.textContent = txt;
            chips.appendChild(s);
        };
        const loc = o.localidad ? o.localidad + ', ' + o.provincia : o.provincia;
        addChip(loc, 'bg-gray-100 text-gray-600 border-gray-200');
        addChip(o.tipo, 'bg-cyan-50 text-cyan-700 border-cyan-200');
        addChip(o.modalidad, 'bg-purple-50 text-purple-700 border-purple-200');
        addChip(o.especialidad, 'bg-orange-50 text-orange-700 border-orange-200');
        if (o.experiencia) addChip(o.experiencia + ' años de experiencia', 'bg-gray-100 text-gray-600 border-gray-200');
        if (o.vencimiento) addChip('Cierra: ' + o.vencimiento, 'bg-red-50 text-red-600 border-red-200');

        const salWrap = document.getElementById('mo-salario-wrap');
        if (o.sal_min || o.sal_max) {
            let txt = '';
            if (o.sal_min && o.sal_max) txt = '$' + Number(o.sal_min).toLocaleString('es-AR') + ' – $' + Number(o.sal_max).toLocaleString('es-AR') + ' ARS';
            else if (o.sal_min) txt = 'Desde $' + Number(o.sal_min).toLocaleString('es-AR') + ' ARS';
            else txt = 'Hasta $' + Number(o.sal_max).toLocaleString('es-AR') + ' ARS';
            document.getElementById('mo-salario').textContent = txt;
            salWrap.classList.remove('hidden');
            salWrap.classList.add('flex');
        } else {
            salWrap.classList.add('hidden');
            salWrap.classList.remove('flex');
        }

        document.getElementById('mo-desc').textContent = o.descripcion || 'Sin descripción.';
        const reqWrap = document.getElementById('mo-req-wrap');
        if (o.requisitos) {
            document.getElementById('mo-req').textContent = o.requisitos;
            reqWrap.classList.remove('hidden');
        } else reqWrap.classList.add('hidden');

        document.getElementById('modalOferta').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarOferta() {
        document.getElementById('modalOferta').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // ── Modal Contactar ───────────────────────────────────────────────────────
    function abrirContacto(email, tel, nombre) {
        document.getElementById('contacto-nombre').textContent = nombre;
        document.getElementById('contacto-email').textContent = email;
        document.getElementById('contacto-email').href = 'mailto:' + email;
        document.getElementById('contacto-email-txt').textContent = email;
        const tw = document.getElementById('contacto-tel-wrap');
        if (tel) {
            document.getElementById('contacto-tel').textContent = tel;
            tw.classList.remove('hidden');
            tw.classList.add('flex');
        } else tw.classList.add('hidden');
        document.getElementById('modalContacto').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarContacto() {
        document.getElementById('modalContacto').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // ── Modal Notas ───────────────────────────────────────────────────────────
    function abrirNotas(id, texto) {
        document.getElementById('notas-id').value = id;
        document.getElementById('notas-texto').value = texto;
        document.getElementById('modalNotas').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarNotas() {
        document.getElementById('modalNotas').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function copiar(id) {
        navigator.clipboard.writeText(document.getElementById(id).textContent)
            .then(() => toast('Copiado al portapapeles'));
    }

    function toast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 2500);
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            cerrarOferta();
            cerrarContacto();
            cerrarNotas();
            cerrarModalSesion();
        }
    });
    document.getElementById('modalOferta').addEventListener('click', function(e) {
        if (e.target === this) cerrarOferta();
    });
    document.getElementById('modalContacto').addEventListener('click', function(e) {
        if (e.target === this) cerrarContacto();
    });
    document.getElementById('modalNotas').addEventListener('click', function(e) {
        if (e.target === this) cerrarNotas();
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
        echo "<script>console.error('💥 {$m} — {$f} línea {$error['line']}');</script>";
    }
});
?>