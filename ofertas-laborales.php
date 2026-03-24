<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Argentina/Buenos_Aires');
include_once("conexion.php");


$search           = trim($_GET['q'] ?? '');
$filtro_esp       = intval($_GET['especialidad'] ?? 0);
$filtro_provincia = intval($_GET['provincia'] ?? 0);
$filtro_contrato  = trim($_GET['contrato'] ?? '');
$ver_oferta       = intval($_GET['ver'] ?? 0);

$especialidades = [];
$res_esp = mysqli_query($conexion, "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
if (!$res_esp) die("<b>ERROR esp:</b> " . mysqli_error($conexion));
while ($r = mysqli_fetch_assoc($res_esp)) $especialidades[] = $r;

$provincias = [];
$res_prov = mysqli_query($conexion, "SELECT id_provincia, nombre FROM provincias ORDER BY nombre ASC");
if (!$res_prov) die("<b>ERROR prov:</b> " . mysqli_error($conexion));
while ($r = mysqli_fetch_assoc($res_prov)) $provincias[] = $r;

$oferta_detalle = null;
if ($ver_oferta > 0) {
    $vid = intval($ver_oferta);
    $sql_det = "SELECT o.*, e.nombre_especialidad,
                       p.nombre AS nombre_provincia,
                       l.nombre_localidad,
                       emp.nombre_empresa,
                       emp.descripcion_empresa AS desc_empresa,
                       emp.logo AS logo_empresa,
                       emp.email_contacto,
                       emp.telefono,
                       emp.domicilio,
                       (SELECT COUNT(*) FROM postulaciones po WHERE po.id_oferta = o.id_oferta) AS total_postulantes
                FROM ofertas_laborales o
                LEFT JOIN especialidades e ON o.id_especialidad = e.id_especialidad
                LEFT JOIN provincias p     ON o.id_provincia    = p.id_provincia
                LEFT JOIN localidades l    ON o.id_localidad    = l.id_localidad
                LEFT JOIN empresa emp      ON o.id_empresa      = emp.id_empresa
                WHERE o.id_oferta = $vid AND o.estado = 'Activa'";
    $res_det = mysqli_query($conexion, $sql_det);
    if ($res_det) $oferta_detalle = mysqli_fetch_assoc($res_det);
}

// Trabajador logueado para detectar postulaciones en el listado
$id_persona_sesion = intval($_SESSION['idpersona'] ?? 0);

// Preferencias de ubicación del trabajador
$pref_provincia  = 0;
$pref_localidad  = 0;
if ($id_persona_sesion > 0) {
    $res_pref = mysqli_query($conexion,
        "SELECT id_provincia_preferencia, id_localidad_preferencia
         FROM persona WHERE id_persona = $id_persona_sesion LIMIT 1");
    if ($res_pref && $row_pref = mysqli_fetch_assoc($res_pref)) {
        $pref_provincia = intval($row_pref['id_provincia_preferencia'] ?? 0);
        $pref_localidad = intval($row_pref['id_localidad_preferencia'] ?? 0);
    }
}

$where = ["o.estado = 'Activa'"];
if ($search !== '') {
    $s = mysqli_real_escape_string($conexion, $search);
    $where[] = "(o.titulo LIKE '%$s%' OR o.descripcion LIKE '%$s%' OR emp.nombre_empresa LIKE '%$s%')";
}
if ($filtro_esp > 0)       $where[] = "o.id_especialidad = " . intval($filtro_esp);
if ($filtro_provincia > 0) $where[] = "o.id_provincia = " . intval($filtro_provincia);
if ($filtro_contrato !== '') {
    $fc = mysqli_real_escape_string($conexion, $filtro_contrato);
    $where[] = "o.tipo_contrato = '$fc'";
}
$where_sql = implode(' AND ', $where);

$sql = "SELECT o.id_oferta, o.titulo, o.tipo_contrato, o.modalidad,
               o.salario_min, o.salario_max, o.id_provincia, o.id_localidad,
               o.fecha_publicacion, o.fecha_vencimiento, o.id_empresa,
               e.nombre_especialidad,
               p.nombre AS nombre_provincia,
               l.nombre_localidad,
               emp.nombre_empresa,
               emp.logo AS logo_empresa,
               (SELECT COUNT(*) FROM postulaciones po WHERE po.id_oferta = o.id_oferta) AS total_postulantes,
               (SELECT COUNT(*) FROM postulaciones pp WHERE pp.id_oferta = o.id_oferta AND pp.id_persona = " . ($id_persona_sesion ?: 0) . ") AS ya_postulado
        FROM ofertas_laborales o
        LEFT JOIN especialidades e ON o.id_especialidad = e.id_especialidad
        LEFT JOIN provincias p     ON o.id_provincia    = p.id_provincia
        LEFT JOIN localidades l    ON o.id_localidad    = l.id_localidad
        LEFT JOIN empresa emp      ON o.id_empresa      = emp.id_empresa
        WHERE $where_sql
        ORDER BY o.fecha_publicacion DESC";

$result = mysqli_query($conexion, $sql);
if (!$result) die("<b>ERROR listado:</b> " . mysqli_error($conexion) . "<br><pre>" . $sql . "</pre>");
$ofertas = [];
if ($result) while ($r = mysqli_fetch_assoc($result)) $ofertas[] = $r;

$tipos_contrato = ['Tiempo completo', 'Medio tiempo', 'Por proyecto', 'Pasantía', 'Temporal'];
$ic = "w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition";

function tiempo_relativo($fecha) {
    $tz       = new DateTimeZone('America/Argentina/Buenos_Aires');
    $ahora    = new DateTime('now', $tz);
    $dt_fecha = new DateTime($fecha, $tz);
    $diff     = $ahora->getTimestamp() - $dt_fecha->getTimestamp();

    if ($diff < 60)    return "Hace un momento";
    if ($diff < 3600)  return "Hace " . max(1, floor($diff / 60)) . " min";
    if ($diff < 7200)  return "Hace 1 hora";
    if ($diff < 86400) return "Hace " . floor($diff / 3600) . " horas";

    $hoy    = new DateTime('today', $tz);
    $ayer   = new DateTime('yesterday', $tz);
    $dt_dia = new DateTime($dt_fecha->format('Y-m-d'), $tz);

    if ($dt_dia == $hoy)  return "Hoy";
    if ($dt_dia == $ayer) return "Ayer";

    $dias = (int) $hoy->diff($dt_dia)->days;
    if ($dias < 7)  return "Hace " . $dias . " días";
    if ($dias < 14) return "Hace 1 semana";
    if ($dias < 30) return "Hace " . floor($dias / 7) . " semanas";
    if ($dias < 60) return "Hace 1 mes";
    return $dt_fecha->format("d/m/Y");
}

$svg_pin  = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
$svg_cal  = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
$svg_bag  = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
$svg_usr  = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
$svg_tool = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
$svg_mail = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
$svg_doc  = '<svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
$svg_req  = '<svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
$svg_chk  = '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ofertas laborales - YoConstructor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</head>
<body class="bg-white text-gray-800 font-sans antialiased flex flex-col min-h-screen">

<?php include_once("navbar-trabajador.php"); ?>

<nav class="w-full px-6 py-3 bg-gray-50 border-b border-gray-200">
    <ol class="flex items-center space-x-2 text-sm text-gray-500 max-w-7xl mx-auto">
        <li><a href="index.php" class="hover:text-blue-600 transition">Inicio</a></li>
        <li><span class="text-gray-300">/</span></li>
        <?php if ($oferta_detalle): ?>
            <li><a href="ofertas-laborales.php" class="hover:text-blue-600 transition">Ofertas laborales</a></li>
            <li><span class="text-gray-300">/</span></li>
            <li class="text-gray-700 font-semibold truncate max-w-xs"><?= htmlspecialchars($oferta_detalle['titulo']) ?></li>
        <?php else: ?>
            <li class="text-gray-700 font-semibold">Ofertas laborales</li>
        <?php endif; ?>
    </ol>
</nav>

<?php if ($oferta_detalle): ?>
<!-- ===== VISTA DETALLE ===== -->
<main class="max-w-5xl mx-auto px-4 py-10 flex-1 w-full">
    <a href="ofertas-laborales.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-blue-600 mb-6 transition font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver al listado
    </a>

    <?php if (isset($_GET['exito']) && $_GET['exito'] === 'postulado'): ?>
    <div class="mb-6 flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800">
        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-semibold">¡Te postulaste con éxito! La empresa revisará tu perfil.</p>
    </div>
    <?php elseif (isset($_GET['error'])): ?>
    <div class="mb-6 flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-semibold">
            <?php
            $errores = [
                'ya_postulado'         => 'Ya te habías postulado a esta oferta.',
                'oferta_no_disponible' => 'Esta oferta ya no está disponible.',
                'fallo'                => 'Ocurrió un error, intentá de nuevo.',
            ];
            echo $errores[$_GET['error']] ?? 'Ocurrió un error inesperado.';
            ?>
        </p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Columna principal -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Header oferta -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 rounded-xl bg-gray-100 border border-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                        <?php if (!empty($oferta_detalle['logo_empresa'])): ?>
                            <img src="<?= htmlspecialchars($oferta_detalle['logo_empresa']) ?>" alt="Logo" class="w-full h-full object-cover">
                        <?php else: ?>
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl font-extrabold text-gray-900"><?= htmlspecialchars($oferta_detalle['titulo']) ?></h1>
                        <p class="text-blue-600 font-semibold mt-1"><?= htmlspecialchars($oferta_detalle['nombre_empresa'] ?? 'Empresa confidencial') ?></p>
                        <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-500">
                            <?php if ($oferta_detalle['nombre_provincia']): ?>
                            <span class="flex items-center gap-1.5">
                                <?= $svg_pin ?>
                                <?= htmlspecialchars($oferta_detalle['nombre_localidad'] ? $oferta_detalle['nombre_localidad'].', '.$oferta_detalle['nombre_provincia'] : $oferta_detalle['nombre_provincia']) ?>
                            </span>
                            <?php
                            // Badge "fuera de tu zona" solo para trabajadores con preferencia configurada
                            $es_fuera_zona = false;
                            if ($id_persona_sesion > 0 && $pref_provincia > 0) {
                                $prov_oferta = intval($oferta_detalle['id_provincia'] ?? 0);
                                $loc_oferta  = intval($oferta_detalle['id_localidad'] ?? 0);
                                if ($pref_localidad > 0 && $loc_oferta > 0) {
                                    $es_fuera_zona = ($loc_oferta !== $pref_localidad);
                                } else {
                                    $es_fuera_zona = ($prov_oferta !== $pref_provincia);
                                }
                            }
                            if ($es_fuera_zona): ?>
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Fuera de tu zona preferida
                            </span>
                            <?php endif; ?>
                            <?php endif; ?>
                            <span class="flex items-center gap-1.5">
                                <?= $svg_cal ?>
                                <?= tiempo_relativo($oferta_detalle['fecha_publicacion']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mt-5 pt-5 border-t border-gray-100">
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full border border-blue-100">
                        <?= $svg_bag ?> <?= htmlspecialchars($oferta_detalle['tipo_contrato']) ?>
                    </span>
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <?= htmlspecialchars($oferta_detalle['modalidad']) ?>
                    </span>
                    <?php if ($oferta_detalle['nombre_especialidad']): ?>
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 text-orange-700 text-xs font-semibold rounded-full border border-orange-100">
                        <?= $svg_tool ?> <?= htmlspecialchars($oferta_detalle['nombre_especialidad']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($oferta_detalle['experiencia_requerida']): ?>
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        <?= $oferta_detalle['experiencia_requerida'] ?> año(s) exp.
                    </span>
                    <?php endif; ?>
                    <?php if ($oferta_detalle['fecha_vencimiento']): ?>
                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-600 text-xs font-semibold rounded-full border border-red-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Cierra: <?= date('d/m/Y', strtotime($oferta_detalle['fecha_vencimiento'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Salario -->
            <?php if ($oferta_detalle['salario_min'] || $oferta_detalle['salario_max']): ?>
            <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-semibold text-green-800">Remuneración</p>
                </div>
                <p class="text-2xl font-extrabold text-green-700">
                    <?php if ($oferta_detalle['salario_min'] && $oferta_detalle['salario_max']): ?>
                        $<?= number_format($oferta_detalle['salario_min'],0,',','.') ?> – $<?= number_format($oferta_detalle['salario_max'],0,',','.') ?>
                    <?php elseif ($oferta_detalle['salario_min']): ?>
                        Desde $<?= number_format($oferta_detalle['salario_min'],0,',','.') ?>
                    <?php else: ?>
                        Hasta $<?= number_format($oferta_detalle['salario_max'],0,',','.') ?>
                    <?php endif; ?>
                    <span class="text-sm font-medium text-green-600">ARS</span>
                </p>
            </div>
            <?php endif; ?>

            <!-- Descripción -->
            <?php if (!empty($oferta_detalle['descripcion'])): ?>
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <?= $svg_doc ?> Descripción del puesto
                </h2>
                <div class="text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($oferta_detalle['descripcion'])) ?></div>
            </div>
            <?php endif; ?>

            <!-- Requisitos -->
            <?php if (!empty($oferta_detalle['requisitos'])): ?>
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <?= $svg_req ?> Requisitos
                </h2>
                <div class="text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($oferta_detalle['requisitos'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Columna lateral -->
        <div class="space-y-5">

            <!-- Botón postularme -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm sticky top-24">
                <?php
                $logueado      = isset($_SESSION['idusuario']);
                $tipo_sesion   = intval($_SESSION['tipo'] ?? 0);
                $es_trabajador = $logueado && $tipo_sesion == 2;
                $es_empresa    = $logueado && $tipo_sesion == 1;

                $ya_postulado = false;
                if ($es_trabajador) {
                    $id_usu = intval($_SESSION['idpersona'] ?? 0);
                    $chk = mysqli_query($conexion, "SELECT id_postulacion FROM postulaciones WHERE id_oferta = $ver_oferta AND id_persona = $id_usu");
                    $ya_postulado = $chk && mysqli_num_rows($chk) > 0;
                }
                ?>

                <?php if ($ya_postulado): ?>
                    <div class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-xl">
                        <?= str_replace('w-5 h-5', 'w-6 h-6 text-green-600', $svg_chk) ?>
                        <div>
                            <p class="text-sm font-semibold text-green-800">Ya te postulaste</p>
                            <p class="text-xs text-green-600 mt-0.5">La empresa revisará tu perfil</p>
                        </div>
                    </div>

                <?php elseif ($es_trabajador): ?>
                    <p class="text-xs text-gray-500 text-center mb-3 flex items-center justify-center gap-1">
                        <?= $svg_usr ?>
                        <span><?= intval($oferta_detalle['total_postulantes']) ?> persona(s) ya se postuló</span>
                    </p>
                    <form method="POST" action="postular.php">
                        <input type="hidden" name="id_oferta" value="<?= $ver_oferta ?>">
                        <button type="submit"
                            class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm px-5 py-4 rounded-xl transition shadow-md hover:shadow-lg">
                            <?= $svg_chk ?>
                            Postularme ahora
                        </button>
                    </form>

                <?php elseif ($es_empresa): ?>
                    <div class="flex items-center gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs text-amber-700 font-medium">Las empresas no pueden postularse a ofertas.</p>
                    </div>

                <?php else: ?>
                    <p class="text-sm text-gray-600 text-center mb-4">Iniciá sesión para postularte a esta oferta</p>
                    <a href="login.php?redirect=ofertas-laborales.php?ver=<?= $ver_oferta ?>"
                        class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-3.5 rounded-xl transition mb-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Iniciar sesión
                    </a>
                    <a href="registrarme.php"
                        class="w-full flex items-center justify-center gap-2 bg-white border border-blue-100 hover:bg-blue-50 text-blue-600 font-semibold text-sm px-5 py-3 rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Crear cuenta gratis
                    </a>
                <?php endif; ?>
            </div>

            <!-- Info empresa -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 mb-4">Sobre la empresa</h3>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 border border-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                        <?php if (!empty($oferta_detalle['logo_empresa'])): ?>
                            <img src="<?= htmlspecialchars($oferta_detalle['logo_empresa']) ?>" alt="Logo" class="w-full h-full object-cover">
                        <?php else: ?>
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($oferta_detalle['nombre_empresa'] ?? 'Empresa confidencial') ?></p>
                </div>
                <?php if (!empty($oferta_detalle['desc_empresa'])): ?>
                <p class="text-xs text-gray-500 leading-relaxed mb-3"><?= htmlspecialchars($oferta_detalle['desc_empresa']) ?></p>
                <?php endif; ?>
                <?php if (!empty($oferta_detalle['email_contacto'])): ?>
                <p class="flex items-center gap-1.5 text-xs text-gray-500 mt-1"><?= $svg_mail ?> <?= htmlspecialchars($oferta_detalle['email_contacto']) ?></p>
                <?php endif; ?>
                <?php if (!empty($oferta_detalle['domicilio'])): ?>
                <p class="flex items-center gap-1.5 text-xs text-gray-500 mt-1"><?= $svg_pin ?> <?= htmlspecialchars($oferta_detalle['domicilio']) ?></p>
                <?php endif; ?>
                <a href="perfil-empresa-publica.php?id=<?= intval($oferta_detalle['id_empresa']) ?>"
                    class="flex items-center justify-center gap-2 w-full mt-4 text-xs font-semibold text-gray-700 border border-gray-300 hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50 px-4 py-2.5 rounded-xl transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/></svg>
                    Ver perfil completo
                </a>
            </div>

        </div>
    </div>
</main>

<?php else: ?>
<!-- ===== VISTA LISTADO ===== -->
<main class="max-w-7xl mx-auto px-4 py-10 flex-1 w-full">

    <div class="mb-10 text-center">
        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight tracking-tight">
            Encontrá tu próximo <span class="text-blue-600">trabajo</span>
        </h1>
        <p class="text-gray-500 mt-3 text-lg max-w-xl mx-auto">Explorá las ofertas laborales del sector construcción en Argentina.</p>
    </div>

    <!-- Buscador -->
    <form method="GET" action="" class="max-w-2xl mx-auto mb-8">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Buscar por título, descripción o empresa..."
                    class="w-full pl-11 pr-4 py-3 bg-white border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm transition">
                <?php if ($filtro_esp):       echo '<input type="hidden" name="especialidad" value="'.intval($filtro_esp).'">'; endif; ?>
                <?php if ($filtro_provincia): echo '<input type="hidden" name="provincia" value="'.intval($filtro_provincia).'">'; endif; ?>
                <?php if ($filtro_contrato):  echo '<input type="hidden" name="contrato" value="'.htmlspecialchars($filtro_contrato).'">'; endif; ?>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl transition shadow-md">Buscar</button>
        </div>
    </form>

    <!-- Tags especialidades -->
    <div class="flex flex-wrap justify-center gap-2 mb-10">
        <?php foreach (array_slice($especialidades, 0, 8) as $esp): ?>
        <a href="?especialidad=<?= intval($esp['id_especialidad']) ?><?= $search ? '&q='.urlencode($search) : '' ?>"
            class="px-3 py-1.5 text-xs font-semibold rounded-full border transition <?= $filtro_esp == $esp['id_especialidad'] ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400 hover:text-blue-600' ?>">
            <?= htmlspecialchars($esp['nombre_especialidad']) ?>
        </a>
        <?php endforeach; ?>
        <?php if ($filtro_esp || $filtro_provincia || $filtro_contrato || $search): ?>
        <a href="ofertas-laborales.php" class="px-3 py-1.5 text-xs font-semibold rounded-full bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition">✕ Limpiar</a>
        <?php endif; ?>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">

        <!-- Sidebar filtros -->
        <aside class="lg:w-64 flex-shrink-0">
            <form method="GET" action="" class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 space-y-5 sticky top-24">
                <h2 class="text-sm font-bold text-gray-900">Filtros</h2>
                <?php if ($search): echo '<input type="hidden" name="q" value="'.htmlspecialchars($search).'">'; endif; ?>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Especialidad</label>
                    <select name="especialidad" class="<?= $ic ?>">
                        <option value="">Todas</option>
                        <?php foreach ($especialidades as $esp): ?>
                        <option value="<?= intval($esp['id_especialidad']) ?>" <?= $filtro_esp == $esp['id_especialidad'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($esp['nombre_especialidad']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Provincia</label>
                    <select name="provincia" class="<?= $ic ?>">
                        <option value="">Todas</option>
                        <?php foreach ($provincias as $prov): ?>
                        <option value="<?= intval($prov['id_provincia']) ?>" <?= $filtro_provincia == $prov['id_provincia'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prov['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Tipo de contrato</label>
                    <select name="contrato" class="<?= $ic ?>">
                        <option value="">Todos</option>
                        <?php foreach ($tipos_contrato as $tc): ?>
                        <option value="<?= $tc ?>" <?= $filtro_contrato === $tc ? 'selected' : '' ?>><?= $tc ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-2 pt-1">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 rounded-xl transition shadow-sm">Aplicar filtros</button>
                    <a href="ofertas-laborales.php" class="w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium py-2.5 rounded-xl transition">Limpiar</a>
                </div>
            </form>
        </aside>

        <!-- Cards -->
        <div class="flex-1">
            <p class="text-sm text-gray-500 mb-5">
                <span class="font-bold text-gray-900"><?= count($ofertas) ?></span>
                oferta<?= count($ofertas) !== 1 ? 's' : '' ?> encontrada<?= count($ofertas) !== 1 ? 's' : '' ?>
            </p>

            <?php if (empty($ofertas)): ?>
            <div class="bg-white border border-dashed border-gray-200 rounded-2xl p-16 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500 font-semibold">No encontramos ofertas con esos criterios.</p>
                <a href="ofertas-laborales.php" class="inline-block mt-3 text-blue-600 hover:underline text-sm font-medium">Ver todas las ofertas</a>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($ofertas as $o): ?>
                <div class="group relative bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-blue-600 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>

                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <!-- Logo -->
                            <div class="w-12 h-12 rounded-xl bg-gray-100 border border-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                <?php if (!empty($o['logo_empresa'])): ?>
                                    <img src="<?= htmlspecialchars($o['logo_empresa']) ?>" alt="Logo" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 text-base group-hover:text-blue-600 transition-colors truncate">
                                            <?= htmlspecialchars($o['titulo']) ?>
                                        </h3>
                                        <p class="text-sm text-blue-600 font-semibold mt-0.5"><?= htmlspecialchars($o['nombre_empresa'] ?? 'Empresa confidencial') ?></p>
                                    </div>
                                    <?php if ($o['salario_min'] || $o['salario_max']): ?>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-bold text-green-700">
                                            <?php if ($o['salario_min'] && $o['salario_max']): ?>
                                                $<?= number_format($o['salario_min']/1000,0) ?>k – $<?= number_format($o['salario_max']/1000,0) ?>k
                                            <?php elseif ($o['salario_min']): ?>
                                                Desde $<?= number_format($o['salario_min']/1000,0) ?>k
                                            <?php else: ?>
                                                Hasta $<?= number_format($o['salario_max']/1000,0) ?>k
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-gray-400">ARS / mes</p>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex flex-wrap gap-3 mt-2.5 text-xs text-gray-500">
                                    <?php if ($o['nombre_provincia']): ?>
                                    <span class="flex items-center gap-1">
                                        <?= $svg_pin ?>
                                        <?= htmlspecialchars($o['nombre_localidad'] ? $o['nombre_localidad'].', '.$o['nombre_provincia'] : $o['nombre_provincia']) ?>
                                    </span>
                                    <?php
                                    if ($id_persona_sesion > 0 && $pref_provincia > 0) {
                                        $prov_o = intval($o['id_provincia'] ?? 0);
                                        $loc_o  = intval($o['id_localidad'] ?? 0);
                                        $fuera  = ($pref_localidad > 0 && $loc_o > 0)
                                                    ? ($loc_o !== $pref_localidad)
                                                    : ($prov_o !== $pref_provincia);
                                        if ($fuera): ?>
                                    <span class="flex items-center gap-1 text-amber-600 font-semibold">
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        Fuera de tu zona
                                    </span>
                                    <?php   endif;
                                    } ?>
                                    <?php endif; ?>
                                    <span class="flex items-center gap-1">
                                        <?= $svg_bag ?>
                                        <?= htmlspecialchars($o['tipo_contrato']) ?>
                                    </span>
                                    <span class="flex items-center gap-1 text-blue-600">
                                        <?= $svg_usr ?>
                                        <?= intval($o['total_postulantes']) ?> postulante<?= $o['total_postulantes'] != 1 ? 's' : '' ?>
                                    </span>
                                </div>

                                <?php if ($o['nombre_especialidad']): ?>
                                <div class="mt-3">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-100">
                                        <?= $svg_tool ?> <?= htmlspecialchars($o['nombre_especialidad']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Footer card -->
                    <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-between gap-3">
                        <span class="flex items-center gap-1.5 text-xs text-gray-400 flex-shrink-0">
                            <?= $svg_cal ?>
                            <?= tiempo_relativo($o['fecha_publicacion']) ?>
                        </span>
                        <div class="flex items-center gap-2">
                            <?php if (!empty($o['ya_postulado'])): ?>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-200 px-2.5 py-1.5 rounded-lg cursor-default">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Ya postulado
                            </span>
                            <?php endif; ?>
                            <a href="ofertas-laborales.php?ver=<?= intval($o['id_oferta']) ?>"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition shadow-sm flex-shrink-0">
                                Ver detalles
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php endif; ?>

<footer class="bg-white border-t border-gray-200 text-gray-600 py-8 px-3 mt-16">
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