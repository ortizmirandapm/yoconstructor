<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

$nombreCompleto = 'Usuario';
$tipo_usuario   = null;
$tipo_nombre    = 'Usuario';
$EmailUsuario   = '';
$foto_perfil    = './img/profile.png';

if (!function_exists('getFotoPerfilEmpPub')) {
    function getFotoPerfilEmpPub($imagen_perfil) {
        if (!empty($imagen_perfil) && file_exists('uploads/perfil/' . $imagen_perfil)) {
            return 'uploads/perfil/' . $imagen_perfil;
        }
        return './img/profile.png';
    }
}

if (isset($_SESSION['idusuario'])) {
    $id_usuario   = $_SESSION['idusuario'];
    $tipo_usuario = $_SESSION['tipo'];
    $tipo_nombre  = $_SESSION['tipo_nombre'] ?? 'Usuario';
    $EmailUsuario = $_SESSION['emailusuario'] ?? '';

    if ($tipo_usuario == 2) {
        $sql = "SELECT CONCAT(UPPER(p.nombre), ' ', UPPER(p.apellido)) as nombre_completo,
                       p.imagen_perfil
                FROM users u
                INNER JOIN persona p ON u.id_persona = p.id_persona
                WHERE u.id_usuario = '$id_usuario'";
        $resultado = mysqli_query($conexion, $sql);
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $datos          = mysqli_fetch_assoc($resultado);
            $nombreCompleto = $datos['nombre_completo'];
            $foto_perfil    = getFotoPerfilEmpPub($datos['imagen_perfil']);
        }
    } else {
        $nombreCompleto = strtoupper($_SESSION['nombreusuario'] ?? 'Usuario');
    }
}

// --- ID EMPRESA ---
$id_empresa = intval($_GET['id'] ?? 0);
if ($id_empresa <= 0) {
    header("Location: ofertas-laborales.php");
    exit;
}

// --- DATOS EMPRESA ---
$sql_empresa = "SELECT e.*,
                    r.nombre AS rubro_nombre,
                    p.nombre AS provincia_nombre
                FROM empresa e
                LEFT JOIN rubros r      ON e.id_rubro     = r.id_rubro
                LEFT JOIN provincias p  ON e.id_provincia = p.id_provincia
                WHERE e.id_empresa = $id_empresa";
$res_emp = mysqli_query($conexion, $sql_empresa);
if (!$res_emp) {
    die("<b>ERROR empresa:</b> " . mysqli_error($conexion) . "<pre>$sql_empresa</pre>");
}
if (mysqli_num_rows($res_emp) === 0) {
    die("<b>No se encontro la empresa con id = $id_empresa</b>");
}
$empresa = mysqli_fetch_assoc($res_emp);

// --- ESTADÍSTICAS ---
$q_of = mysqli_query($conexion, "SELECT COUNT(*) AS c FROM ofertas_laborales WHERE id_empresa = $id_empresa AND estado = 'Activa'");
if (!$q_of) die("<b>ERROR stats ofertas:</b> " . mysqli_error($conexion));
$total_ofertas = mysqli_fetch_assoc($q_of)['c'];

$q_po = mysqli_query($conexion, "SELECT COUNT(*) AS c FROM postulaciones po INNER JOIN ofertas_laborales o ON po.id_oferta = o.id_oferta WHERE o.id_empresa = $id_empresa");
if (!$q_po) die("<b>ERROR stats postulantes:</b> " . mysqli_error($conexion));
$total_postulantes = mysqli_fetch_assoc($q_po)['c'];

// --- OFERTAS ACTIVAS ---
$sql_of = "SELECT o.id_oferta, o.titulo, o.tipo_contrato, o.modalidad,
            o.salario_min, o.salario_max, o.fecha_publicacion,
            esp.nombre_especialidad,
            p.nombre AS nombre_provincia,
            l.nombre_localidad,
            (SELECT COUNT(*) FROM postulaciones po WHERE po.id_oferta = o.id_oferta) AS total_post
     FROM ofertas_laborales o
     LEFT JOIN especialidades esp ON o.id_especialidad = esp.id_especialidad
     LEFT JOIN provincias p       ON o.id_provincia    = p.id_provincia
     LEFT JOIN localidades l      ON o.id_localidad    = l.id_localidad
     WHERE o.id_empresa = $id_empresa AND o.estado = 'Activa'
     ORDER BY o.fecha_publicacion DESC";
$res_ofertas = mysqli_query($conexion, $sql_of);
if (!$res_ofertas) die("<b>ERROR ofertas:</b> " . mysqli_error($conexion));
$ofertas = [];
while ($r = mysqli_fetch_assoc($res_ofertas)) $ofertas[] = $r;

function tiempo_relativo_emp($fecha)
{
    $diff = time() - strtotime($fecha);
    if ($diff < 3600)       return "Hace " . max(1, floor($diff / 60)) . " min";
    if ($diff < 86400)      return "Hace " . floor($diff / 3600) . " h";
    if ($diff < 86400 * 2)  return "Ayer";
    if ($diff < 86400 * 7)  return "Hace " . floor($diff / 86400) . " días";
    return date("d/m/Y", strtotime($fecha));
}

$svg_pin  = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
$svg_cal  = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
$svg_bag  = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
$svg_usr  = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
$svg_tool = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
$svg_mail = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
$svg_tel  = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
$svg_web  = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($empresa['nombre_empresa']) ?> - YoConstructor</title>
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

<!-- Breadcrumb -->
<nav class="w-full px-6 py-3 bg-gray-50 border-b border-gray-200">
    <ol class="flex items-center space-x-2 text-sm text-gray-500 max-w-7xl mx-auto">
        <li><a href="index.php" class="hover:text-blue-600 transition">Inicio</a></li>
        <li><span class="text-gray-300">/</span></li>
        <li><a href="ofertas-laborales.php" class="hover:text-blue-600 transition">Ofertas laborales</a></li>
        <li><span class="text-gray-300">/</span></li>
        <li class="text-gray-700 font-semibold truncate max-w-xs"><?= htmlspecialchars($empresa['nombre_empresa']) ?></li>
    </ol>
</nav>

<main class="max-w-5xl mx-auto px-4 py-10 flex-1 w-full">

    <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-blue-600 mb-6 transition font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ===== COLUMNA PRINCIPAL ===== -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Hero empresa -->
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 pt-6 pb-6">
                    <div class="flex items-center justify-between mb-5">
                        <div class="w-20 h-20 rounded-2xl border border-gray-200 bg-gray-50 shadow-sm overflow-hidden flex items-center justify-center flex-shrink-0">
                            <?php if (!empty($empresa['logo'])): ?>
                                <?php
                                $logo_src = str_starts_with($empresa['logo'], 'uploads/') ? $empresa['logo'] : 'uploads/logos/' . $empresa['logo'];
                                ?>
                                <img src="<?= htmlspecialchars($logo_src) ?>" alt="Logo" class="w-full h-full object-cover">
                            <?php else: ?>
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h1 class="text-2xl font-extrabold text-gray-900"><?= htmlspecialchars($empresa['nombre_empresa']) ?></h1>
                    <?php if ($empresa['razon_social']): ?>
                        <p class="text-sm text-gray-400 mt-0.5"><?= htmlspecialchars($empresa['razon_social']) ?></p>
                    <?php endif; ?>

                    <!-- Chips info -->
                    <div class="flex flex-wrap gap-2 mt-4">
                        <?php if ($empresa['rubro_nombre']): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 text-orange-700 text-xs font-semibold rounded-full border border-orange-200">
                            <?= $svg_tool ?>
                            <?= htmlspecialchars($empresa['rubro_nombre']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($empresa['provincia_nombre']): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                            <?= $svg_pin ?>
                            <?= htmlspecialchars($empresa['provincia_nombre']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($empresa['cuit']): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            CUIT: <?= htmlspecialchars($empresa['cuit']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($empresa['fecha_ingreso']): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                            <?= $svg_cal ?>
                            Desde <?= date('Y', strtotime($empresa['fecha_ingreso'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Descripción -->
            <?php if (!empty($empresa['descripcion_empresa'] ?? $empresa['descripcion'] ?? '')):
                $desc_empresa = $empresa['descripcion_empresa'] ?? $empresa['descripcion'] ?? '';
            ?>
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>
                    </svg>
                    Sobre la empresa
                </h2>
                <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"><?= htmlspecialchars($desc_empresa) ?></p>
            </div>
            <?php endif; ?>

            <!-- Ofertas activas -->
            <div>
                <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Ofertas activas
                </h2>

                <?php if (empty($ofertas)): ?>
                <div class="bg-white border border-dashed border-gray-200 rounded-2xl p-10 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-gray-400 text-sm font-semibold">Esta empresa no tiene ofertas activas por el momento</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($ofertas as $o): ?>
                    <div class="group relative bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 right-0 h-0.5 bg-blue-600 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-gray-900 text-base group-hover:text-blue-600 transition-colors truncate">
                                        <?= htmlspecialchars($o['titulo']) ?>
                                    </h3>
                                    <div class="flex flex-wrap gap-3 mt-2 text-xs text-gray-500">
                                        <?php if ($o['nombre_provincia']): ?>
                                        <span class="flex items-center gap-1">
                                            <?= $svg_pin ?>
                                            <?= htmlspecialchars($o['nombre_localidad'] ? $o['nombre_localidad'] . ', ' . $o['nombre_provincia'] : $o['nombre_provincia']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <span class="flex items-center gap-1">
                                            <?= $svg_bag ?>
                                            <?= htmlspecialchars($o['tipo_contrato']) ?>
                                        </span>
                                        <?php if ($o['modalidad']): ?>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                            <?= htmlspecialchars($o['modalidad']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($o['nombre_especialidad']): ?>
                                    <div class="mt-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-100">
                                            <?= $svg_tool ?> <?= htmlspecialchars($o['nombre_especialidad']) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($o['salario_min'] || $o['salario_max']): ?>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-bold text-green-700">
                                        <?php if ($o['salario_min'] && $o['salario_max']): ?>
                                            $<?= number_format($o['salario_min'] / 1000, 0) ?>k – $<?= number_format($o['salario_max'] / 1000, 0) ?>k
                                        <?php elseif ($o['salario_min']): ?>
                                            Desde $<?= number_format($o['salario_min'] / 1000, 0) ?>k
                                        <?php else: ?>
                                            Hasta $<?= number_format($o['salario_max'] / 1000, 0) ?>k
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-400">ARS / mes</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-xs text-gray-400">
                                <?= $svg_cal ?>
                                <?= tiempo_relativo_emp($o['fecha_publicacion']) ?>
                            </span>
                            <a href="ofertas-laborales.php?ver=<?= intval($o['id_oferta']) ?>"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition shadow-sm">
                                Ver detalles
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ===== COLUMNA LATERAL ===== -->
        <div class="space-y-5">

            <!-- Estadísticas -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 mb-4">Actividad</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-100">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Ofertas activas
                        </div>
                        <span class="font-bold text-gray-900"><?= $total_ofertas ?></span>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <?= str_replace('w-3.5', 'w-4', $svg_usr) ?>
                            Total postulantes
                        </div>
                        <span class="font-bold text-gray-900"><?= $total_postulantes ?></span>
                    </div>
                </div>
            </div>

            <!-- Contacto -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 mb-4">Contacto</h3>
                <div class="space-y-3">
                    <?php if (!empty($empresa['email_contacto'])): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0 text-blue-600">
                            <?= $svg_mail ?>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">Email</p>
                            <a href="mailto:<?= htmlspecialchars($empresa['email_contacto']) ?>" class="text-sm font-medium text-blue-600 hover:underline truncate block">
                                <?= htmlspecialchars($empresa['email_contacto']) ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($empresa['telefono'])): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0 text-blue-600">
                            <?= $svg_tel ?>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Teléfono</p>
                            <a href="tel:<?= htmlspecialchars($empresa['telefono']) ?>" class="text-sm font-medium text-gray-800 hover:text-blue-600 transition">
                                <?= htmlspecialchars($empresa['telefono']) ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($empresa['domicilio'])): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0 text-blue-600">
                            <?= $svg_pin ?>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Dirección</p>
                            <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($empresa['domicilio']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($empresa['sitio_web'])): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0 text-blue-600">
                            <?= $svg_web ?>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">Sitio web</p>
                            <a href="<?= htmlspecialchars($empresa['sitio_web']) ?>" target="_blank" class="text-sm font-medium text-blue-600 hover:underline truncate block">
                                <?= htmlspecialchars($empresa['sitio_web']) ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($empresa['email_contacto']) && empty($empresa['telefono']) && empty($empresa['domicilio'])): ?>
                    <p class="text-xs text-gray-400 text-center py-2">Sin datos de contacto públicos</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ver ofertas CTA -->
            <?php if ($total_ofertas > 0): ?>
            <a href="ofertas-laborales.php?empresa=<?= $id_empresa ?>"
                class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-3.5 rounded-xl transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Ver todas las ofertas
            </a>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- Footer -->
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