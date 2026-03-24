<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("conexion.php");
include_once("notificaciones_helper.php");

$error_msg = '';

if (isset($_POST['enviar'])) {
    if (empty($_POST['email']) || empty($_POST['contrasena'])) {
        $error_msg = 'El email y la contraseña son obligatorios.';
    } else {
        $email      = mysqli_real_escape_string($conexion, $_POST['email']);
        $contrasena = $_POST['contrasena'];

        // Primero traemos el usuario solo por email
        $sql = "SELECT u.*, tu.nombre AS tipo_nombre 
                FROM users u 
                INNER JOIN tipo_usuario tu ON u.tipo = tu.id_tipo 
                WHERE u.email = '$email'
                LIMIT 1";

        $resultado = mysqli_query($conexion, $sql);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $reg = mysqli_fetch_assoc($resultado);

            // Verificamos la contraseña con password_verify
            if (!password_verify($contrasena, $reg['contrasena'])) {
                $error_msg = 'El email o contraseña son incorrectos o no están registrados.';
            } elseif (strtolower($reg['estado']) !== 'activo') {
                $error_msg = 'Tu cuenta está desactivada. Contactá al soporte para reactivarla.';
            } else {
                $_SESSION['idusuario']     = $reg['id_usuario'];
                $_SESSION['idpersona']     = $reg['id_persona'];
                $_SESSION['idempresa']     = $reg['id_empresa'];
                $_SESSION['nombreusuario'] = $reg['usuario'];
                $_SESSION['emailusuario']  = $reg['email'];
                $_SESSION['tipo']          = $reg['tipo'];
                $_SESSION['tipo_nombre']   = $reg['tipo_nombre'];

                // ── NOTIFICACIONES AL LOGIN (solo trabajadores) ──────────────
                if ($reg['tipo'] == 2) {
                    $id_u = intval($reg['id_usuario']);
                    $id_p = intval($reg['id_persona']);
                    chequear_perfil_incompleto($conexion, $id_u, $id_p);
                    chequear_ofertas_compatibles($conexion, $id_u, $id_p);
                }
                // ─────────────────────────────────────────────────────────────

                if ($_SESSION['tipo'] == 1) {
                    header("Location: admin-dashboard.php");
                } elseif ($_SESSION['tipo'] == 2) {
                    header("Location: index.php");
                } elseif (in_array($_SESSION['tipo'], [3, 4])) {
                    header("Location: index-empresa.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            }
        } else {
            $error_msg = 'El email o contraseña son incorrectos o no están registrados.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - YoConstructor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">

    <!-- Modal de error -->
    <?php if ($error_msg): ?>
        <div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
                <div class="border-b px-6 py-4 bg-red-50 rounded-t-xl">
                    <h5 class="text-lg font-semibold text-red-700 flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        Error al ingresar
                    </h5>
                </div>
                <div class="px-6 py-5">
                    <p class="text-gray-700"><?= htmlspecialchars($error_msg) ?></p>
                </div>
                <div class="border-t px-6 py-4 flex justify-end bg-gray-50 rounded-b-xl">
                    <button onclick="document.getElementById('errorModal').remove()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2 rounded-lg transition-colors">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8 md:p-10">
            <div class="text-center mb-8">
                <a href="index.php" class="inline-flex items-center justify-center gap-2 text-gray-800 hover:text-indigo-600 mb-4">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                    </svg>
                    <span class="text-2xl font-bold">YoConstructor</span>
                </a>
                <h2 class="text-2xl font-bold text-gray-900">¡Bienvenido de nuevo!</h2>
                <p class="mt-2 text-sm text-gray-600">
                    ¿No tenés cuenta?
                    <a href="registrarme.php" class="font-medium text-indigo-600 hover:text-indigo-500">Registrate aquí</a>
                </p>
            </div>

            <form class="space-y-5" action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                    <input name="email" type="email" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        placeholder="usuario@mail.com">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Contraseña</label>
                    <div class="relative">
                        <input name="contrasena" id="contrasena" type="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all pr-12"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePass()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="text-right">
                    <a href="recuperar-contraseña.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Olvidé mi contraseña</a>
                </div>

                <button type="submit" name="enviar"
                    class="w-full bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-semibold py-3 rounded-lg transition-all duration-300 transform hover:scale-[1.02] shadow-lg">
                    Ingresar
                </button>

                <a href="index.php"
                    class="block w-full text-center bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 border border-gray-300 rounded-lg shadow-sm transition-all">
                    Continuar sin registrar
                </a>
            </form>

            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Continuar con</span>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-3">
                    <a href="#" class="flex items-center justify-center py-3 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all">
                        <img class="h-5 w-5" src="https://www.svgrepo.com/show/512120/facebook-176.svg" alt="Facebook">
                    </a>
                    <a href="#" class="flex items-center justify-center py-3 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all">
                        <img class="h-5 w-5" src="https://www.svgrepo.com/show/513008/twitter-154.svg" alt="Twitter">
                    </a>
                    <a href="#" class="flex items-center justify-center py-3 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all">
                        <img class="h-6 w-6" src="https://www.svgrepo.com/show/506498/google.svg" alt="Google">
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePass() {
            const input = document.getElementById('contrasena');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>

</html>