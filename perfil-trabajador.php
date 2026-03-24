<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include("conexion.php");

$nombreCompleto = 'Usuario';
$tipo_usuario = null;
$tipo_nombre = 'Usuario';
$EmailUsuario = '';

$persona = [
    'id_persona'               => null,
    'dni'                      => '',
    'nombre'                   => '',
    'apellido'                 => '',
    'descripcion_persona'      => '',
    'anios_experiencia'        => 0,
    'curriculum_pdf'           => '',
    'imagen_perfil'            => '',
    'domicilio'                => '',
    'telefono'                 => '',
    'fecha_nacimiento'         => '',
    'nombre_titulo'            => '',
    'georeferencia'            => '',
    'id_provincia_preferencia' => null,
    'id_localidad_preferencia' => null,
    'provincia_preferencia'    => 'No especificada',
    'localidad_preferencia'    => 'No especificada'
];

$especialidades_persona    = [];
$especialidad_principal    = null;

if (isset($_SESSION['idusuario'])) {
    $id_usuario  = $_SESSION['idusuario'];
    $tipo_usuario = $_SESSION['tipo'];
    $tipo_nombre  = $_SESSION['tipo_nombre'] ?? 'Usuario';
    $EmailUsuario = $_SESSION['emailusuario'] ?? '';

    if ($tipo_usuario == 2) {
        $sql = "SELECT 
                p.id_persona, p.dni, p.nombre, p.apellido,
                CONCAT(UPPER(p.nombre), ' ', UPPER(p.apellido)) as nombre_completo,
                p.descripcion_persona, p.anios_experiencia, p.curriculum_pdf,
                p.imagen_perfil, p.domicilio, p.telefono, p.fecha_nacimiento,
                p.nombre_titulo, p.georeferencia,
                p.id_provincia_preferencia, p.id_localidad_preferencia,
                prov_pref.nombre as provincia_preferencia,
                loc_pref.nombre_localidad as localidad_preferencia
            FROM users u
            INNER JOIN persona p ON u.id_persona = p.id_persona
            LEFT JOIN provincias prov_pref ON p.id_provincia_preferencia = prov_pref.id_provincia
            LEFT JOIN localidades loc_pref ON p.id_localidad_preferencia = loc_pref.id_localidad
            WHERE u.id_usuario = '$id_usuario'";

        $resultado = mysqli_query($conexion, $sql);
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $datos        = mysqli_fetch_assoc($resultado);
            $nombreCompleto = $datos['nombre_completo'];
            $persona = [
                'id_persona'               => $datos['id_persona'] ?? null,
                'dni'                      => $datos['dni'] ?? '',
                'nombre'                   => $datos['nombre'] ?? '',
                'apellido'                 => $datos['apellido'] ?? '',
                'descripcion_persona'      => $datos['descripcion_persona'] ?? '',
                'anios_experiencia'        => $datos['anios_experiencia'] ?? 0,
                'curriculum_pdf'           => $datos['curriculum_pdf'] ?? '',
                'imagen_perfil'            => $datos['imagen_perfil'] ?? '',
                'domicilio'                => $datos['domicilio'] ?? '',
                'telefono'                 => $datos['telefono'] ?? '',
                'fecha_nacimiento'         => $datos['fecha_nacimiento'] ?? '',
                'nombre_titulo'            => $datos['nombre_titulo'] ?? '',
                'georeferencia'            => $datos['georeferencia'] ?? '',
                'id_provincia_preferencia' => $datos['id_provincia_preferencia'] ?? null,
                'id_localidad_preferencia' => $datos['id_localidad_preferencia'] ?? null,
                'provincia_preferencia'    => $datos['provincia_preferencia'] ?? 'No especificada',
                'localidad_preferencia'    => $datos['localidad_preferencia'] ?? 'No especificada'
            ];

            // Cargar especialidades — principal primero
            $sql_esp = "SELECT pe.id_persona_especialidad, pe.id_especialidad,
                               pe.nivel_experiencia, pe.es_principal,
                               e.nombre_especialidad
                        FROM persona_especialidades pe
                        INNER JOIN especialidades e ON pe.id_especialidad = e.id_especialidad
                        WHERE pe.id_persona = '{$persona['id_persona']}'
                        ORDER BY pe.es_principal DESC, e.nombre_especialidad ASC";
            $res_esp = mysqli_query($conexion, $sql_esp);
            while ($esp = mysqli_fetch_assoc($res_esp)) {
                $especialidades_persona[] = [
                    'id_persona_especialidad' => $esp['id_persona_especialidad'],
                    'id_especialidad'         => $esp['id_especialidad'],
                    'nombre'                  => $esp['nombre_especialidad'],
                    'nivel'                   => $esp['nivel_experiencia'],
                    'es_principal'            => (bool)$esp['es_principal']
                ];
            }
            // Referencia rápida a la principal
            foreach ($especialidades_persona as $esp) {
                if ($esp['es_principal']) { $especialidad_principal = $esp; break; }
            }
        }
    }
}

// Foto de perfil
$foto_perfil = !empty($persona['imagen_perfil'])
    ? 'uploads/perfil/' . htmlspecialchars($persona['imagen_perfil'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($persona['nombre'] . '+' . $persona['apellido']) . '&background=2563eb&color=fff&size=128';

// ============================================================
// PROCESAR ACTUALIZACIÓN
// ============================================================
if (isset($_POST['actualizar_perfil'])) {
    $id_persona = $persona['id_persona'];

    $dni = preg_replace('/[^0-9]/', '', $_POST['dni']);
    $dni = mysqli_real_escape_string($conexion, $dni);

    if (strlen($dni) < 7 || strlen($dni) > 8) {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { mostrarMensaje('❌ El DNI debe tener entre 7 y 8 dígitos', 'error'); });</script>";
    } else {
        $nombre            = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $apellido          = mysqli_real_escape_string($conexion, $_POST['apellido']);
        $descripcion       = mysqli_real_escape_string($conexion, $_POST['descripcion_persona']);
        $anios_experiencia = intval($_POST['anios_experiencia']);
        $domicilio         = mysqli_real_escape_string($conexion, $_POST['domicilio']);
        $telefono          = mysqli_real_escape_string($conexion, $_POST['telefono']);
        $fecha_nacimiento  = mysqli_real_escape_string($conexion, $_POST['fecha_nacimiento']);
        $nombre_titulo     = mysqli_real_escape_string($conexion, $_POST['nombre_titulo']);
        $georeferencia     = mysqli_real_escape_string($conexion, $_POST['georeferencia']);
        $curriculum_pdf    = $persona['curriculum_pdf'];
        $imagen_perfil     = $persona['imagen_perfil'];
        $id_provincia_pref = mysqli_real_escape_string($conexion, $_POST['id_provincia_preferencia']);
        $id_localidad_pref = mysqli_real_escape_string($conexion, $_POST['id_localidad_preferencia']);
        $id_esp_principal  = intval($_POST['id_especialidad_principal'] ?? 0);

        // Subida imagen
        if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] == 0) {
            $allowed_img  = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $max_size_img = 2 * 1024 * 1024;
            if (in_array($_FILES['imagen_perfil']['type'], $allowed_img) && $_FILES['imagen_perfil']['size'] <= $max_size_img) {
                $upload_dir_img = 'uploads/perfil/';
                if (!file_exists($upload_dir_img)) mkdir($upload_dir_img, 0777, true);
                $ext_img         = pathinfo($_FILES['imagen_perfil']['name'], PATHINFO_EXTENSION);
                $nuevo_nombre_img = 'perfil_' . $id_persona . '_' . time() . '.' . $ext_img;
                if (!empty($persona['imagen_perfil']) && file_exists($upload_dir_img . $persona['imagen_perfil'])) {
                    unlink($upload_dir_img . $persona['imagen_perfil']);
                }
                if (move_uploaded_file($_FILES['imagen_perfil']['tmp_name'], $upload_dir_img . $nuevo_nombre_img)) {
                    $imagen_perfil = $nuevo_nombre_img;
                } else {
                    echo "<script>document.addEventListener('DOMContentLoaded', function() { mostrarMensaje('❌ Error al subir la imagen', 'error'); });</script>";
                    exit;
                }
            } else {
                echo "<script>document.addEventListener('DOMContentLoaded', function() { mostrarMensaje('❌ La imagen debe ser JPG, PNG o WEBP y menor a 2MB', 'error'); });</script>";
                exit;
            }
        }

        // Subida CV
        if (isset($_FILES['curriculum_pdf']) && $_FILES['curriculum_pdf']['error'] == 0) {
            if ($_FILES['curriculum_pdf']['type'] === 'application/pdf' && $_FILES['curriculum_pdf']['size'] <= 5 * 1024 * 1024) {
                $upload_dir = 'uploads/cv/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $nuevo_cv = 'cv_' . $id_persona . '_' . time() . '.pdf';
                if (!empty($persona['curriculum_pdf']) && file_exists($upload_dir . $persona['curriculum_pdf'])) {
                    unlink($upload_dir . $persona['curriculum_pdf']);
                }
                if (move_uploaded_file($_FILES['curriculum_pdf']['tmp_name'], $upload_dir . $nuevo_cv)) {
                    $curriculum_pdf = $nuevo_cv;
                } else {
                    echo "<script>document.addEventListener('DOMContentLoaded', function() { mostrarMensaje('❌ Error al subir el CV', 'error'); });</script>";
                    exit;
                }
            } else {
                echo "<script>document.addEventListener('DOMContentLoaded', function() { mostrarMensaje('❌ El CV debe ser PDF y menor a 5MB', 'error'); });</script>";
                exit;
            }
        }

        $sql_update = "UPDATE persona SET 
               nombre = '$nombre', apellido = '$apellido', dni = '$dni',
               descripcion_persona = '$descripcion', anios_experiencia = $anios_experiencia,
               curriculum_pdf = '$curriculum_pdf', imagen_perfil = '$imagen_perfil',
               domicilio = '$domicilio', telefono = '$telefono',
               fecha_nacimiento = " . ($fecha_nacimiento ? "'$fecha_nacimiento'" : "NULL") . ",
               nombre_titulo = '$nombre_titulo', georeferencia = '$georeferencia',
               id_provincia_preferencia = " . ($id_provincia_pref ? "'$id_provincia_pref'" : "NULL") . ",
               id_localidad_preferencia = " . ($id_localidad_pref ? "'$id_localidad_pref'" : "NULL") . "
               WHERE id_persona = '$id_persona'";

        if (mysqli_query($conexion, $sql_update)) {
            // Actualizar especialidades
            mysqli_query($conexion, "DELETE FROM persona_especialidades WHERE id_persona = '$id_persona'");

            if (isset($_POST['especialidades']) && is_array($_POST['especialidades'])) {
                foreach ($_POST['especialidades'] as $id_esp) {
                    $id_esp       = intval($id_esp);
                    $nivel        = mysqli_real_escape_string($conexion, $_POST['nivel_' . $id_esp] ?? 'Básico');
                    $es_principal = ($id_esp === $id_esp_principal) ? 1 : 0;
                    mysqli_query($conexion, "INSERT INTO persona_especialidades (id_persona, id_especialidad, nivel_experiencia, es_principal)
                                            VALUES ('$id_persona', '$id_esp', '$nivel', '$es_principal')");
                }
            }

            echo "<script>document.addEventListener('DOMContentLoaded', function() {
                mostrarMensaje('✓ Perfil actualizado correctamente', 'success');
                setTimeout(function() { window.location.href = 'perfil-trabajador.php'; }, 1500);
            });</script>";
        } else {
            echo "<script>document.addEventListener('DOMContentLoaded', function() {
                mostrarMensaje('❌ Error al actualizar: " . mysqli_error($conexion) . "', 'error');
            });</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - YoConstructor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">

<?php include_once("navbar-trabajador.php"); ?>

<main class="min-h-screen py-8">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white shadow-sm border border-gray-200">
            <div class="lg:grid lg:grid-cols-12 lg:divide-x lg:divide-gray-100">

                <!-- SIDEBAR -->
                <aside class="py-6 lg:col-span-3 bg-gray-50 rounded-l-2xl">
                    <nav class="space-y-1 px-2">
                        <a href="perfil-trabajador.php" class="bg-blue-50 border-blue-600 text-blue-700 group border-l-4 px-3 py-2 flex items-center text-sm font-semibold rounded-r-xl" aria-current="page">
                            <svg class="text-blue-600 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">Mi perfil</span>
                        </a>
                        <a href="mis-postulaciones.php" class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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

                <!-- CONTENIDO PRINCIPAL -->
                <div class="lg:col-span-9">
                    <div class="py-6 px-4 sm:p-6 lg:pb-8">

                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-extrabold text-gray-900">Mi Perfil</h2>
                                <p class="mt-1 text-sm text-gray-500">Información personal y configuración de tu cuenta.</p>
                            </div>
                            <button onclick="abrirModalEditar()"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-md hover:shadow-lg text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar datos
                            </button>
                        </div>

                        <!-- Foto + datos básicos -->
                        <div class="flex flex-col md:flex-row gap-6 mb-8 pb-6 border-b border-gray-100">
                            <div class="flex-shrink-0 flex flex-col items-center gap-2">
                                <div class="w-32 h-32 rounded-2xl overflow-hidden border-2 border-gray-200 shadow-sm">
                                    <img id="foto-preview-vista" class="w-full h-full object-cover"
                                        src="<?= $foto_perfil ?>" alt="Foto de perfil">
                                </div>
                                <span class="text-xs text-gray-400">Foto de perfil</span>
                            </div>

                            <div class="flex-1">
                                <h3 class="text-2xl font-extrabold text-gray-900"><?= htmlspecialchars($nombreCompleto) ?></h3>
                                <?php if (!empty($persona['nombre_titulo'])): ?>
                                    <p class="text-sm text-blue-600 font-semibold mt-1"><?= htmlspecialchars($persona['nombre_titulo']) ?></p>
                                <?php endif; ?>

                                <!-- Especialidad principal destacada -->
                                <?php if ($especialidad_principal): ?>
                                    <div class="mt-2 inline-flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            Especialidad principal
                                        </span>
                                        <span class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($especialidad_principal['nombre']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <p class="text-sm text-gray-500 mt-3 leading-relaxed"><?= htmlspecialchars($persona['descripcion_persona'] ?: 'Sin descripción') ?></p>
                            </div>

                            <!-- CV -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 md:w-64">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Curriculum Vitae</label>
                                <?php if (!empty($persona['curriculum_pdf'])): ?>
                                    <div class="flex items-center gap-3">
                                        <svg class="w-8 h-8 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium text-gray-700 truncate"><?= basename($persona['curriculum_pdf']) ?></p>
                                            <a href="uploads/cv/<?= $persona['curriculum_pdf'] ?>" target="_blank"
                                                class="text-xs text-blue-600 hover:text-blue-700 font-medium">Ver PDF →</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm">No subiste tu CV aún</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Grid de datos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Zona de búsqueda laboral</label>
                                <div class="grid grid-cols-2 gap-4 mt-2">
                                    <div>
                                        <p class="text-xs text-gray-400">Provincia</p>
                                        <p class="text-gray-900 font-semibold text-sm mt-0.5"><?= htmlspecialchars($persona['provincia_preferencia']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">Localidad</p>
                                        <p class="text-gray-900 font-semibold text-sm mt-0.5"><?= htmlspecialchars($persona['localidad_preferencia']) ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Años de experiencia</label>
                                <p class="text-2xl font-extrabold text-blue-600"><?= $persona['anios_experiencia'] ?> <span class="text-sm font-normal text-gray-500">años</span></p>
                            </div>

                            <!-- Especialidades -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Especialidades</label>
                                <?php if (!empty($especialidades_persona)): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($especialidades_persona as $esp): ?>
                                            <?php if ($esp['es_principal']): ?>
                                                <!-- Principal: sin nivel, badge especial -->
                                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white rounded-full text-sm font-semibold shadow-sm">
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                    <?= htmlspecialchars($esp['nombre']) ?>
                                                </div>
                                            <?php else: ?>
                                                <!-- Secundarias: con nivel -->
                                                <?php
                                                $badge_class = match ($esp['nivel']) {
                                                    'Intermedio' => 'bg-green-100 text-green-700',
                                                    'Avanzado'   => 'bg-orange-100 text-orange-700',
                                                    'Experto'    => 'bg-purple-100 text-purple-700',
                                                    default      => 'bg-blue-100 text-blue-700'
                                                };
                                                ?>
                                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border-2 border-blue-200 rounded-full text-sm hover:border-blue-400 transition">
                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($esp['nombre']) ?></span>
                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $badge_class ?>"><?= $esp['nivel'] ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm">No agregaste especialidades aún</p>
                                <?php endif; ?>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Fecha de nacimiento</label>
                                <p class="text-gray-900 font-medium text-sm"><?= $persona['fecha_nacimiento'] ? date('d/m/Y', strtotime($persona['fecha_nacimiento'])) : 'No especificada' ?></p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">DNI</label>
                                <p class="text-gray-900 font-medium text-sm"><?= htmlspecialchars($persona['dni'] ?: 'No especificado') ?></p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Teléfono</label>
                                <p class="text-gray-900 font-medium text-sm"><?= htmlspecialchars($persona['telefono'] ?: 'No especificado') ?></p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Título</label>
                                <p class="text-gray-900 font-medium text-sm"><?= htmlspecialchars($persona['nombre_titulo'] ?: 'Sin título') ?></p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Domicilio</label>
                                <p class="text-gray-900 font-medium text-sm"><?= htmlspecialchars($persona['domicilio'] ?: 'No especificado') ?></p>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- ===== MODAL EDITAR ===== -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">

        <div class="bg-blue-600 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h3 class="text-xl font-extrabold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar Perfil
            </h3>
            <button onclick="cerrarModalEditar()" class="text-white hover:text-blue-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="overflow-y-auto flex-1">
            <div class="p-6 space-y-6">

                <!-- FOTO -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-4">Foto de perfil</label>
                    <div class="flex items-center gap-6">
                        <div class="relative flex-shrink-0">
                            <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-gray-200 shadow-sm">
                                <img id="foto-preview" src="<?= $foto_perfil ?>" alt="Preview" class="w-full h-full object-cover">
                            </div>
                            <label for="imagen_perfil"
                                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-40 rounded-2xl opacity-0 hover:opacity-100 transition cursor-pointer">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </label>
                        </div>
                        <div class="flex-1">
                            <input type="file" name="imagen_perfil" id="imagen_perfil"
                                accept="image/jpeg,image/jpg,image/png,image/webp"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition"
                                onchange="previewFoto(this)">
                            <p class="mt-2 text-xs text-gray-400">JPG, PNG o WEBP · Máximo 2MB</p>
                        </div>
                    </div>
                </div>

                <!-- DATOS PERSONALES -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre *</label>
                        <input type="text" name="nombre" required value="<?= htmlspecialchars($persona['nombre']) ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Apellido *</label>
                        <input type="text" name="apellido" required value="<?= htmlspecialchars($persona['apellido']) ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">DNI *</label>
                        <input type="text" name="dni" required value="<?= htmlspecialchars($persona['dni']) ?>"
                            maxlength="8" pattern="[0-9]{7,8}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm"
                            placeholder="12345678" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,8)">
                        <p class="mt-1 text-xs text-gray-400">Solo números, 7 u 8 dígitos</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($persona['fecha_nacimiento']) ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Teléfono</label>
                        <input type="tel" name="telefono" value="<?= htmlspecialchars($persona['telefono']) ?>"
                            maxlength="20" placeholder="+54 9 11 1234-5678"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Título</label>
                        <input type="text" name="nombre_titulo" value="<?= htmlspecialchars($persona['nombre_titulo']) ?>"
                            maxlength="50" placeholder="Ej: Maestro mayor de obras"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Domicilio</label>
                        <input type="text" name="domicilio" value="<?= htmlspecialchars($persona['domicilio']) ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                    </div>
                    <input type="hidden" name="georeferencia" value="<?= htmlspecialchars($persona['georeferencia']) ?>">
                </div>

                <!-- DESCRIPCIÓN -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descripción personal</label>
                    <textarea name="descripcion_persona" rows="3" maxlength="500"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm"
                        placeholder="Cuéntanos sobre ti..."><?= htmlspecialchars($persona['descripcion_persona']) ?></textarea>
                    <p class="mt-1 text-xs text-gray-400">Máximo 500 caracteres</p>
                </div>

                <!-- CV -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Curriculum Vitae (PDF)</label>
                    <?php if (!empty($persona['curriculum_pdf'])): ?>
                        <div class="mb-3 p-3 bg-gray-50 rounded-xl flex items-center justify-between border border-gray-100">
                            <div class="flex items-center gap-2">
                                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-gray-700"><?= basename($persona['curriculum_pdf']) ?></span>
                            </div>
                            <a href="uploads/cv/<?= $persona['curriculum_pdf'] ?>" target="_blank" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ver actual →</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="curriculum_pdf" accept="application/pdf"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 transition">
                    <p class="mt-1 text-xs text-gray-400">Solo PDF · Máximo 5MB</p>
                </div>

                <!-- ZONA DE BÚSQUEDA -->
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <h4 class="text-sm font-bold text-gray-900 mb-1 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        ¿Dónde buscás trabajo?
                    </h4>
                    <p class="text-xs text-gray-500 mb-3">Las empresas podrán encontrarte según tu zona</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Provincia</label>
                            <select name="id_provincia_preferencia" id="id_provincia_preferencia"
                                onchange="cargarLocalidades(this.value)"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white text-sm">
                                <option value="">Seleccioná una provincia</option>
                                <?php
                                $res_prov = mysqli_query($conexion, "SELECT id_provincia, nombre FROM provincias ORDER BY nombre");
                                while ($prov = mysqli_fetch_assoc($res_prov)):
                                    $sel = ($persona['id_provincia_preferencia'] == $prov['id_provincia']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $prov['id_provincia'] ?>" <?= $sel ?>><?= htmlspecialchars($prov['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Localidad</label>
                            <select name="id_localidad_preferencia" id="id_localidad_preferencia"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white text-sm">
                                <option value="">Seleccioná primero una provincia</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- AÑOS DE EXPERIENCIA -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Años de Experiencia Total</label>
                    <input type="number" name="anios_experiencia" min="0" max="50"
                        value="<?= $persona['anios_experiencia'] ?>"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                    <p class="mt-1 text-xs text-gray-400">Total de años trabajando en construcción</p>
                </div>

                <!-- ESPECIALIDADES -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Especialidades</label>
                    <p class="text-xs text-gray-400 mb-3">
                        Seleccioná tus especialidades, indicá el nivel y marcá cuál es tu <strong>especialidad principal</strong> (solo una).
                    </p>

                    <!-- Hidden para especialidad principal -->
                    <input type="hidden" name="id_especialidad_principal" id="id_especialidad_principal"
                        value="<?= $especialidad_principal ? $especialidad_principal['id_especialidad'] : '' ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-80 overflow-y-auto border border-gray-200 rounded-xl p-4 bg-gray-50">
                        <?php
                        $sql_all_esp = "SELECT id_especialidad, nombre_especialidad FROM especialidades WHERE estado = 1 ORDER BY nombre_especialidad";
                        $res_all_esp = mysqli_query($conexion, $sql_all_esp);
                        $esp_ids     = array_column($especialidades_persona, 'id_especialidad');
                        $esp_niveles = [];
                        $esp_principal_id = $especialidad_principal ? $especialidad_principal['id_especialidad'] : null;
                        foreach ($especialidades_persona as $esp) {
                            $esp_niveles[$esp['id_especialidad']] = $esp['nivel'];
                        }
                        while ($esp = mysqli_fetch_assoc($res_all_esp)):
                            $checked      = in_array($esp['id_especialidad'], $esp_ids);
                            $nivel        = $esp_niveles[$esp['id_especialidad']] ?? 'Básico';
                            $is_principal = ($esp['id_especialidad'] == $esp_principal_id);
                        ?>
                            <div class="bg-white border-2 rounded-xl p-3 transition <?= $is_principal ? 'border-blue-500' : 'border-gray-200 hover:border-blue-200' ?>"
                                 id="card_esp_<?= $esp['id_especialidad'] ?>">

                                <!-- Checkbox + nombre -->
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="especialidades[]"
                                            value="<?= $esp['id_especialidad'] ?>"
                                            id="esp_<?= $esp['id_especialidad'] ?>"
                                            <?= $checked ? 'checked' : '' ?>
                                            onchange="toggleEspecialidad(<?= $esp['id_especialidad'] ?>)"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <label for="esp_<?= $esp['id_especialidad'] ?>" class="text-sm font-semibold text-gray-900 cursor-pointer">
                                            <?= htmlspecialchars($esp['nombre_especialidad']) ?>
                                        </label>
                                    </div>
                                    <!-- Botón principal -->
                                    <button type="button"
                                        id="btn_principal_<?= $esp['id_especialidad'] ?>"
                                        onclick="marcarPrincipal(<?= $esp['id_especialidad'] ?>)"
                                        title="Marcar como principal"
                                        class="<?= $is_principal ? 'text-blue-600' : 'text-gray-300 hover:text-blue-400' ?> transition <?= !$checked ? 'hidden' : '' ?>">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Badge principal o select nivel -->
                                <?php if ($is_principal): ?>
                                    <div id="label_principal_<?= $esp['id_especialidad'] ?>"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        Especialidad principal
                                    </div>
                                    <select name="nivel_<?= $esp['id_especialidad'] ?>" id="nivel_<?= $esp['id_especialidad'] ?>" class="hidden">
                                        <option value="Básico" <?= $nivel === 'Básico' ? 'selected' : '' ?>>Básico</option>
                                        <option value="Intermedio" <?= $nivel === 'Intermedio' ? 'selected' : '' ?>>Intermedio</option>
                                        <option value="Avanzado" <?= $nivel === 'Avanzado' ? 'selected' : '' ?>>Avanzado</option>
                                        <option value="Experto" <?= $nivel === 'Experto' ? 'selected' : '' ?>>Experto</option>
                                    </select>
                                <?php else: ?>
                                    <div id="label_principal_<?= $esp['id_especialidad'] ?>"
                                        class="hidden inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        Especialidad principal
                                    </div>
                                    <select name="nivel_<?= $esp['id_especialidad'] ?>" id="nivel_<?= $esp['id_especialidad'] ?>"
                                        <?= !$checked ? 'disabled' : '' ?>
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition <?= !$checked ? 'bg-gray-100' : 'bg-white' ?>">
                                        <option value="Básico"     <?= $nivel === 'Básico'     ? 'selected' : '' ?>>Básico</option>
                                        <option value="Intermedio" <?= $nivel === 'Intermedio' ? 'selected' : '' ?>>Intermedio</option>
                                        <option value="Avanzado"   <?= $nivel === 'Avanzado'   ? 'selected' : '' ?>>Avanzado</option>
                                        <option value="Experto"    <?= $nivel === 'Experto'    ? 'selected' : '' ?>>Experto</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Tocá la ⭐ de una especialidad seleccionada para marcarla como principal.</p>
                </div>

            </div>

            <!-- Footer modal -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100 flex-shrink-0">
                <button type="button" onclick="cerrarModalEditar()"
                    class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition font-medium text-sm">
                    Descartar cambios
                </button>
                <button type="submit" name="actualizar_perfil"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition font-semibold shadow-md text-sm">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Notificación -->
<div id="mensaje" class="hidden fixed top-4 right-4 z-50 px-6 py-3 rounded-xl shadow-lg font-semibold text-sm"></div>

<!-- Footer -->
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
// ── Modal ──────────────────────────────────────────────────────────────────
function abrirModalEditar() {
    document.getElementById('modalEditar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    abrirModalEditarFoto();
    const provinciaSelect  = document.getElementById('id_provincia_preferencia');
    const localidadActual  = <?= intval($persona['id_localidad_preferencia'] ?? 0) ?>;
    if (provinciaSelect && provinciaSelect.value) {
        cargarLocalidades(provinciaSelect.value, localidadActual || null);
    }
}
let _fotoOriginal = null;

function abrirModalEditarFoto() {
    // Captura el src actual SOLO la primera vez que se abre
    const preview = document.getElementById('foto-preview');
    if (preview && !_fotoOriginal) _fotoOriginal = preview.src;
}

function cerrarModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
    document.body.style.overflow = 'auto';
    // Resetear input file
    const inputFoto = document.getElementById('imagen_perfil');
    if (inputFoto) inputFoto.value = '';
    // Restaurar preview del modal al src original
    const preview = document.getElementById('foto-preview');
    if (preview && _fotoOriginal) preview.src = _fotoOriginal;
}

// ── Notificación ───────────────────────────────────────────────────────────
function mostrarMensaje(texto, tipo) {
    const el = document.getElementById('mensaje');
    el.textContent = texto;
    el.className = 'fixed top-4 right-4 z-[100] px-6 py-3 rounded-xl shadow-xl font-semibold text-sm ' +
        (tipo === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 3500);
}

// ── Foto preview ───────────────────────────────────────────────────────────
function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('foto-preview').src = e.target.result;
            // NO tocamos foto-preview-vista aquí — se actualiza solo al recargar tras guardar
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Especialidades ─────────────────────────────────────────────────────────
function toggleEspecialidad(id) {
    const cb   = document.getElementById('esp_' + id);
    const sel  = document.getElementById('nivel_' + id);
    const btn  = document.getElementById('btn_principal_' + id);
    const card = document.getElementById('card_esp_' + id);

    if (cb.checked) {
        sel.disabled = false;
        sel.classList.replace('bg-gray-100', 'bg-white');
        btn.classList.remove('hidden');
    } else {
        // Si era principal, limpiar
        const hiddenPrincipal = document.getElementById('id_especialidad_principal');
        if (hiddenPrincipal.value == id) {
            hiddenPrincipal.value = '';
        }
        sel.disabled = true;
        sel.classList.replace('bg-white', 'bg-gray-100');
        sel.value = 'Básico';
        btn.classList.add('hidden');
        // Quitar estilo principal
        card.classList.replace('border-blue-500', 'border-gray-200');
        document.getElementById('label_principal_' + id).classList.add('hidden');
        btn.classList.remove('text-blue-600');
        btn.classList.add('text-gray-300');
    }
}

function marcarPrincipal(id) {
    const cb = document.getElementById('esp_' + id);
    if (!cb.checked) return;

    const anterior = document.getElementById('id_especialidad_principal').value;

    // Desmarcar anterior si existe y es distinto
    if (anterior && anterior != id) {
        const cardAnt = document.getElementById('card_esp_' + anterior);
        const btnAnt  = document.getElementById('btn_principal_' + anterior);
        const lblAnt  = document.getElementById('label_principal_' + anterior);
        const selAnt  = document.getElementById('nivel_' + anterior);

        if (cardAnt) cardAnt.classList.replace('border-blue-500', 'border-gray-200');
        if (btnAnt)  { btnAnt.classList.remove('text-blue-600'); btnAnt.classList.add('text-gray-300'); }
        if (lblAnt)  lblAnt.classList.add('hidden');
        // Mostrar select de nivel del anterior
        if (selAnt)  { selAnt.classList.remove('hidden'); }
    }

    // Si toco la misma que ya era principal, la deselecciono
    if (anterior == id) {
        document.getElementById('id_especialidad_principal').value = '';
        const card = document.getElementById('card_esp_' + id);
        const btn  = document.getElementById('btn_principal_' + id);
        const lbl  = document.getElementById('label_principal_' + id);
        const sel  = document.getElementById('nivel_' + id);
        card.classList.replace('border-blue-500', 'border-gray-200');
        btn.classList.remove('text-blue-600'); btn.classList.add('text-gray-300');
        lbl.classList.add('hidden');
        sel.classList.remove('hidden');
        return;
    }

    // Marcar nueva principal
    document.getElementById('id_especialidad_principal').value = id;
    const card = document.getElementById('card_esp_' + id);
    const btn  = document.getElementById('btn_principal_' + id);
    const lbl  = document.getElementById('label_principal_' + id);
    const sel  = document.getElementById('nivel_' + id);

    card.classList.replace('border-gray-200', 'border-blue-500');
    btn.classList.remove('text-gray-300'); btn.classList.add('text-blue-600');
    lbl.classList.remove('hidden');
    // Ocultar select de nivel para la principal
    sel.classList.add('hidden');
}

// ── Localidades ────────────────────────────────────────────────────────────
function cargarLocalidades(idProvincia, localidadSeleccionada = null) {
    const select = document.getElementById('id_localidad_preferencia');
    select.innerHTML = '<option value="">Cargando...</option>';
    if (!idProvincia) {
        select.innerHTML = '<option value="">Seleccioná primero una provincia</option>';
        return;
    }
    fetch('get_localidades.php?id_provincia=' + idProvincia)
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(data => {
            select.innerHTML = '<option value="">Todas las localidades</option>';
            data.forEach(loc => {
                const opt = document.createElement('option');
                opt.value       = loc.id_localidad;
                opt.textContent = loc.nombre_localidad;
                if (localidadSeleccionada && loc.id_localidad == localidadSeleccionada) opt.selected = true;
                select.appendChild(opt);
            });
        })
        .catch(() => { select.innerHTML = '<option value="">Error al cargar localidades</option>'; });
}

// ── Escape / click fuera ───────────────────────────────────────────────────
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalEditar(); });
document.getElementById('modalEditar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEditar();
});
</script>

</body>
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
?>
</html>