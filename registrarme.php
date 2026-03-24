<?php
include("conexion.php");

$modal_html = '';
$form_data = [];
$form_data_empresa = [];

// ============================================
// REGISTRO DE EMPRESA
// ============================================

if (isset($_POST['agregar_empresa'])) {

    $form_data_empresa = [
        'nombre_empresa' => $_POST['nombre_empresa'] ?? '',
        'razon_social'   => $_POST['razon_social'] ?? '',
        'cuit'           => $_POST['cuit'] ?? '',
        'id_provincia'   => $_POST['id_provincia'] ?? '',
        'id_rubro'       => $_POST['id_rubro'] ?? '',
        'email'          => $_POST['email'] ?? '',
        'email_contacto' => $_POST['email_contacto'] ?? '',
        'telefono'       => $_POST['telefono'] ?? ''
    ];

    if ($_POST['contrasena'] !== $_POST['confirmar_contrasena']) {
        $modal_html = modal_error('Las contraseñas no coinciden');
    } elseif (strlen($_POST['contrasena']) < 6 || !preg_match('/[a-zA-Z]/', $_POST['contrasena'])) {
        $modal_html = modal_error('La contraseña debe tener al menos 6 caracteres y contener una letra.');
    } else {
        $nombre_empresa = mysqli_real_escape_string($conexion, $_POST['nombre_empresa']);
        $razon_social   = mysqli_real_escape_string($conexion, $_POST['razon_social']);
        $cuit           = mysqli_real_escape_string($conexion, $_POST['cuit']);
        $id_provincia   = mysqli_real_escape_string($conexion, $_POST['id_provincia']);
        $id_rubro       = mysqli_real_escape_string($conexion, $_POST['id_rubro']);
        $email          = mysqli_real_escape_string($conexion, $_POST['email']);
        $email_contacto = mysqli_real_escape_string($conexion, $_POST['email_contacto']);
        $telefono       = mysqli_real_escape_string($conexion, $_POST['telefono']);
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
        $tipo   = 3;
        $estado = 'activo';

        $sql_verificar = "SELECT u.email, e.cuit FROM users u LEFT JOIN empresa e ON u.id_empresa = e.id_empresa WHERE u.email = '$email' OR e.cuit = '$cuit'";
        $resultado_verificar = mysqli_query($conexion, $sql_verificar);

        if (mysqli_num_rows($resultado_verificar) > 0) {
            $datos_existentes = mysqli_fetch_assoc($resultado_verificar);
            $mensaje_error = '';
            if ($datos_existentes['email'] == $email) $mensaje_error = 'El email ya está registrado';
            if ($datos_existentes['cuit'] == $cuit) {
                $mensaje_error = !empty($mensaje_error) ? 'El email y el CUIT ya están registrados' : 'El CUIT ya está registrado';
            }
            $modal_html = modal_error($mensaje_error);
        } else {
            mysqli_begin_transaction($conexion);
            try {
                $sql_empresa = "INSERT INTO empresa (nombre_empresa, razon_social, id_rubro, cuit, id_provincia, telefono, email_contacto, descripcion_empresa, estado) VALUES ('$nombre_empresa', '$razon_social', '$id_rubro', '$cuit', '$id_provincia', '$telefono', '$email_contacto', '', 'activo')";
                $resultado_empresa = mysqli_query($conexion, $sql_empresa);
                if (!$resultado_empresa) throw new Exception("Error al insertar empresa: " . mysqli_error($conexion));
                $id_empresa = mysqli_insert_id($conexion);
                $sql_usuario = "INSERT INTO users (id_empresa, email, contrasena, tipo, estado) VALUES ('$id_empresa', '$email', '$contrasena', '$tipo', '$estado')";
                $resultado_usuario = mysqli_query($conexion, $sql_usuario);
                if (!$resultado_usuario) throw new Exception("Error al insertar usuario: " . mysqli_error($conexion));
                mysqli_commit($conexion);
                $form_data_empresa = [];
                $modal_html = modal_exito("Empresa registrada correctamente", $nombre_empresa);
            } catch (Exception $e) {
                mysqli_rollback($conexion);
                $modal_html = modal_error('Error al crear la empresa: ' . $e->getMessage());
            }
        }
    }
}

// ============================================
// REGISTRO DE TRABAJADOR
// ============================================

if (isset($_POST['agregar_trabajador'])) {

    $form_data = [
        'nombre'          => $_POST['nombre'] ?? '',
        'apellido'        => $_POST['apellido'] ?? '',
        'email'           => $_POST['email'] ?? '',
        'dni'             => $_POST['dni'] ?? '',
        'id_especialidad' => $_POST['id_especialidad'] ?? ''
    ];

    if ($_POST['contrasena'] !== $_POST['confirmar_contrasena']) {
        $modal_html = modal_error('Las contraseñas no coinciden');
    } elseif (strlen($_POST['contrasena']) < 6 || !preg_match('/[a-zA-Z]/', $_POST['contrasena'])) {
        $modal_html = modal_error('La contraseña debe tener al menos 6 caracteres y contener una letra.');
    } else {
        $nombre          = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $apellido        = mysqli_real_escape_string($conexion, $_POST['apellido']);
        $email           = mysqli_real_escape_string($conexion, $_POST['email']);
        $dni             = mysqli_real_escape_string($conexion, $_POST['dni']);
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);        $id_especialidad = mysqli_real_escape_string($conexion, $_POST['id_especialidad']);
        $tipo   = 2;
        $estado = 'activo';

        $sql_verificar = "SELECT u.email, p.dni FROM users u LEFT JOIN persona p ON u.id_persona = p.id_persona WHERE u.email = '$email' OR p.dni = '$dni'";
        $resultado_verificar = mysqli_query($conexion, $sql_verificar);

        if (mysqli_num_rows($resultado_verificar) > 0) {
            $datos_existentes = mysqli_fetch_assoc($resultado_verificar);
            $mensaje_error = '';
            if ($datos_existentes['email'] == $email) $mensaje_error = 'El email ya está registrado';
            if ($datos_existentes['dni'] == $dni) {
                $mensaje_error = !empty($mensaje_error) ? 'El email y el DNI ya están registrados' : 'El DNI ya está registrado';
            }
            $modal_html = modal_error($mensaje_error);
        } else {
            mysqli_begin_transaction($conexion);
            try {
                $sql_persona = "INSERT INTO persona (nombre, apellido, dni, imagen_perfil) VALUES ('$nombre', '$apellido', '$dni', '')";
                $resultado_persona = mysqli_query($conexion, $sql_persona);
                if (!$resultado_persona) throw new Exception("Error al insertar persona: " . mysqli_error($conexion));
                $id_persona = mysqli_insert_id($conexion);
                $sql_especialidad = "INSERT INTO persona_especialidades (id_persona, id_especialidad) VALUES ('$id_persona', '$id_especialidad')";
                $resultado_especialidad = mysqli_query($conexion, $sql_especialidad);
                if (!$resultado_especialidad) throw new Exception("Error al insertar especialidad: " . mysqli_error($conexion));
                $sql_usuario = "INSERT INTO users (id_persona, email, contrasena, tipo, estado) VALUES ('$id_persona', '$email', '$contrasena', '$tipo', '$estado')";
                $resultado_usuario = mysqli_query($conexion, $sql_usuario);
                if (!$resultado_usuario) throw new Exception("Error al insertar usuario: " . mysqli_error($conexion));
                mysqli_commit($conexion);
                $form_data = [];
                $modal_html = modal_exito("Usuario creado correctamente", "$nombre $apellido");
            } catch (Exception $e) {
                mysqli_rollback($conexion);
                $modal_html = modal_error('Error al crear el usuario: ' . $e->getMessage());
            }
        }
    }
}

function modal_error($mensaje) {
    return "<div id='myModal' class='fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50'><div class='bg-white rounded-lg shadow-xl w-full max-w-md mx-4'><div class='border-b px-6 py-4 bg-red-50'><h5 class='text-lg font-semibold text-red-700 flex items-center'><svg class='w-5 h-5 mr-2' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd'/></svg>Error</h5></div><div class='px-6 py-4'><p class='text-gray-700'>$mensaje</p></div><div class='border-t px-6 py-4 flex justify-end bg-gray-50'><button type='button' class='bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded transition-colors' onclick='document.getElementById(\"myModal\").remove()'>Aceptar</button></div></div></div>";
}

function modal_exito($mensaje, $subtexto = '') {
    $sub = $subtexto ? "<p class='text-sm text-gray-500 mt-2'>$subtexto</p>" : '';
    return "<div id='myModal' class='fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50'><div class='bg-white rounded-lg shadow-xl w-full max-w-md mx-4'><div class='border-b px-6 py-4 bg-green-50'><h5 class='text-lg font-semibold text-green-700 flex items-center'><svg class='w-5 h-5 mr-2' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/></svg>¡Registro Exitoso!</h5></div><div class='px-6 py-4'><p class='text-gray-700'>$mensaje</p>$sub</div><div class='border-t px-6 py-4 flex justify-end bg-gray-50'><button type='button' class='bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded transition-colors' onclick='location.assign(\"login.php\")'>Continuar</button></div></div></div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Plataforma Laboral</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-start md:items-center justify-center p-4 py-8">

    <?php if (!empty($modal_html)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.body.insertAdjacentHTML('beforeend', `<?php echo addslashes($modal_html); ?>`);
                <?php if (!empty($form_data)): ?>
                    document.getElementById('seleccion').classList.add('hidden');
                    document.getElementById('formTrabajador').classList.remove('hidden');
                <?php endif; ?>
                <?php if (!empty($form_data_empresa)): ?>
                    document.getElementById('seleccion').classList.add('hidden');
                    document.getElementById('formEmpresa').classList.remove('hidden');
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

    <div class="w-full max-w-3xl mx-auto">

        <!-- Selección de tipo -->
        <div id="seleccion" class="bg-white rounded-2xl shadow-xl p-8 md:p-12">
            <div class="flex justify-center mb-7">
                <a href="index.php" class="inline-flex items-center gap-2 border border-gray-300 rounded-lg hover:border-gray-400 text-blue-600 hover:bg-gray-50 py-2 px-4 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                    </svg>
                    Volver al home
                </a>
            </div>
            <h1 class="text-2xl md:text-4xl font-bold text-center text-gray-800 mb-3">Crea tu cuenta</h1>
            <p class="text-center text-gray-600 mb-10">Selecciona el tipo de cuenta que deseas crear.</p>
            <div class="grid md:grid-cols-2 gap-6">
                <button onclick="mostrarFormulario('empresa')"
                    class="group bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl p-6 md:p-8 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <h2 class="text-2xl font-bold mb-2">Empresa</h2>
                    <p class="text-blue-100">Busca profesionales cualificados</p>
                </button>
                <button onclick="mostrarFormulario('trabajador')"
                    class="group bg-gradient-to-br from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-xl p-6 md:p-8 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
                    <svg class="w-10 h-10 md:w-16 md:h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <h2 class="text-2xl font-bold mb-2">Trabajador</h2>
                    <p class="text-indigo-100">Encuentra oportunidades laborales</p>
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- Formulario Empresa                        -->
        <!-- ══════════════════════════════════════════ -->
        <div id="formEmpresa" class="bg-white rounded-2xl shadow-xl p-5 md:p-10 hidden overflow-y-auto">
            <button onclick="volverSeleccion()" class="text-gray-600 hover:text-gray-800 mb-6 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Volver
            </button>
            <div class="flex items-center gap-3 mb-8">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl md:text-3xl font-bold text-gray-800">Registro Empresa</h2>
                    <p class="text-gray-500 text-sm">Completá los datos de tu empresa</p>
                </div>
            </div>

            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" onsubmit="return validarFormEmpresa()">

                <!-- Datos de la empresa -->
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-4 pb-1 border-b border-blue-100">Datos de la empresa</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nombre de la Empresa <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre_empresa" required value="<?= htmlspecialchars($form_data_empresa['nombre_empresa'] ?? '') ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                            placeholder="Ej: Construcciones García">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Razón Social <span class="text-gray-400 font-normal text-xs">(Opcional)</span></label>
                        <input type="text" name="razon_social" value="<?= htmlspecialchars($form_data_empresa['razon_social'] ?? '') ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                            placeholder="Ej: Construcciones García S.R.L.">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email de acceso <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($form_data_empresa['email'] ?? '') ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                            placeholder="Para iniciar sesión">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">CUIT <span class="text-red-500">*</span></label>
                        <input type="text" id="cuit" name="cuit" required value="<?= htmlspecialchars($form_data_empresa['cuit'] ?? '') ?>"
                            pattern="[0-9]{2}-[0-9]{8}-[0-9]{1}" maxlength="13"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                            placeholder="30-12345678-9">
                    </div>                
                 
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Provincia <span class="text-red-500">*</span></label>
                        <select name="id_provincia" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="">Seleccioná una provincia</option>
                            <?php
                            $sql = "SELECT id_provincia, nombre FROM provincias WHERE estado = 1 ORDER BY nombre";
                            $resultado = mysqli_query($conexion, $sql);
                            while ($row = mysqli_fetch_assoc($resultado)) {
                                $selected = (isset($form_data_empresa['id_provincia']) && $form_data_empresa['id_provincia'] == $row['id_provincia']) ? 'selected' : '';
                                echo "<option value='{$row['id_provincia']}' $selected>{$row['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Rubro Principal <span class="text-red-500">*</span></label>
                        <select name="id_rubro" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="">Seleccioná un rubro</option>
                            <?php
                            $sql = "SELECT id_rubro, nombre FROM rubros WHERE estado = 1 ORDER BY orden, nombre";
                            $resultado = mysqli_query($conexion, $sql);
                            while ($row = mysqli_fetch_assoc($resultado)) {
                                $selected = (isset($form_data_empresa['id_rubro']) && $form_data_empresa['id_rubro'] == $row['id_rubro']) ? 'selected' : '';
                                echo "<option value='{$row['id_rubro']}' $selected>{$row['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Contacto -->
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-4 pb-1 border-b border-blue-100">Contacto</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Teléfono <span class="text-gray-400 font-normal text-xs">(Opcional)</span></label>
                        <input type="tel" name="telefono" value="<?= htmlspecialchars($form_data_empresa['telefono'] ?? '') ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                            placeholder="Ej: 011-4567-8901">
                    </div>
               
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email de contacto <span class="text-gray-400 font-normal text-xs">(Opcional)</span></label>
                        <input type="email" name="email_contacto" value="<?= htmlspecialchars($form_data_empresa['email_contacto'] ?? '') ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                            placeholder="Visible para trabajadores">
                    </div>
                </div>

                <!-- Contraseña empresa -->
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-4 pb-1 border-b border-blue-100">Contraseña</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="contrasena" id="password-empresa" required
                                oninput="validarPass('password-empresa','confirm-empresa','req-empresa','match-empresa')"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all pr-12 text-sm"
                                placeholder="Mín. 6 caracteres y una letra">
                            <button type="button" onclick="toggleVer('password-empresa')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <div id="req-empresa" class="mt-2 space-y-1 hidden">
                            <div id="req-empresa-len" class="flex items-center gap-2 text-xs text-gray-400">
                                <span class="req-icon w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0"></span>
                                Mínimo 6 caracteres
                            </div>
                            <div id="req-empresa-letra" class="flex items-center gap-2 text-xs text-gray-400">
                                <span class="req-icon w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0"></span>
                                Al menos una letra (a-z)
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Repetir contraseña <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="confirmar_contrasena" id="confirm-empresa" required
                                oninput="validarPass('password-empresa','confirm-empresa','req-empresa','match-empresa')"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all pr-12 text-sm"
                                placeholder="Repetir contraseña">
                            <button type="button" onclick="toggleVer('confirm-empresa')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p id="match-empresa" class="text-xs mt-1 hidden"></p>
                    </div>
                </div>

                <button type="submit" name="agregar_empresa"
                    class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3.5 rounded-lg transition-all duration-300 transform hover:scale-[1.01] shadow-lg hover:shadow-xl text-sm">
                    Registrar Empresa
                </button>
            </form>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- Formulario Trabajador                     -->
        <!-- ══════════════════════════════════════════ -->
        <div id="formTrabajador" class="bg-white rounded-2xl shadow-xl p-5 md:p-10 hidden overflow-y-auto">
            <button onclick="volverSeleccion()" class="text-gray-600 hover:text-gray-800 mb-6 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Volver
            </button>
            <div class="flex items-center gap-3 mb-8">
                <div class="bg-indigo-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl md:text-3xl font-bold text-gray-800">Registro Trabajador</h2>
                    <p class="text-gray-500 text-sm">Completá tu perfil profesional</p>
                </div>
            </div>

            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" onsubmit="return validarFormTrabajador()">

                <!-- Datos personales -->
                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-4 pb-1 border-b border-indigo-100">Datos personales</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" required value="<?= htmlspecialchars($form_data['nombre'] ?? '') ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                            placeholder="Juan">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Apellido <span class="text-red-500">*</span></label>
                        <input type="text" name="apellido" required value="<?= htmlspecialchars($form_data['apellido'] ?? '') ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                            placeholder="Pérez García">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">DNI <span class="text-red-500">*</span></label>
                        <input type="text" id="dni" name="dni" required value="<?= htmlspecialchars($form_data['dni'] ?? '') ?>"
                            maxlength="8"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                            placeholder="12345678">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Especialidad principal <span class="text-red-500">*</span></label>
                        <select name="id_especialidad" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                            <option value="">Seleccioná una especialidad</option>
                            <?php
                            $sql = "SELECT id_especialidad, nombre_especialidad FROM especialidades WHERE estado = 1 ORDER BY nombre_especialidad";
                            $resultado = mysqli_query($conexion, $sql);
                            while ($row = mysqli_fetch_assoc($resultado)) {
                                $selected = (isset($form_data['id_especialidad']) && $form_data['id_especialidad'] == $row['id_especialidad']) ? 'selected' : '';
                                echo "<option value='{$row['id_especialidad']}' $selected>{$row['nombre_especialidad']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Cuenta -->
                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-4 pb-1 border-b border-indigo-100">Cuenta</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($form_data['email'] ?? '') ?>"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                            placeholder="example@mail.com">
                    </div>
                </div>

                <!-- Contraseña trabajador -->
                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-4 pb-1 border-b border-indigo-100">Contraseña</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="contrasena" id="password-trabajador" required
                                oninput="validarPass('password-trabajador','confirm-trabajador','req-trabajador','match-trabajador')"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all pr-12 text-sm"
                                placeholder="Mín. 6 caracteres y una letra">
                            <button type="button" onclick="toggleVer('password-trabajador')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <div id="req-trabajador" class="mt-2 space-y-1 hidden">
                            <div id="req-trabajador-len" class="flex items-center gap-2 text-xs text-gray-400">
                                <span class="req-icon w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0"></span>
                                Mínimo 6 caracteres
                            </div>
                            <div id="req-trabajador-letra" class="flex items-center gap-2 text-xs text-gray-400">
                                <span class="req-icon w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0"></span>
                                Al menos una letra (a-z)
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Repetir contraseña <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="confirmar_contrasena" id="confirm-trabajador" required
                                oninput="validarPass('password-trabajador','confirm-trabajador','req-trabajador','match-trabajador')"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all pr-12 text-sm"
                                placeholder="Repetir contraseña">
                            <button type="button" onclick="toggleVer('confirm-trabajador')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p id="match-trabajador" class="text-xs mt-1 hidden"></p>
                    </div>
                </div>

                <button type="submit" name="agregar_trabajador"
                    class="w-full bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-semibold py-3.5 rounded-lg transition-all duration-300 transform hover:scale-[1.01] shadow-lg hover:shadow-xl text-sm">
                    Registrarme
                </button>
            </form>
        </div>

    </div>

    <script>
    function mostrarFormulario(tipo) {
        document.getElementById('seleccion').classList.add('hidden');
        document.getElementById(tipo === 'empresa' ? 'formEmpresa' : 'formTrabajador').classList.remove('hidden');
    }
    function volverSeleccion() {
        document.getElementById('formEmpresa').classList.add('hidden');
        document.getElementById('formTrabajador').classList.add('hidden');
        document.getElementById('seleccion').classList.remove('hidden');
    }
    function toggleVer(inputId) {
        const input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    function validarPass(passId, confId, reqId, matchId) {
        const pass    = document.getElementById(passId).value;
        const conf    = document.getElementById(confId).value;
        const reqEl   = document.getElementById(reqId);
        const matchEl = document.getElementById(matchId);
        const okLen   = pass.length >= 6;
        const okLetra = /[a-zA-Z]/.test(pass);
        pass.length > 0 ? reqEl.classList.remove('hidden') : reqEl.classList.add('hidden');
        actualizarReq(reqId + '-len',   okLen);
        actualizarReq(reqId + '-letra', okLetra);
        if (conf.length > 0) {
            matchEl.classList.remove('hidden');
            if (pass === conf) {
                matchEl.textContent = '✓ Las contraseñas coinciden';
                matchEl.className   = 'text-xs mt-1 text-green-600 font-medium';
            } else {
                matchEl.textContent = '✗ Las contraseñas no coinciden';
                matchEl.className   = 'text-xs mt-1 text-red-500 font-medium';
            }
        } else {
            matchEl.classList.add('hidden');
        }
    }
    function actualizarReq(rowId, cumple) {
        const row = document.getElementById(rowId);
        if (!row) return;
        const icon = row.querySelector('.req-icon');
        if (cumple) {
            row.classList.remove('text-gray-400'); row.classList.add('text-green-600', 'font-medium');
            icon.classList.remove('border-gray-300'); icon.classList.add('border-green-500', 'bg-green-500');
            icon.innerHTML = '<svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
        } else {
            row.classList.add('text-gray-400'); row.classList.remove('text-green-600', 'font-medium');
            icon.classList.add('border-gray-300'); icon.classList.remove('border-green-500', 'bg-green-500');
            icon.innerHTML = '';
        }
    }
    function validarFormEmpresa()   { return validarAlSubmit('password-empresa',    'confirm-empresa'); }
    function validarFormTrabajador(){ return validarAlSubmit('password-trabajador', 'confirm-trabajador'); }
    function validarAlSubmit(passId, confId) {
        const pass = document.getElementById(passId).value;
        const conf = document.getElementById(confId).value;
        if (pass.length < 6 || !/[a-zA-Z]/.test(pass)) { alert('La contraseña debe tener al menos 6 caracteres y contener una letra.'); return false; }
        if (pass !== conf) { alert('Las contraseñas no coinciden.'); return false; }
        return true;
    }
    document.getElementById('cuit')?.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length > 2)  v = v.substring(0,2)  + '-' + v.substring(2);
        if (v.length > 11) v = v.substring(0,11) + '-' + v.substring(11);
        e.target.value = v.substring(0,13);
    });
    document.getElementById('dni')?.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g,'').substring(0,8);
    });
    </script>
</body>
</html>