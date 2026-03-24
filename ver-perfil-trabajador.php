<?php
$from = $_GET['from'] ?? '';
$id_oferta_back = intval($_GET['oferta'] ?? 0);

// Sidebar activo según origen
if ($from === 'postulantes') {
    $page = 'ofertas-publicadas'; // marca "Publicadas" en submenu Ofertas
} elseif ($from === 'postulantes-global') {
    $page = 'postulantes-global'; // marca "Postulantes"
} else {
    $page = 'buscar'; // marca "Buscar Perfiles"
}
$pageTitle = 'Ver perfil';
include("conexion.php");

$from    = $_GET['from'] ?? '';
$id_oferta_back = intval($_GET['oferta'] ?? 0);

$back_url   = 'buscar-perfiles.php'; // default
$back_label = 'Volver';

if ($from === 'postulantes' && $id_oferta_back) {
    $back_url   = "postulantes.php?id=$id_oferta_back";
    $back_label = 'Volver a postulantes';
} elseif ($from === 'postulantes-global') {
    $back_url   = 'postulantes-global.php';
    $back_label = 'Volver a postulantes';
} elseif ($from === 'buscar-perfiles') {
    $back_url   = 'buscar-perfiles.php';
    $back_label = 'Volver a búsqueda';
}

$id_empresa = $_SESSION['idempresa'] ?? null;
if (!$id_empresa) {
    header("Location: login.php");
    exit;
}

$id_persona = intval($_GET['id'] ?? 0);
if (!$id_persona) {
    header("Location: buscar-perfiles.php");
    exit;
}



// --- Query persona + usuario ---
$sql_p = "SELECT
            p.id_persona, p.nombre, p.apellido, p.dni,
            p.descripcion_persona, p.anios_experiencia,
            p.curriculum_pdf, p.domicilio, p.telefono,
            p.fecha_nacimiento, p.nombre_titulo, p.imagen_perfil,
            p.georeferencia,
            p.id_provincia_preferencia,
            p.id_localidad_preferencia,
            u.email, u.id_usuario,
            prov.nombre  AS nombre_provincia,
            loc.nombre_localidad AS nombre_localidad
          FROM persona p
          INNER JOIN users u        ON u.id_persona      = p.id_persona
          LEFT JOIN provincias prov ON prov.id_provincia = p.id_provincia_preferencia
          LEFT JOIN localidades loc ON loc.id_localidad  = p.id_localidad_preferencia
          WHERE p.id_persona = $id_persona
            AND u.tipo = 2
            AND u.estado != 'inactivo'
          LIMIT 1";

$res_p = mysqli_query($conexion, $sql_p);
if (!$res_p) die("<b>ERROR:</b> " . mysqli_error($conexion));
if (mysqli_num_rows($res_p) === 0) {
    header("Location: buscar-perfiles.php");
    exit;
}
$per = mysqli_fetch_assoc($res_p);

// --- Especialidades: principal primero, luego las demás ---
$especialidades = [];
$res_esp = mysqli_query(
    $conexion,
    "SELECT e.nombre_especialidad, pe.nivel_experiencia, pe.es_principal
     FROM persona_especialidades pe
     INNER JOIN especialidades e ON pe.id_especialidad = e.id_especialidad
     WHERE pe.id_persona = $id_persona
     ORDER BY pe.es_principal DESC, pe.fecha_agregado ASC"
);
while ($r = mysqli_fetch_assoc($res_esp)) $especialidades[] = $r;

// Separar principal de las otras
$esp_principal = null;
$otras_esps    = [];
foreach ($especialidades as $esp) {
    if ($esp['es_principal'] && !$esp_principal) {
        $esp_principal = $esp;
    } else {
        $otras_esps[] = $esp;
    }
}
// Si ninguna tiene es_principal=1, la primera es la principal igual
if (!$esp_principal && !empty($especialidades)) {
    $esp_principal = array_shift($especialidades);
    $otras_esps    = $especialidades;
}

// --- Ya tiene postulaciones en esta empresa? ---
$res_contact = mysqli_query(
    $conexion,
    "SELECT COUNT(*) as total FROM postulaciones po
     INNER JOIN ofertas_laborales o ON po.id_oferta = o.id_oferta
     WHERE po.id_persona = $id_persona AND o.id_empresa = $id_empresa"
);
$ya_contactado = mysqli_fetch_assoc($res_contact)['total'] ?? 0;

// --- Helpers ---
$nombre_completo = htmlspecialchars(ucwords(strtolower($per['nombre'] . ' ' . $per['apellido'])));
$foto = !empty($per['imagen_perfil']) ? 'uploads/perfil/' . $per['imagen_perfil'] : './img/profile.png';
$cv   = !empty($per['curriculum_pdf'])
    ? (str_starts_with($per['curriculum_pdf'], 'uploads/') ? $per['curriculum_pdf'] : 'uploads/cv/' . $per['curriculum_pdf'])
    : null;
$initials = strtoupper(substr($per['nombre'], 0, 1) . substr($per['apellido'], 0, 1));

$edad = null;
if (!empty($per['fecha_nacimiento'])) {
    $edad = (int) date_diff(date_create($per['fecha_nacimiento']), date_create('today'))->y;
}

// Color por nivel
function nivel_color($nivel)
{
    return match ($nivel) {
        'Básico'      => 'bg-gray-100 text-gray-600 border-gray-300',
        'Intermedio'  => 'bg-blue-50 text-blue-600 border-blue-200',
        'Avanzado'    => 'bg-purple-50 text-purple-600 border-purple-200',
        'Experto'     => 'bg-amber-50 text-amber-600 border-amber-200',
        default       => 'bg-gray-100 text-gray-500 border-gray-200',
    };
}

include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-10">

    <!-- Breadcrumb + título -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-400 mb-1">
                <a href="buscar-perfiles.php" class="hover:text-indigo-600 transition">Buscar perfiles</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-600 font-medium truncate max-w-xs"><?= $nombre_completo ?></span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Perfil del trabajador</h1>
        </div>
        <a href="<?= $back_url ?>"
            class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <?= $back_label ?>
        </a>
    </div>

    <div class="max-w-5xl mx-auto space-y-6">

        <!-- ===== HERO CARD ===== -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-5">

                <!-- Foto + nombre + botón -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
                    <div class="flex items-center gap-4">
                        <div class="relative flex-shrink-0">
                            <img src="<?= $foto ?>"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                class="w-16 h-16 rounded-xl object-cover border-2 border-gray-200" alt="foto">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-cyan-500 to-indigo-600 items-center justify-center text-white font-bold text-xl border-2 border-gray-200 hidden">
                                <?= $initials ?>
                            </div>
                            <?php if ($ya_contactado > 0): ?>
                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white" title="Ya postulado a tu empresa"></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900"><?= $nombre_completo ?></h1>
                            <?php if ($per['nombre_titulo']): ?>
                                <p class="text-sm text-gray-500 mt-0.5"><?= htmlspecialchars($per['nombre_titulo']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                  
                </div>

                <!-- ── Chips rápidos ── -->
                <div class="space-y-2.5">

                    <!-- Especialidad principal -->
                    <?php if ($esp_principal): ?>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide whitespace-nowrap">Especialidad:</span>
                            <span class="inline-flex items-center gap-1.5 text-xs bg-orange-50 text-orange-700 px-3 py-1 rounded-full border border-orange-300 font-semibold">
                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?= htmlspecialchars($esp_principal['nombre_especialidad']) ?>
                            </span>
                           
                        </div>
                    <?php endif; ?>

                    <!-- Otras especialidades -->
                    <?php if (!empty($otras_esps)): ?>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide whitespace-nowrap">Otras especialidades:</span>
                            <?php foreach ($otras_esps as $esp): ?>


                                <span class="inline-flex items-center gap-1.5 text-xs bg-orange-50/70 text-orange-600 px-3 py-1 rounded-full border border-orange-200 font-medium">
                                    <?= htmlspecialchars($esp['nombre_especialidad']) ?>
                                    <?php if (!empty($esp['nivel_experiencia'])): ?>
                                        <span class="<?= match ($esp['nivel_experiencia']) {
                                                            'Básico'     => 'bg-gray-200 text-gray-500',
                                                            'Intermedio' => 'bg-blue-100 text-blue-600',
                                                            'Avanzado'   => 'bg-purple-100 text-purple-600',
                                                            'Experto'    => 'bg-amber-100 text-amber-600',
                                                            default      => 'bg-gray-100 text-gray-500',
                                                        } ?> text-xs px-1.5 py-0.5 rounded-full font-semibold"><?= htmlspecialchars($esp['nivel_experiencia']) ?></span>
                                    <?php endif; ?>
                                </span>

                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Experiencia + ubicación + edad -->
                    <div class="flex flex-wrap gap-2 pt-1">
                        <?php if ($per['anios_experiencia']): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-cyan-50 text-cyan-700 px-3 py-1 rounded-full border border-cyan-200 font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                                <?= intval($per['anios_experiencia']) ?> año<?= $per['anios_experiencia'] != 1 ? 's' : '' ?> de experiencia
                            </span>
                        <?php endif; ?>
                        <?php if ($per['nombre_provincia']): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Busca trabajo en: <?= htmlspecialchars($per['nombre_provincia']) ?>
                                <?= $per['nombre_localidad'] ? ' · ' . htmlspecialchars($per['nombre_localidad']) : '' ?>
                            </span>
                        <?php endif; ?>
                      
                    </div>

                </div>
            </div>
        </div>

        <!-- ===== DOS COLUMNAS ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- COLUMNA IZQUIERDA: Descripción + CV -->
            <div class="lg:col-span-2 space-y-6">

                <?php if ($per['descripcion_persona']): ?>
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
                        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Sobre el trabajador
                        </h2>
                        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"><?= htmlspecialchars($per['descripcion_persona']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($cv):
                    $cv_filename = basename($cv); ?>
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Currículum
                        </h2>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-xl">
                            <div class="w-12 h-12 bg-red-50 border border-red-200 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17h8v-1H8v1zm0-3h8v-1H8v1zm0-3h5v-1H8v1z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($cv_filename) ?></p>
                                <p class="text-xs text-gray-400 mt-0.5">Archivo PDF</p>
                            </div>
                            <a href="<?= $cv ?>" download target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descargar
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white border border-dashed border-gray-300 rounded-2xl p-8 text-center">
                        <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-400 text-sm font-medium">Sin CV adjunto</p>
                    </div>
                <?php endif; ?>

            </div>

            <!-- COLUMNA DERECHA: Contacto + datos personales -->
            <div class="space-y-5">

                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Contacto
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="w-8 h-8 bg-cyan-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Email</p>
                                <a href="mailto:<?= htmlspecialchars($per['email']) ?>" class="text-sm font-medium text-cyan-600 hover:underline truncate block"><?= htmlspecialchars($per['email']) ?></a>
                            </div>
                            <button onclick="copiar('val-email')" class="flex-shrink-0 text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition" title="Copiar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                            <span id="val-email" class="hidden"><?= htmlspecialchars($per['email']) ?></span>
                        </div>

                        <?php if ($per['telefono']): ?>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                <div class="w-8 h-8 bg-cyan-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-400 mb-0.5">Teléfono</p>
                                    <p id="val-tel" class="text-sm font-medium text-gray-800"><?= htmlspecialchars($per['telefono']) ?></p>
                                </div>
                                <button onclick="copiar('val-tel')" class="flex-shrink-0 text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition" title="Copiar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($per['domicilio']): ?>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                                <div class="w-8 h-8 bg-cyan-50 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-400 mb-0.5">Domicilio</p>
                                    <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($per['domicilio']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button onclick="abrirContacto()"
                        class="w-full mt-4 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Enviar email
                    </button>
                </div>

                <!-- Datos personales — sin bloque de especialidades al final -->
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                        Datos personales
                    </h2>
                    <dl class="space-y-3 text-sm">
                        <?php if ($per['dni']): ?>
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-400">DNI</dt>
                                <dd class="font-medium text-gray-700 text-right"><?= htmlspecialchars($per['dni']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($edad): ?>
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-400">Edad</dt>
                                <dd class="font-medium text-gray-700"><?= $edad ?> años</dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($per['fecha_nacimiento']): ?>
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-400">Nacimiento</dt>
                                <dd class="font-medium text-gray-700"><?= date('d/m/Y', strtotime($per['fecha_nacimiento'])) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($per['anios_experiencia']): ?>
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-400">Experiencia</dt>
                                <dd class="font-medium text-gray-700"><?= intval($per['anios_experiencia']) ?> año<?= $per['anios_experiencia'] != 1 ? 's' : '' ?></dd>
                            </div>
                        <?php endif; ?>
                      
                        <?php if ($per['nombre_titulo']): ?>
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-400">Título</dt>
                                <dd class="font-medium text-gray-700 text-right"><?= htmlspecialchars($per['nombre_titulo']) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- MODAL CONTACTAR -->
<div id="modalContacto" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Contactar a <?= $nombre_completo ?></h3>
            <button onclick="cerrarContacto()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="px-6 py-5 space-y-3">
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <svg class="w-5 h-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-400">Email</p>
                    <a href="mailto:<?= htmlspecialchars($per['email']) ?>" class="text-sm font-medium text-cyan-600 hover:underline truncate block"><?= htmlspecialchars($per['email']) ?></a>
                </div>
                <button onclick="copiar('val-email')" class="flex-shrink-0 text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
            <?php if ($per['telefono']): ?>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-400">Teléfono</p>
                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($per['telefono']) ?></p>
                    </div>
                    <button onclick="copiar('val-tel')" class="flex-shrink-0 text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl flex justify-between items-center gap-3">
            <a href="mailto:<?= htmlspecialchars($per['email']) ?>"
                class="flex-1 text-center inline-flex items-center justify-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Abrir cliente de mail
            </a>
            <button onclick="cerrarContacto()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-100 transition">Cerrar</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-5 right-5 z-50 bg-gray-800 text-white text-sm px-5 py-3 rounded-xl shadow-lg flex items-center gap-2">
    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
    <span id="toast-msg"></span>
</div>

<script>
    function abrirContacto() {
        document.getElementById('modalContacto').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarContacto() {
        document.getElementById('modalContacto').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function copiar(id) {
        const txt = document.getElementById(id).textContent.trim();
        navigator.clipboard.writeText(txt).then(() => mostrarToast('Copiado al portapapeles'));
    }

    function mostrarToast(msg) {
        document.getElementById('toast-msg').textContent = msg;
        const t = document.getElementById('toast');
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

<?php
if (isset($_SESSION)) {
    echo "<script>console.group('🔐 Sesión');</script>";
    foreach ($_SESSION as $k => $v) {
        $val = is_array($v) ? json_encode($v) : "'" . addslashes($v ?? '') . "'";
        echo "<script>console.log('{$k}:', {$val});</script>";
    }
    echo "<script>console.groupEnd();</script>";
}
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR]))
        echo "<script>console.error('💥 " . addslashes($e['message']) . " — " . basename($e['file']) . ":" . $e['line'] . "');</script>";
});
?>