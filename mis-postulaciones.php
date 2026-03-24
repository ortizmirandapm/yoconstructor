<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Argentina/Buenos_Aires');
include("conexion.php");

if (!isset($_SESSION['idusuario']) || intval($_SESSION['tipo'] ?? 0) !== 2) {
    header("Location: login.php");
    exit;
}

$id_persona     = intval($_SESSION['idpersona'] ?? 0);
$nombreCompleto = 'Usuario';
$tipo_nombre    = $_SESSION['tipo_nombre'] ?? 'Trabajador';
$EmailUsuario   = $_SESSION['emailusuario'] ?? '';
$foto_perfil    = './img/profile.png';

$sql_user = "SELECT CONCAT(UPPER(p.nombre), ' ', UPPER(p.apellido)) as nombre_completo, p.imagen_perfil
             FROM users u INNER JOIN persona p ON u.id_persona = p.id_persona
             WHERE u.id_usuario = '{$_SESSION['idusuario']}'";
$res_user = mysqli_query($conexion, $sql_user);
if ($res_user && mysqli_num_rows($res_user) > 0) {
    $d = mysqli_fetch_assoc($res_user);
    $nombreCompleto = $d['nombre_completo'];
    if (!empty($d['imagen_perfil']) && file_exists('uploads/perfil/' . $d['imagen_perfil']))
        $foto_perfil = 'uploads/perfil/' . $d['imagen_perfil'];
}

$msg_success = '';
$msg_error   = '';
if (isset($_POST['cancelar']) && isset($_POST['id_postulacion'])) {
    $id_post = intval($_POST['id_postulacion']);
    $check = mysqli_query($conexion, "SELECT id_postulacion, estado FROM postulaciones WHERE id_postulacion = $id_post AND id_persona = $id_persona");
    if ($check && mysqli_num_rows($check) > 0) {
        $pd = mysqli_fetch_assoc($check);
        if ($pd['estado'] === 'Pendiente') {
            if (mysqli_query($conexion, "DELETE FROM postulaciones WHERE id_postulacion = $id_post"))
                $msg_success = 'Postulación cancelada correctamente.';
            else
                $msg_error = 'Error al cancelar la postulación.';
        } else {
            $msg_error = 'Solo podés cancelar postulaciones en estado Pendiente.';
        }
    } else {
        $msg_error = 'No se encontró la postulación.';
    }
}

$filtro_estado  = $_GET['estado'] ?? '';
$estados_validos = ['Pendiente', 'Revisada', 'Entrevista', 'Aceptada', 'Rechazada'];
$where_estado   = '';
if ($filtro_estado && in_array($filtro_estado, $estados_validos)) {
    $fe = mysqli_real_escape_string($conexion, $filtro_estado);
    $where_estado = "AND p.estado = '$fe'";
}

$sql_post = "SELECT p.id_postulacion, p.estado, p.fecha_postulacion,
                o.id_oferta, o.titulo, o.tipo_contrato, o.modalidad, o.salario_min, o.salario_max,
                o.fecha_vencimiento, o.estado AS estado_oferta, o.descripcion, o.requisitos,
                o.experiencia_requerida,
                emp.nombre_empresa, emp.logo AS logo_empresa,
                prov.nombre AS provincia, loc.nombre_localidad AS localidad,
                esp.nombre_especialidad
             FROM postulaciones p
             INNER JOIN ofertas_laborales o ON p.id_oferta = o.id_oferta
             LEFT JOIN empresa emp      ON o.id_empresa   = emp.id_empresa
             LEFT JOIN provincias prov  ON o.id_provincia = prov.id_provincia
             LEFT JOIN localidades loc  ON o.id_localidad = loc.id_localidad
             LEFT JOIN especialidades esp ON o.id_especialidad = esp.id_especialidad
             WHERE p.id_persona = $id_persona $where_estado
             ORDER BY p.fecha_postulacion DESC";
$res_post = mysqli_query($conexion, $sql_post);
$postulaciones = [];
while ($row = mysqli_fetch_assoc($res_post)) $postulaciones[] = $row;

$sql_counts = "SELECT estado, COUNT(*) as total FROM postulaciones WHERE id_persona = $id_persona GROUP BY estado";
$res_counts = mysqli_query($conexion, $sql_counts);
$counts = ['Pendiente'=>0,'Revisada'=>0,'Entrevista'=>0,'Aceptada'=>0,'Rechazada'=>0];
while ($c = mysqli_fetch_assoc($res_counts)) $counts[$c['estado']] = $c['total'];
$total = array_sum($counts);

$estado_config = [
    'Pendiente'  => ['bg'=>'bg-yellow-100','text'=>'text-yellow-800','border'=>'border-yellow-300','dot'=>'bg-yellow-400','label'=>'Pendiente'],
    'Revisada'   => ['bg'=>'bg-blue-100',  'text'=>'text-blue-800',  'border'=>'border-blue-300',  'dot'=>'bg-blue-400',  'label'=>'Vista por empresa'],
    'Entrevista' => ['bg'=>'bg-purple-100','text'=>'text-purple-800','border'=>'border-purple-300','dot'=>'bg-purple-500','label'=>'Entrevista'],
    'Aceptada'   => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'border'=>'border-green-300', 'dot'=>'bg-green-500', 'label'=>'Aceptada'],
    'Rechazada'  => ['bg'=>'bg-red-100',   'text'=>'text-red-800',   'border'=>'border-red-300',   'dot'=>'bg-red-400',   'label'=>'Rechazada'],
];
$bar_colors = ['Pendiente'=>'bg-yellow-400','Revisada'=>'bg-blue-400','Entrevista'=>'bg-purple-500','Aceptada'=>'bg-green-500','Rechazada'=>'bg-red-400'];

function tr_post($fecha) {
    $d = time() - strtotime($fecha);
    if ($d < 3600)   return 'Hace ' . floor($d/60) . ' min';
    if ($d < 86400)  return 'Hace ' . floor($d/3600) . ' h';
    if ($d < 172800) return 'Ayer';
    if ($d < 604800) return 'Hace ' . floor($d/86400) . ' días';
    return date('d/m/Y', strtotime($fecha));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Postulaciones - YoConstructor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">

<?php include_once("navbar-trabajador.php"); ?>

<!-- MAIN -->
<main class="min-h-screen py-8">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white shadow-sm border border-gray-200">
            <div class="lg:grid lg:grid-cols-12 lg:divide-x lg:divide-gray-100">

                <!-- SIDEBAR -->
                <aside class="py-6 lg:col-span-3 bg-gray-50 rounded-l-2xl">
                    <nav class="space-y-1 px-2">
                        <a href="perfil-trabajador.php" class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">Mi perfil</span>
                        </a>
                        <!-- Activo -->
                        <a href="mis-postulaciones.php" class="bg-blue-50 border-blue-600 text-blue-700 group border-l-4 px-3 py-2 flex items-center text-sm font-semibold rounded-r-xl" aria-current="page">
                            <svg class="text-blue-600 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                            </svg>
                            <span class="truncate">Mis postulaciones</span>
                        </a>
                        <a href="notificaciones.php" class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                            </svg>
                            <span class="truncate">Notificaciones</span>                
                           
                        </a>
                        <a href="configuraciones-trabajador.php" class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">Configuración</span>
                        </a>
                    </nav>
                </aside>

                <!-- CONTENIDO -->
                <div class="lg:col-span-9">
                    <div class="py-6 px-4 sm:p-6 lg:pb-8">

                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-extrabold text-gray-900">Mis postulaciones</h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    <?= $total ?> postulación<?= $total !== 1 ? 'es' : '' ?> en total
                                </p>
                            </div>
                            <!-- 
                            <a href="ofertas-laborales.php"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition text-sm font-semibold shadow-md hover:shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Ver ofertas
                            </a>
                            -->
                        </div>

                        <!-- Mensajes -->
                        <?php if ($msg_success): ?>
                        <div class="mb-5 flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm font-medium"><?= htmlspecialchars($msg_success) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($msg_error): ?>
                        <div class="mb-5 flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm font-medium"><?= htmlspecialchars($msg_error) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Filtro por estado -->
                        <div class="flex flex-wrap gap-2 mb-6">
                            <a href="mis-postulaciones.php"
                                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium border transition
                                    <?= $filtro_estado === '' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' ?>">
                                Todos
                                <span class="<?= $filtro_estado === '' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600' ?> text-xs font-bold px-1.5 py-0.5 rounded-full"><?= $total ?></span>
                            </a>
                            <?php
                            $filtros_labels = [
                                'Pendiente'  => ['label'=>'Pendiente',        'cnt'=>$counts['Pendiente'],  'active_cls'=>'bg-yellow-500 text-white border-yellow-500', 'dot'=>'bg-yellow-400'],
                                'Revisada'   => ['label'=>'Vista por empresa','cnt'=>$counts['Revisada'],   'active_cls'=>'bg-blue-500 text-white border-blue-500',     'dot'=>'bg-blue-400'],
                                'Entrevista' => ['label'=>'Entrevista',       'cnt'=>$counts['Entrevista'], 'active_cls'=>'bg-purple-500 text-white border-purple-500', 'dot'=>'bg-purple-500'],
                                'Aceptada'   => ['label'=>'Aceptada',         'cnt'=>$counts['Aceptada'],   'active_cls'=>'bg-green-500 text-white border-green-500',   'dot'=>'bg-green-500'],
                                'Rechazada'  => ['label'=>'Rechazada',        'cnt'=>$counts['Rechazada'],  'active_cls'=>'bg-red-500 text-white border-red-500',       'dot'=>'bg-red-400'],
                            ];
                            foreach ($filtros_labels as $val => $fi):
                                $activo = $filtro_estado === $val;
                            ?>
                            <a href="?estado=<?= $val ?>"
                                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium border transition
                                    <?= $activo ? $fi['active_cls'] : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' ?>">
                                <span class="w-2 h-2 rounded-full flex-shrink-0 <?= $activo ? 'bg-white opacity-80' : $fi['dot'] ?>"></span>
                                <?= $fi['label'] ?>
                                <span class="<?= $activo ? 'bg-white bg-opacity-30 text-white' : 'bg-gray-100 text-gray-600' ?> text-xs font-bold px-1.5 py-0.5 rounded-full"><?= $fi['cnt'] ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- Listado -->
                        <?php if (empty($postulaciones)): ?>
                        <div class="flex flex-col items-center justify-center py-20 text-center">
                            <svg class="w-16 h-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                            </svg>
                            <p class="text-gray-400 font-semibold text-lg mb-1">Sin postulaciones<?= $filtro_estado ? ' en este estado' : '' ?></p>
                            <p class="text-gray-400 text-sm mb-5">Explorá las ofertas disponibles y comenzá a postularte</p>
                            <a href="ofertas-laborales.php" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition shadow-md">
                                Explorar ofertas laborales
                            </a>
                        </div>

                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($postulaciones as $p):
                                $cfg = $estado_config[$p['estado']] ?? $estado_config['Pendiente'];
                                $bar = $bar_colors[$p['estado']] ?? 'bg-gray-300';
                                $oferta_vencida  = $p['fecha_vencimiento'] && strtotime($p['fecha_vencimiento']) < time();
                                $oferta_inactiva = $p['estado_oferta'] !== 'Activa';
                                if (!empty($p['logo_empresa'])) {
                                    $logo_raw = $p['logo_empresa'];
                                    $logo_src = (str_starts_with($logo_raw, 'uploads/') || str_starts_with($logo_raw, 'http'))
                                        ? $logo_raw
                                        : 'uploads/logos/' . $logo_raw;
                                } else {
                                    $logo_src = './img/profile.png';
                                }
                                $data_oferta = htmlspecialchars(json_encode([
                                    'titulo'        => $p['titulo'],
                                    'empresa'       => $p['nombre_empresa'] ?? '',
                                    'descripcion'   => $p['descripcion'] ?? '',
                                    'requisitos'    => $p['requisitos'] ?? '',
                                    'tipo_contrato' => $p['tipo_contrato'] ?? '',
                                    'modalidad'     => $p['modalidad'] ?? '',
                                    'salario_min'   => $p['salario_min'],
                                    'salario_max'   => $p['salario_max'],
                                    'provincia'     => $p['provincia'] ?? '',
                                    'localidad'     => $p['localidad'] ?? '',
                                    'especialidad'  => $p['nombre_especialidad'] ?? '',
                                    'experiencia'   => $p['experiencia_requerida'] ?? '',
                                    'vencimiento'   => $p['fecha_vencimiento'] ? date('d/m/Y', strtotime($p['fecha_vencimiento'])) : '',
                                    'estado_oferta' => $p['estado_oferta'],
                                    'logo'          => $logo_src,
                                    'id_oferta'     => $p['id_oferta'],
                                    'estado_post'   => $p['estado'],
                                    'fecha_post'    => tr_post($p['fecha_postulacion']),
                                ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <!-- Card con barra animada igual al index -->
                            <div class="group relative border border-gray-200 rounded-2xl hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 bg-white overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-0.5 bg-blue-600 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                                <div class="p-5">
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-14 h-14 rounded-xl overflow-hidden border border-gray-100 bg-gray-50">
                                                <img src="<?= $logo_src ?>" class="w-full h-full object-contain p-1" alt="logo">
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-start justify-between gap-2 mb-1">
                                                <div>
                                                    <h3 class="text-base font-bold text-gray-900 leading-tight group-hover:text-blue-600 transition-colors">
                                                        <?= htmlspecialchars($p['titulo']) ?>
                                                    </h3>
                                                    <p class="text-sm text-blue-600 font-semibold mt-0.5"><?= htmlspecialchars($p['nombre_empresa'] ?? 'Empresa') ?></p>
                                                </div>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $cfg['bg'] ?> <?= $cfg['text'] ?> border <?= $cfg['border'] ?> flex-shrink-0">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $bar ?>"></span>
                                                    <?= $cfg['label'] ?>
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5 my-3">
                                                <?php if ($p['localidad'] || $p['provincia']): ?>
                                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <?= htmlspecialchars($p['localidad'] ? $p['localidad'] . ', ' . $p['provincia'] : $p['provincia']) ?>
                                                </span>
                                                <?php endif; ?>
                                                <?php if ($p['tipo_contrato']): ?>
                                                <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full border border-blue-100">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    <?= htmlspecialchars($p['tipo_contrato']) ?>
                                                </span>
                                                <?php endif; ?>
                                                <?php if ($p['modalidad']): ?>
                                                <span class="inline-flex items-center gap-1 text-xs bg-purple-50 text-purple-700 px-2.5 py-1 rounded-full border border-purple-200">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                                    <?= htmlspecialchars($p['modalidad']) ?>
                                                </span>
                                                <?php endif; ?>
                                                <?php if ($p['nombre_especialidad']): ?>
                                                <span class="inline-flex items-center gap-1 text-xs bg-orange-50 text-orange-700 px-2.5 py-1 rounded-full border border-orange-200">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <?= htmlspecialchars($p['nombre_especialidad']) ?>
                                                </span>
                                                <?php endif; ?>
                                                <?php if ($p['salario_min'] || $p['salario_max']): ?>
                                                <span class="inline-flex items-center gap-1 text-xs bg-green-50 text-green-700 px-2.5 py-1 rounded-full border border-green-200 font-medium">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    $<?= number_format(($p['salario_min'] ?: $p['salario_max'])/1000, 0) ?>k<?= ($p['salario_min'] && $p['salario_max']) ? ' – $'.number_format($p['salario_max']/1000,0).'k' : '' ?> ARS
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-gray-100">
                                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span>Postulado <?= tr_post($p['fecha_postulacion']) ?></span>
                                                    <?php if ($oferta_inactiva): ?>
                                                    <span class="inline-flex items-center gap-1 text-orange-500 font-medium">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                        Oferta pausada
                                                    </span>
                                                    <?php elseif ($oferta_vencida): ?>
                                                    <span class="inline-flex items-center gap-1 text-red-400 font-medium">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        Oferta vencida
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button onclick='abrirOferta(<?= $data_oferta ?>)'
                                                        class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 border border-gray-300 text-gray-600 hover:bg-gray-50 hover:border-gray-400 rounded-lg transition font-medium">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                        Ver oferta
                                                    </button>
                                                    <?php if ($p['estado'] === 'Pendiente'): ?>
                                                    <button onclick="confirmarCancelar(<?= $p['id_postulacion'] ?>, '<?= htmlspecialchars(addslashes($p['titulo'])) ?>')"
                                                        class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 border border-red-300 text-red-600 hover:bg-red-50 rounded-lg transition font-medium">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        Cancelar
                                                    </button>
                                                    <?php elseif ($p['estado'] === 'Aceptada'): ?>
                                                    <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-green-100 text-green-700 rounded-lg font-semibold">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        ¡Felicitaciones!
                                                    </span>
                                                    <?php elseif ($p['estado'] === 'Entrevista'): ?>
                                                    <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg font-semibold">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                        Revisá tu email
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                </div><!-- fin col-span-9 -->

            </div><!-- fin grid -->
        </div><!-- fin rounded card -->
    </div>
</main>

<!-- MODAL VER OFERTA -->
<div id="modalOferta" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl overflow-hidden border border-gray-100 bg-gray-50 flex-shrink-0">
                    <img id="mo-logo" src="" class="w-full h-full object-contain p-1" alt="logo">
                </div>
                <div>
                    <h3 id="mo-titulo" class="text-lg font-extrabold text-gray-900 leading-tight"></h3>
                    <p id="mo-empresa" class="text-sm text-blue-600 font-semibold"></p>
                </div>
            </div>
            <button onclick="cerrarOferta()" class="text-gray-400 hover:text-gray-600 transition ml-4 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">
            <div id="mo-chips" class="flex flex-wrap gap-2"></div>
            <div id="mo-salario-wrap" class="hidden bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span id="mo-salario" class="text-green-800 font-semibold text-sm"></span>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm font-bold text-gray-700">Descripción</p>
                </div>
                <p id="mo-descripcion" class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"></p>
            </div>
            <div id="mo-req-wrap" class="hidden">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <p class="text-sm font-bold text-gray-700">Requisitos</p>
                </div>
                <p id="mo-requisitos" class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"></p>
            </div>
            <div id="mo-estado-wrap" class="rounded-xl p-4 border"></div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center flex-shrink-0 bg-gray-50 rounded-b-2xl">
            <span id="mo-fecha-post" class="text-xs text-gray-400"></span>
            <div class="flex gap-3">
                <button onclick="cerrarOferta()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-xl text-sm hover:bg-gray-100 transition font-medium">Cerrar</button>
                <a id="mo-link" href="#" target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Ver página completa
                </a>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CANCELAR -->
<div id="modalCancelar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-lg font-extrabold text-gray-900">Cancelar postulación</h3>
        </div>
        <div class="px-6 py-5">
            <p class="text-gray-600 text-sm">¿Estás seguro que querés cancelar tu postulación a:</p>
            <p id="modalOfertaTitulo" class="font-semibold text-gray-900 mt-2 text-sm bg-gray-50 px-3 py-2 rounded-xl border border-gray-100"></p>
            <p class="text-gray-400 text-xs mt-3 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Esta acción no se puede deshacer.
            </p>
        </div>
        <form id="formCancelar" method="POST" action="mis-postulaciones.php<?= $filtro_estado ? '?estado='.urlencode($filtro_estado) : '' ?>">
            <input type="hidden" name="id_postulacion" id="cancelarId">
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
                <button type="button" onclick="cerrarModalCancelar()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition text-sm font-medium">
                    No, mantener
                </button>
                <button type="submit" name="cancelar" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition text-sm font-semibold shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Sí, cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FOOTER — unificado igual al index -->
<footer class="bg-white border-t border-gray-200 text-gray-600 py-8 px-3 mt-8">
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

<script>
function confirmarCancelar(id, titulo) {
    document.getElementById('cancelarId').value = id;
    document.getElementById('modalOfertaTitulo').textContent = titulo;
    document.getElementById('modalCancelar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalCancelar() {
    document.getElementById('modalCancelar').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

const estadoConfig = <?= json_encode($estado_config) ?>;

function abrirOferta(o) {
    document.getElementById('mo-logo').src              = o.logo;
    document.getElementById('mo-titulo').textContent    = o.titulo;
    document.getElementById('mo-empresa').textContent   = o.empresa;
    document.getElementById('mo-descripcion').textContent = o.descripcion || 'Sin descripción';
    document.getElementById('mo-link').href             = 'ofertas-laborales.php?ver=' + o.id_oferta;
    document.getElementById('mo-fecha-post').textContent = 'Postulado: ' + o.fecha_post;

    const reqWrap = document.getElementById('mo-req-wrap');
    if (o.requisitos) {
        document.getElementById('mo-requisitos').textContent = o.requisitos;
        reqWrap.classList.remove('hidden');
    } else { reqWrap.classList.add('hidden'); }

    const salWrap = document.getElementById('mo-salario-wrap');
    if (o.salario_min || o.salario_max) {
        let txt = '$' + Math.round((o.salario_min || o.salario_max) / 1000) + 'k ARS';
        if (o.salario_min && o.salario_max) txt = '$' + Math.round(o.salario_min/1000) + 'k – $' + Math.round(o.salario_max/1000) + 'k ARS';
        document.getElementById('mo-salario').textContent = txt;
        salWrap.classList.remove('hidden');
    } else { salWrap.classList.add('hidden'); }

    const chips = document.getElementById('mo-chips');
    chips.innerHTML = '';
    const addChip = (txt, cls) => {
        if (!txt) return;
        const s = document.createElement('span');
        s.className = 'inline-flex items-center text-xs px-2.5 py-1 rounded-full border font-medium ' + cls;
        s.textContent = txt;
        chips.appendChild(s);
    };
    addChip(o.localidad ? o.localidad + ', ' + o.provincia : o.provincia, 'bg-gray-100 text-gray-600 border-gray-200');
    addChip(o.tipo_contrato,  'bg-blue-50 text-blue-700 border-blue-100');
    addChip(o.modalidad,      'bg-purple-50 text-purple-700 border-purple-200');
    addChip(o.especialidad,   'bg-orange-50 text-orange-700 border-orange-200');
    addChip(o.experiencia ? o.experiencia + ' de experiencia' : null, 'bg-gray-100 text-gray-600 border-gray-200');
    if (o.vencimiento) addChip('Vence: ' + o.vencimiento, 'bg-red-50 text-red-600 border-red-200');

    const cfg = estadoConfig[o.estado_post] || estadoConfig['Pendiente'];
    const labels = {
        Pendiente:  'Tu postulación está siendo revisada por la empresa.',
        Revisada:   'La empresa ya vio tu postulación.',
        Entrevista: 'La empresa quiere hacerte una entrevista. Revisá tu email.',
        Aceptada:   '¡Felicitaciones! Fuiste aceptado para este puesto.',
        Rechazada:  'La empresa no seleccionó tu postulación para este puesto.',
    };
    const eWrap = document.getElementById('mo-estado-wrap');
    eWrap.className = 'rounded-xl p-4 border ' + cfg.bg + ' ' + cfg.border;
    eWrap.innerHTML = `<div class="flex items-center gap-2 mb-1">
        <span class="w-2 h-2 rounded-full flex-shrink-0 ${cfg.dot}"></span>
        <span class="text-sm font-semibold ${cfg.text}">${cfg.label}</span>
    </div>
    <p class="text-sm ${cfg.text} opacity-80">${labels[o.estado_post] || ''}</p>`;

    document.getElementById('modalOferta').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarOferta() {
    document.getElementById('modalOferta').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarModalCancelar(); cerrarOferta(); }
});
document.getElementById('modalCancelar').addEventListener('click', function(e) { if (e.target === this) cerrarModalCancelar(); });
document.getElementById('modalOferta').addEventListener('click',   function(e) { if (e.target === this) cerrarOferta(); });
</script>

</body>
</html>