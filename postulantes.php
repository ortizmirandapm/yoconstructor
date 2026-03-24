<?php
$page = 'ofertas-publicadas';
$pageTitle = 'Postulantes';
include("conexion.php");
include_once("notificaciones_helper.php");

$id_empresa = $_SESSION['idempresa'] ?? null;
$id_usuario = $_SESSION['idusuario'] ?? null;

if (!$id_empresa) {
    header("Location: login.php");
    exit;
}

$id_oferta = intval($_GET['id'] ?? 0);
if (!$id_oferta) {
    header("Location: ofertas-publicadas.php");
    exit;
}

$res_oferta = mysqli_query($conexion, "SELECT id_oferta, titulo FROM ofertas_laborales WHERE id_oferta = $id_oferta AND id_empresa = $id_empresa");
if (!$res_oferta || mysqli_num_rows($res_oferta) === 0) {
    header("Location: ofertas-publicadas.php");
    exit;
}
$oferta = mysqli_fetch_assoc($res_oferta);

// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion         = $_POST['accion'] ?? '';
    $id_postulacion = intval($_POST['id_postulacion'] ?? 0);
    $notas          = trim($_POST['notas'] ?? '');

    if ($id_postulacion > 0) {
        // Verificar que la postulación pertenece a esta empresa
        $check = mysqli_query($conexion, "SELECT p.id_postulacion, p.id_persona,
                CONCAT(per.nombre, ' ', per.apellido) AS nombre_trabajador
            FROM postulaciones p
            INNER JOIN ofertas_laborales o  ON p.id_oferta   = o.id_oferta
            INNER JOIN persona per          ON p.id_persona  = per.id_persona
            WHERE p.id_postulacion = $id_postulacion AND o.id_empresa = $id_empresa");

        if ($check && mysqli_num_rows($check) > 0) {
            $row_check       = mysqli_fetch_assoc($check);
            $nombre_trabajador = $row_check['nombre_trabajador'];

            if ($accion === 'cambiar_estado') {
                $nuevo_estado = $_POST['nuevo_estado'] ?? '';
                $estados_ok   = ['Pendiente', 'Revisada', 'Entrevista', 'Aceptada', 'Rechazada'];

                if (in_array($nuevo_estado, $estados_ok)) {
                    $stmt = mysqli_prepare($conexion, "UPDATE postulaciones SET estado = ?, fecha_actualizacion = NOW() WHERE id_postulacion = ?");
                    mysqli_stmt_bind_param($stmt, 'si', $nuevo_estado, $id_postulacion);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    notificar_estado_postulacion($conexion, $id_postulacion, $nuevo_estado);

                    // Auditoría solo cuando se acepta
                    if ($nuevo_estado === 'Aceptada') {
                        registrar_auditoria(
                            $conexion,
                            $id_usuario,
                            $id_empresa,
                            'aceptar_postulante',
                            'postulacion',
                            $id_postulacion,
                            "Aceptó a $nombre_trabajador en oferta: {$oferta['titulo']}"
                        );
                    }
                }

            } elseif ($accion === 'guardar_notas') {
                $stmt = mysqli_prepare($conexion, "UPDATE postulaciones SET notas_empresa = ? WHERE id_postulacion = ?");
                mysqli_stmt_bind_param($stmt, 'si', $notas, $id_postulacion);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }

    header("Location: postulantes.php?id=$id_oferta" . (isset($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''));
    exit;
}

// Filtro por estado
$filtro_estado   = $_GET['estado'] ?? '';
$estados_validos = ['Pendiente', 'Revisada', 'Entrevista', 'Aceptada', 'Rechazada'];
$where_estado    = '';
if ($filtro_estado && in_array($filtro_estado, $estados_validos)) {
    $fe = mysqli_real_escape_string($conexion, $filtro_estado);
    $where_estado = "AND p.estado = '$fe'";
}

// Contadores por estado
$res_counts = mysqli_query($conexion, "SELECT p.estado, COUNT(*) as total
    FROM postulaciones p INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
    WHERE p.id_oferta = $id_oferta AND o.id_empresa = $id_empresa GROUP BY p.estado");
$counts = ['Pendiente' => 0, 'Revisada' => 0, 'Entrevista' => 0, 'Aceptada' => 0, 'Rechazada' => 0];
while ($c = mysqli_fetch_assoc($res_counts)) $counts[$c['estado']] = intval($c['total']);
$total = array_sum($counts);

// Query principal
$sql = "SELECT p.id_postulacion, p.estado, p.fecha_postulacion, p.fecha_actualizacion,
            p.mensaje, p.cv_adjunto, p.notas_empresa,
            per.id_persona, per.nombre, per.apellido, per.dni,
            per.anios_experiencia, per.descripcion_persona,
            per.imagen_perfil, per.telefono, per.nombre_titulo, per.curriculum_pdf,
            per.fecha_nacimiento,
            u.email
        FROM postulaciones p
        INNER JOIN persona per ON p.id_persona = per.id_persona
        INNER JOIN users u     ON u.id_persona = per.id_persona
        WHERE p.id_oferta = $id_oferta $where_estado
        ORDER BY p.fecha_postulacion DESC";

$res = mysqli_query($conexion, $sql);
$postulantes = [];
while ($r = mysqli_fetch_assoc($res)) $postulantes[] = $r;

// Especialidades por persona (con nivel y es_principal)
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
    'Revisada'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'border' => 'border-blue-300',   'dot' => 'bg-blue-400',   'label' => 'Revisada'],
    'Entrevista' => ['bg' => 'bg-purple-100',  'text' => 'text-purple-800', 'border' => 'border-purple-300', 'dot' => 'bg-purple-500', 'label' => 'Pre-seleccionado'],
    'Aceptada'   => ['bg' => 'bg-green-100',   'text' => 'text-green-800',  'border' => 'border-green-300',  'dot' => 'bg-green-500',  'label' => 'Aceptada'],
    'Rechazada'  => ['bg' => 'bg-red-100',     'text' => 'text-red-800',    'border' => 'border-red-300',    'dot' => 'bg-red-400',    'label' => 'Rechazada'],
];

function nivel_badge($nivel)
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
function foto_src($img)
{
    return !empty($img) ? 'uploads/perfil/' . $img : './img/profile.png';
}
function cv_src($cv)
{
    if (!empty($cv)) return str_starts_with($cv, 'uploads/') ? $cv : 'uploads/cv/' . $cv;
    return null;
}
function tr_fecha($fecha)
{
    $tz       = new DateTimeZone('America/Argentina/Buenos_Aires');
    $ahora    = new DateTime('now', $tz);
    $dt_fecha = new DateTime($fecha, $tz);
    $diff     = $ahora->getTimestamp() - $dt_fecha->getTimestamp();

    if ($diff < 60)    return 'Hace un momento';
    if ($diff < 3600)  return 'Hace ' . max(1, floor($diff / 60)) . ' min';
    if ($diff < 7200)  return 'Hace 1 hora';
    if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' horas';

    $hoy    = new DateTime('today');
    $ayer   = new DateTime('yesterday');
    $dt_dia = new DateTime($dt_fecha->format('Y-m-d'));

    if ($dt_dia == $hoy)  return 'Hoy';
    if ($dt_dia == $ayer) return 'Ayer';

    $dias = (int) $hoy->diff($dt_dia)->days;
    if ($dias < 7)  return 'Hace ' . $dias . ' días';
    if ($dias < 14) return 'Hace 1 semana';
    if ($dias < 30) return 'Hace ' . floor($dias / 7) . ' semanas';
    if ($dias < 60) return 'Hace 1 mes';
    return $dt_fecha->format('d/m/Y');
}

include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-10">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-400 mb-1">
                <a href="ofertas-publicadas.php" class="hover:text-cyan-600 transition">Mis ofertas</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-600 font-medium truncate max-w-xs"><?= htmlspecialchars($oferta['titulo']) ?></span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Postulantes</h1>
            <p class="text-gray-500 mt-0.5 text-sm"><?= $total ?> postulante<?= $total !== 1 ? 's' : '' ?> en total</p>
        </div>
        <a href="ofertas-publicadas.php"
            class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-lg transition self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver a ofertas
        </a>
    </div>

    <!-- Filtros pills -->
    <div class="flex flex-wrap gap-2 mb-8">
        <?php
        $pills = [
            ''           => ['label' => 'Todos',           'cnt' => $total,                'active' => 'bg-teal-600 text-white border-teal-600',   'dot' => ''],
            'Pendiente'  => ['label' => 'Pendiente',        'cnt' => $counts['Pendiente'],  'active' => 'bg-yellow-500 text-white border-yellow-500', 'dot' => 'bg-yellow-400'],
            'Revisada'   => ['label' => 'Revisada',         'cnt' => $counts['Revisada'],   'active' => 'bg-blue-500 text-white border-blue-500',    'dot' => 'bg-blue-400'],
            'Entrevista' => ['label' => 'Pre-seleccionado', 'cnt' => $counts['Entrevista'], 'active' => 'bg-purple-500 text-white border-purple-500', 'dot' => 'bg-purple-500'],
            'Aceptada'   => ['label' => 'Aceptada',         'cnt' => $counts['Aceptada'],   'active' => 'bg-green-500 text-white border-green-500',  'dot' => 'bg-green-500'],
            'Rechazada'  => ['label' => 'Rechazada',        'cnt' => $counts['Rechazada'],  'active' => 'bg-red-500 text-white border-red-500',      'dot' => 'bg-red-400'],
        ];
        foreach ($pills as $val => $pi):
            $activo = $filtro_estado === $val;
            $href   = 'postulantes.php?id=' . $id_oferta . ($val ? '&estado=' . $val : '');
        ?>
            <a href="<?= $href ?>"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium border transition
                <?= $activo ? $pi['active'] : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' ?>">
                <?php if ($val): ?>
                    <span class="w-2 h-2 rounded-full flex-shrink-0 <?= $activo ? 'bg-white opacity-70' : $pi['dot'] ?>"></span>
                <?php endif; ?>
                <?= $pi['label'] ?>
                <span class="<?= $activo ? 'bg-white bg-opacity-25 text-white' : 'bg-gray-100 text-gray-600' ?> text-xs font-bold px-1.5 py-0.5 rounded-full">
                    <?= $pi['cnt'] ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Cards -->
    <?php if (empty($postulantes)): ?>
        <div class="bg-white border border-dashed border-gray-300 rounded-xl p-16 text-center">
            <svg class="w-14 h-14 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-gray-400 font-medium text-lg mb-1">Sin postulantes<?= $filtro_estado ? ' en este estado' : '' ?></p>
            <p class="text-gray-400 text-sm">Cuando alguien se postule aparecerá aquí</p>
        </div>

    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($postulantes as $p):
                $cfg        = $estado_cfg[$p['estado']] ?? $estado_cfg['Pendiente'];
                $foto       = foto_src($p['imagen_perfil']);
                $cv_file    = !empty($p['curriculum_pdf']) ? $p['curriculum_pdf'] : ($p['cv_adjunto'] ?? '');
                $cv         = cv_src($cv_file);
                $nombre     = ucwords(strtolower($p['nombre'] . ' ' . $p['apellido']));
                $esps       = $especialidades_map[$p['id_persona']] ?? [];
                $esp_main   = $esps[0] ?? null;
                $otras_esps = array_slice($esps, 1);
                $dd_id      = 'dd-' . $p['id_postulacion'];
            ?>
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow flex flex-col">

                    <!-- Header card -->
                    <div class="px-5 pt-5 pb-4 border-b border-gray-100">

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

                    <!-- Cuerpo -->
                    <div class="px-5 py-4 flex-1 space-y-4">

                        <?php if ($p['descripcion_persona']): ?>
                            <p class="text-sm text-gray-600 leading-relaxed line-clamp-3"><?= htmlspecialchars($p['descripcion_persona']) ?></p>
                        <?php else: ?>
                            <p class="text-sm text-gray-400 italic">Sin descripción</p>
                        <?php endif; ?>

                        <div class="space-y-2.5">

                            <div class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm">
                                    <span class="text-gray-400">Experiencia:</span>
                                    <span class="text-gray-700 font-medium ml-1">
                                        <?= $p['anios_experiencia'] ? intval($p['anios_experiencia']) . ' año' . ($p['anios_experiencia'] != 1 ? 's' : '') : 'No especificada' ?>
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

                            <div class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm">
                                    <span class="text-gray-400">Postulado:</span>
                                    <span class="text-gray-700 font-medium ml-1"><?= tr_fecha($p['fecha_postulacion']) ?></span>
                                </p>
                            </div>

                        </div>

                        <?php if ($p['notas_empresa']): ?>
                            <div class="px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-start gap-2">
                                <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span class="line-clamp-2"><?= htmlspecialchars($p['notas_empresa']) ?></span>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Footer card -->
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center gap-2">

                        <!-- Dropdown acciones -->
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
                                    'Revisada'   => ['label' => 'Marcar como visto',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>', 'color' => 'text-blue-600',   'hover' => 'hover:bg-blue-50'],
                                    'Entrevista' => ['label' => 'Pre-seleccionar',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',                                                                                                                                                                                                                                                                   'color' => 'text-purple-600', 'hover' => 'hover:bg-purple-50'],
                                    'Aceptada'   => ['label' => 'Aceptar postulante',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',                                                                                                                                                                                                                                                                                                                 'color' => 'text-green-600',  'hover' => 'hover:bg-green-50'],
                                    'Rechazada'  => ['label' => 'Rechazar postulante', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',                                                                                                                                                                                                                                                                                        'color' => 'text-red-600',    'hover' => 'hover:bg-red-50'],
                                    'Pendiente'  => ['label' => 'Volver a Pendiente',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',                                                                                                                                                                                                                                                 'color' => 'text-yellow-600', 'hover' => 'hover:bg-yellow-50'],
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
                        <a href="ver-perfil-trabajador.php?id=<?= $p['id_persona'] ?>&from=postulantes&oferta=<?= $id_oferta ?>"
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
            <p id="contacto-nombre" class="font-semibold text-gray-800"></p>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-400">Email</p>
                        <a id="contacto-email" href="#" class="text-sm font-medium text-cyan-600 hover:underline truncate block"></a>
                    </div>
                    <button onclick="copiar('contacto-email-txt')" class="flex-shrink-0 text-gray-400 hover:text-gray-600" title="Copiar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
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
                    <button onclick="copiar('contacto-tel')" class="flex-shrink-0 text-gray-400 hover:text-gray-600" title="Copiar">
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

<!-- MODAL NOTAS -->
<div id="modalNotas" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Notas internas</h3>
            <button onclick="cerrarNotas()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="guardar_notas">
            <input type="hidden" name="id_postulacion" id="notas-id">
            <div class="px-6 py-5">
                <p class="text-xs text-gray-400 mb-3">Estas notas solo son visibles para tu empresa.</p>
                <textarea name="notas" id="notas-texto" rows="5"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 resize-none"
                    placeholder="Ej: Candidato con buen perfil, pendiente entrevista técnica..."></textarea>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                <button type="button" onclick="cerrarNotas()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar nota
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
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
    function toggleDropdown(id) {
        const el = document.getElementById(id);
        const isHidden = el.classList.contains('hidden');
        document.querySelectorAll('[id^="dd-"]').forEach(d => d.classList.add('hidden'));
        if (isHidden) el.classList.remove('hidden');
    }
    function cerrarDropdown(id) {
        document.getElementById(id)?.classList.add('hidden');
    }
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[id^="dd-"]') && !e.target.closest('button[onclick^="toggleDropdown"]')) {
            document.querySelectorAll('[id^="dd-"]').forEach(d => d.classList.add('hidden'));
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
    function abrirContacto(email, tel, nombre) {
        document.getElementById('contacto-nombre').textContent = nombre;
        document.getElementById('contacto-email').textContent  = email;
        document.getElementById('contacto-email').href         = 'mailto:' + email;
        document.getElementById('contacto-email-txt').textContent = email;
        const telWrap = document.getElementById('contacto-tel-wrap');
        if (tel) {
            document.getElementById('contacto-tel').textContent = tel;
            telWrap.classList.remove('hidden');
            telWrap.classList.add('flex');
        } else {
            telWrap.classList.add('hidden');
        }
        document.getElementById('modalContacto').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function cerrarContacto() {
        document.getElementById('modalContacto').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    function abrirNotas(id, texto) {
        document.getElementById('notas-id').value    = id;
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
            .then(() => mostrarToast('Copiado al portapapeles'));
    }
    function mostrarToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 2500);
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { cerrarContacto(); cerrarNotas(); cerrarModalSesion(); }
    });
    document.getElementById('modalContacto').addEventListener('click', function(e) { if (e.target === this) cerrarContacto(); });
    document.getElementById('modalNotas').addEventListener('click',    function(e) { if (e.target === this) cerrarNotas(); });
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