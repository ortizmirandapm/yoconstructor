<?php


$page = 'publicar-oferta';
$pageTitle = 'Publicar oferta laboral';
include("conexion.php");
include("sidebar-empresa.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$especialidades = [];
$res_esp = mysqli_query($conexion, "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
if ($res_esp) {
    while ($row = mysqli_fetch_assoc($res_esp)) {
        $especialidades[] = $row;
    }
}

// Obtener provincias
$provincias = [];
$result2 = mysqli_query($conexion, "SELECT id_provincia, nombre FROM provincias ORDER BY nombre ASC");
if ($result2) {
    while ($row = mysqli_fetch_assoc($result2)) {
        $provincias[] = $row;
    }
}

// Obtener localidades (opcional, para cargar dinámicamente)
$localidades = [];

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $titulo          = trim($_POST['titulo'] ?? '');
    $descripcion     = trim($_POST['descripcion'] ?? '');
    $requisitos      = trim($_POST['requisitos'] ?? '');
    $id_especialidad = intval($_POST['id_especialidad'] ?? 0);
    $id_provincia    = !empty($_POST['id_provincia']) ? intval($_POST['id_provincia']) : null;
    $id_localidad    = !empty($_POST['id_localidad']) ? intval($_POST['id_localidad']) : null;
    $modalidad       = $_POST['modalidad'] ?? 'Presencial';
    $tipo_contrato   = $_POST['tipo_contrato'] ?? 'Tiempo completo';
    $salario_min     = ($_POST['salario_min'] ?? '') !== '' ? floatval($_POST['salario_min']) : null;
    $salario_max     = ($_POST['salario_max'] ?? '') !== '' ? floatval($_POST['salario_max']) : null;
    $experiencia     = ($_POST['experiencia_requerida'] ?? '') !== '' ? intval($_POST['experiencia_requerida']) : null;
    $fecha_venc      = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $id_empresa  = $_SESSION['idempresa']  ?? null;
    $id_usuario  = $_SESSION['idusuario']  ?? null;

    // 1. Verificar ID Empresa desde la sesión
    $id_empresa = $_SESSION['idempresa'] ?? null;

    if (!$id_empresa) {
        $error = 'Error de sesión. id_empresa no encontrado en $_SESSION.';
    } elseif (empty($titulo) || empty($descripcion) || $id_especialidad === 0) {
        $error = 'Por favor completa los campos obligatorios.';
    } else {
        mysqli_begin_transaction($conexion);

        try {

            $sql = "INSERT INTO ofertas_laborales 
        (id_empresa, titulo, descripcion, id_especialidad, requisitos, 
         salario_min, salario_max, tipo_contrato, modalidad, 
         id_provincia, id_localidad, experiencia_requerida, fecha_vencimiento, estado) 

        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activa')";



            $stmt = mysqli_prepare($conexion, $sql);

            // 13 parámetros: i s s i s d d s s i i i s
            // id_empresa(i), titulo(s), descripcion(s), id_especialidad(i), requisitos(s),
            // salario_min(d), salario_max(d), tipo_contrato(s), modalidad(s),
            // id_provincia(i), id_localidad(i), experiencia(i), fecha_venc(s)

            $tipos = "issisddsssiis";

            mysqli_stmt_bind_param(
                $stmt,
                $tipos,
                $id_empresa,
                $titulo,
                $descripcion,
                $id_especialidad,
                $requisitos,
                $salario_min,
                $salario_max,
                $tipo_contrato,
                $modalidad,
                $id_provincia,
                $id_localidad,
                $experiencia,
                $fecha_venc
            );;

            mysqli_stmt_execute($stmt);
            $id_nueva_oferta = mysqli_insert_id($conexion);  // ← acá, recién ejecutado el stmt
            mysqli_stmt_close($stmt);

            registrar_auditoria(
                $conexion,
                $id_usuario,
                $id_empresa,
                'publicar_oferta',
                'oferta',
                $id_nueva_oferta,
                "Publicó oferta: $titulo"
            );

            mysqli_commit($conexion);
            $success = true;
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            $error = 'Error crítico: ' . $e->getMessage();
        }
    }
}

?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '¡Oferta publicada!',
                text: 'La oferta fue creada exitosamente y ya está visible para los trabajadores.',
                confirmButtonText: 'Ver mis ofertas',
                confirmButtonColor: '#0891b2',
                showCancelButton: true,
                cancelButtonText: 'Publicar otra',
                cancelButtonColor: '#6b7280',
            }).then((result) => {
                window.location.href = result.isConfirmed ? 'ofertas-publicadas.php' : 'nueva-oferta.php';
            });
        });
    </script>
<?php endif; ?>

<!-- Contenido Principal -->
<div class="min-h-screen bg-gray-50 p-6 md:p-10">

    <div class="mb-8">

        <p class="text-gray-500 mt-1">Completá el formulario para publicar una nueva búsqueda de personal.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-lg">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="formOferta">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- COLUMNA IZQUIERDA -->
            <div class="xl:col-span-2 space-y-6">

                <!-- Información principal -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h2 class="font-semibold text-gray-700">Información principal</h2>
                    </div>
                    <div class="p-6 space-y-5">

                        <div>
                            <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Título de la oferta <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="titulo" name="titulo"
                                value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
                                placeholder="Ej: Electricista matriculado para obra residencial"
                                maxlength="200" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-400 mt-1">Máximo 200 caracteres. Sé específico para atraer mejores candidatos.</p>
                        </div>

                        <div>
                            <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Descripción del puesto <span class="text-red-500">*</span>
                            </label>
                            <textarea id="descripcion" name="descripcion" rows="5" required
                                placeholder="Describí las tareas, el ambiente de trabajo, las condiciones generales del puesto..."
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition resize-none"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label for="requisitos" class="block text-sm font-medium text-gray-700 mb-1.5">Requisitos</label>
                            <textarea id="requisitos" name="requisitos" rows="3"
                                placeholder="Ej: Matrícula habilitante, experiencia mínima, herramientas propias..."
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition resize-none"><?= htmlspecialchars($_POST['requisitos'] ?? '') ?></textarea>
                        </div>

                    </div>
                </div>

                <!-- Especialidad -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h2 class="font-semibold text-gray-700">Especialidad requerida</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                            <div>
                                <label for="id_especialidad" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Especialidad <span class="text-red-500">*</span>
                                </label>
                                <select id="id_especialidad" name="id_especialidad" required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                    <option value="">-- Seleccioná una especialidad --</option>
                                    <?php foreach ($especialidades as $esp): ?>
                                        <option value="<?= $esp['id_especialidad'] ?>"
                                            <?= (($_POST['id_especialidad'] ?? '') == $esp['id_especialidad']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($esp['nombre_especialidad']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="experiencia_requerida" class="block text-sm font-medium text-gray-700 mb-1.5">Años de experiencia</label>
                                <input type="number" id="experiencia_requerida" name="experiencia_requerida"
                                    value="<?= htmlspecialchars($_POST['experiencia_requerida'] ?? '') ?>"
                                    placeholder="Ej: 2" min="0" max="50"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Condiciones laborales -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="font-semibold text-gray-700">Condiciones laborales</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                            <div>
                                <label for="tipo_contrato" class="block text-sm font-medium text-gray-700 mb-1.5">Tipo de contrato</label>
                                <select id="tipo_contrato" name="tipo_contrato"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                    <?php
                                    $contratos = ['Tiempo completo', 'Medio tiempo', 'Por proyecto', 'Pasantía'];
                                    foreach ($contratos as $c):
                                        $sel = (($_POST['tipo_contrato'] ?? 'Tiempo completo') === $c) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $c ?>" <?= $sel ?>><?= $c ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="modalidad" class="block text-sm font-medium text-gray-700 mb-1.5">Modalidad</label>
                                <select id="modalidad" name="modalidad"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                    <?php
                                    $modalidades = ['Presencial', 'Remoto', 'Híbrido'];
                                    foreach ($modalidades as $m):
                                        $sel = (($_POST['modalidad'] ?? 'Presencial') === $m) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $m ?>" <?= $sel ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="salario_min" class="block text-sm font-medium text-gray-700 mb-1.5">Salario mínimo (ARS)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                                    <input type="number" id="salario_min" name="salario_min"
                                        value="<?= htmlspecialchars($_POST['salario_min'] ?? '') ?>"
                                        placeholder="0" min="0"
                                        class="w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                </div>
                            </div>

                            <div>
                                <label for="salario_max" class="block text-sm font-medium text-gray-700 mb-1.5">Salario máximo (ARS)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                                    <input type="number" id="salario_max" name="salario_max"
                                        value="<?= htmlspecialchars($_POST['salario_max'] ?? '') ?>"
                                        placeholder="0" min="0"
                                        class="w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Opcional. Dejalo vacío para no mostrarlo.</p>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- COLUMNA DERECHA -->
            <div class="space-y-6">

                <!-- Ubicación -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h2 class="font-semibold text-gray-700">Ubicación</h2>
                    </div>
                    <div class="p-6 space-y-4">

                        <div>
                            <label for="id_provincia" class="block text-sm font-medium text-gray-700 mb-1.5">Provincia</label>
                            <select id="id_provincia" name="id_provincia" onchange="cargarLocalidades(this.value)"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                <option value="">-- Seleccioná provincia --</option>
                                <?php foreach ($provincias as $prov): ?>
                                    <option value="<?= $prov['id_provincia'] ?>"
                                        <?= (($_POST['id_provincia'] ?? '') == $prov['id_provincia']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prov['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="id_localidad" class="block text-sm font-medium text-gray-700 mb-1.5">Localidad</label>
                            <select id="id_localidad" name="id_localidad"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                <option value="">-- Seleccioná primero una provincia --</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Fechas -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 bg-rose-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="font-semibold text-gray-700">Fechas</h2>
                    </div>
                    <div class="p-6">
                        <label for="fecha_vencimiento" class="block text-sm font-medium text-gray-700 mb-1.5">Cierre de postulaciones</label>
                        <input type="date" id="fecha_vencimiento" name="fecha_vencimiento"
                            value="<?= htmlspecialchars($_POST['fecha_vencimiento'] ?? '') ?>"
                            min="<?= date('Y-m-d') ?>"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                        <p class="text-xs text-gray-400 mt-1">La oferta se cerrará automáticamente en esta fecha.</p>
                    </div>
                </div>

                <!-- Panel publicar -->
                <div class="bg-gradient-to-br from-cyan-600 to-cyan-700 rounded-xl shadow-sm text-white p-6">
                    <h2 class="font-semibold text-lg mb-2">¿Listo para publicar?</h2>
                    <p class="text-cyan-100 text-sm mb-5">Tu oferta será visible inmediatamente para los trabajadores registrados.</p>
                    <ul class="space-y-2 mb-6 text-sm">
                        <li class="flex items-center gap-2 text-cyan-50">
                            <svg class="w-4 h-4 text-cyan-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Estado: <strong class="text-white ml-1">Activa</strong> automáticamente
                        </li>
                        <li class="flex items-center gap-2 text-cyan-50">
                            <svg class="w-4 h-4 text-cyan-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Podrás pausarla o editarla después
                        </li>
                        <li class="flex items-center gap-2 text-cyan-50">
                            <svg class="w-4 h-4 text-cyan-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Campos con <span class="text-red-300 mx-1">*</span> son obligatorios
                        </li>
                    </ul>

                    <button type="submit" form="formOferta" id="btnPublicar"
                        class="w-full bg-white text-cyan-700 font-semibold py-3 px-6 rounded-lg hover:bg-cyan-50 focus:ring-4 focus:ring-white/30 transition text-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Publicar oferta
                    </button>

                    
                </div>

            </div>
        </div>
    </form>
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

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('-translate-x-full');
    }

    function toggleSubmenu(id) {
        const submenu = document.getElementById('submenu-' + id);
        const arrow = document.getElementById('arrow-' + id);
        submenu.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180');
    }

    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isMenuButton = event.target.closest('button[onclick="toggleSidebar()"]');
        if (!isClickInsideSidebar && !isMenuButton && window.innerWidth < 768) {
            sidebar.classList.add('-translate-x-full');
        }
    });

    // ✅ Función para cargar localidades dinámicamente
    function cargarLocalidades(idProvincia) {
        const selectLocalidad = document.getElementById('id_localidad');

        selectLocalidad.innerHTML = '<option value="">Cargando...</option>';

        if (!idProvincia) {
            selectLocalidad.innerHTML = '<option value="">-- Seleccioná primero una provincia --</option>';
            return;
        }

        fetch('get_localidades.php?id_provincia=' + idProvincia)
            .then(response => response.json())
            .then(data => {
                selectLocalidad.innerHTML = '<option value="">-- Todas las localidades --</option>';

                if (data.length === 0) {
                    selectLocalidad.innerHTML = '<option value="">No hay localidades disponibles</option>';
                } else {
                    data.forEach(loc => {
                        const option = document.createElement('option');
                        option.value = loc.id_localidad;
                        option.textContent = loc.nombre_localidad;
                        selectLocalidad.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                selectLocalidad.innerHTML = '<option value="">Error al cargar localidades</option>';
            });
    }

    // Validación salario antes de enviar
    document.getElementById('formOferta').addEventListener('submit', function(e) {
        const min = parseFloat(document.getElementById('salario_min').value) || 0;
        const max = parseFloat(document.getElementById('salario_max').value) || 0;

        if (min > 0 && max > 0 && min > max) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Salario inválido',
                text: 'El salario mínimo no puede ser mayor al salario máximo.',
                confirmButtonColor: '#0891b2'
            });
            return;
        }

        // Spinner en botón
        const btn = document.getElementById('btnPublicar');
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Publicando...
        `;
    });
</script>
</body>

</html>

<?php
// AL FINAL de index.php, antes de cerrar
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

// Capturar errores fatales
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $mensaje = addslashes($error['message'] ?? '');
        $archivo = addslashes(basename($error['file'] ?? ''));
        $linea = $error['line'] ?? 0;
        echo "<script>console.error('💥 Error Fatal:', '{$mensaje}');</script>";
        echo "<script>console.error('📁 Archivo: {$archivo} | Línea: {$linea}');</script>";
    }
});
