<?php
$pageTitle = 'Recuperar contraseña';
include("conexion.php");

$step    = 1; // 1=email, 2=código, 3=nueva contraseña, 4=éxito
$ok_msg  = '';
$err_msg = '';

// ── PASO 1: Solicitar email ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'solicitar_codigo') {
        $email = trim($_POST['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err_msg = 'Ingresá un email válido.';
            $step = 1;
        } else {
            $email_esc = mysqli_real_escape_string($conexion, $email);
            $user = mysqli_fetch_assoc(mysqli_query($conexion,
                "SELECT id_usuario, email FROM users WHERE email='$email_esc' AND estado='activo' LIMIT 1"));
            if (!$user) {
                $err_msg = 'No encontramos una cuenta activa con ese email.';
                $step = 1;
            } else {
                // Generar código de 6 dígitos
                $codigo    = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expira_en = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $id_usuario = $user['id_usuario'];

                // Guardar en sesión (en producción usar tabla BD + envío de email)
                session_start();
                $_SESSION['recuperar_codigo']     = $codigo;
                $_SESSION['recuperar_id_usuario'] = $id_usuario;
                $_SESSION['recuperar_email']       = $email;
                $_SESSION['recuperar_expira']      = $expira_en;
                $_SESSION['recuperar_intentos']    = 0;

                // En producción: enviar $codigo por email
                // mail($email, 'Código de recuperación - YoConstructor', "Tu código es: $codigo");

                $step = 2;
                $ok_msg = 'Te enviamos un código de 6 dígitos al email ingresado.';
            }
        }
    }

    elseif ($_POST['accion'] === 'verificar_codigo') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $codigo_ingresado = trim($_POST['codigo'] ?? '');

        if (!isset($_SESSION['recuperar_codigo'])) {
            $err_msg = 'La sesión expiró. Comenzá de nuevo.';
            $step = 1;
        } elseif ($_SESSION['recuperar_intentos'] >= 5) {
            $err_msg = 'Demasiados intentos fallidos. Comenzá de nuevo.';
            $step = 1;
            session_unset();
        } elseif (strtotime($_SESSION['recuperar_expira']) < time()) {
            $err_msg = 'El código expiró. Solicitá uno nuevo.';
            $step = 1;
            session_unset();
        } elseif ($codigo_ingresado !== $_SESSION['recuperar_codigo']) {
            $_SESSION['recuperar_intentos']++;
            $restantes = 5 - $_SESSION['recuperar_intentos'];
            $err_msg = "Código incorrecto. Te quedan $restantes intento(s).";
            $step = 2;
        } else {
            $_SESSION['recuperar_verificado'] = true;
            $step = 3;
        }
    }

    elseif ($_POST['accion'] === 'nueva_contrasena') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $pass1 = $_POST['password']         ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if (!isset($_SESSION['recuperar_verificado']) || !$_SESSION['recuperar_verificado']) {
            $err_msg = 'Sesión inválida. Comenzá de nuevo.';
            $step = 1;
        } elseif (strlen($pass1) < 6) {
            $err_msg = 'La contraseña debe tener al menos 6 caracteres.';
            $step = 3;
        } elseif ($pass1 !== $pass2) {
            $err_msg = 'Las contraseñas no coinciden.';
            $step = 3;
        } else {
            $id_usuario = intval($_SESSION['recuperar_id_usuario']);
            $hash       = password_hash($pass1, PASSWORD_DEFAULT);
            $hash_esc   = mysqli_real_escape_string($conexion, $hash);
            mysqli_query($conexion,
                "UPDATE users SET contrasena='$hash_esc' WHERE id_usuario=$id_usuario");
            session_unset();
            $step = 4;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - YoConstructor</title>
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
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased flex flex-col min-h-screen">

<?php include("navbar-trabajador.php"); ?>

<main class="flex-1 flex items-center justify-center py-16 px-4 relative overflow-hidden">

    <!-- Decoración fondo -->
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-50 rounded-full opacity-60 blur-3xl"></div>
        <div class="absolute bottom-0 -left-20 w-72 h-72 bg-blue-100 rounded-full opacity-50 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">

        <!-- Logo / título -->
        <div class="text-center mb-8">
            <span class="inline-block text-xs font-bold tracking-widest text-blue-600 uppercase bg-blue-50 border border-blue-100 px-4 py-1.5 rounded-full mb-4">
                Recuperar acceso
            </span>
            <h1 class="text-2xl font-extrabold text-gray-900">
                <?php if ($step === 4): ?>
                    ¡Listo!
                <?php else: ?>
                    Recuperá tu contraseña
                <?php endif; ?>
            </h1>
            <p class="text-gray-500 text-sm mt-2">
                <?php if ($step === 1): ?>
                    Ingresá el email asociado a tu cuenta.
                <?php elseif ($step === 2): ?>
                    Revisá tu bandeja de entrada e ingresá el código.
                <?php elseif ($step === 3): ?>
                    Elegí una nueva contraseña segura.
                <?php else: ?>
                    Tu contraseña fue actualizada correctamente.
                <?php endif; ?>
            </p>
        </div>

        <!-- Indicador de pasos -->
        <?php if ($step < 4): ?>
        <div class="flex items-center justify-center gap-2 mb-8">
            <?php for ($i = 1; $i <= 3; $i++): ?>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all
                        <?= $step >= $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-400' ?>">
                        <?php if ($step > $i): ?>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        <?php else: ?>
                            <?= $i ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($i < 3): ?>
                    <div class="w-10 h-0.5 <?= $step > $i ? 'bg-blue-600' : 'bg-gray-200' ?> transition-all"></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- Card principal -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">

            <?php if ($ok_msg): ?>
            <div class="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-xl mb-6">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-green-700 font-medium"><?= htmlspecialchars($ok_msg) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($err_msg): ?>
            <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl mb-6">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-red-700 font-medium"><?= htmlspecialchars($err_msg) ?></p>
            </div>
            <?php endif; ?>

            <!-- ── PASO 1: Email ── -->
            <?php if ($step === 1): ?>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="accion" value="solicitar_codigo">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Email de tu cuenta <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" required autofocus
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="tu@email.com"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Enviar código
                </button>
            </form>
            <?php endif; ?>

            <!-- ── PASO 2: Código ── -->
            <?php if ($step === 2): ?>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="accion" value="verificar_codigo">

                <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl text-sm text-blue-700">
                    Enviamos un código de 6 dígitos a
                    <strong><?= htmlspecialchars($_SESSION['recuperar_email'] ?? '') ?></strong>.
                    Válido por 30 minutos.
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Código de verificación <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="codigo" required autofocus
                        maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                        placeholder="000000"
                        class="w-full px-3 py-3 border border-gray-300 rounded-xl text-sm text-center font-mono tracking-[0.4em] text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Verificar código
                </button>
            </form>

            <form method="POST" class="mt-4">
                <input type="hidden" name="accion" value="solicitar_codigo">
                <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['recuperar_email'] ?? '') ?>">
                <button type="submit" class="w-full text-center text-sm text-blue-600 hover:text-blue-800 font-medium transition">
                    ¿No recibiste el código? Reenviar
                </button>
            </form>
            <?php endif; ?>

            <!-- ── PASO 3: Nueva contraseña ── -->
            <?php if ($step === 3): ?>
            <form method="POST" class="space-y-5" id="form-pass">
                <input type="hidden" name="accion" value="nueva_contrasena">

                <div class="relative">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Nueva contraseña <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" id="pass1" required autofocus
                        placeholder="Mínimo 6 caracteres"
                        class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <button type="button" onclick="togglePass('pass1','eye1-show','eye1-hide')"
                        class="absolute right-3 top-[34px] text-gray-400 hover:text-gray-600">
                        <svg id="eye1-show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg id="eye1-hide" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>

                <div class="relative">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Confirmar contraseña <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password_confirm" id="pass2" required
                        placeholder="Repetí la contraseña"
                        class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <button type="button" onclick="togglePass('pass2','eye2-show','eye2-hide')"
                        class="absolute right-3 top-[34px] text-gray-400 hover:text-gray-600">
                        <svg id="eye2-show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg id="eye2-hide" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>

                <!-- Indicador de fortaleza -->
                <div>
                    <div class="flex gap-1 mb-1">
                        <div id="str-1" class="h-1 flex-1 rounded-full bg-gray-200 transition-all"></div>
                        <div id="str-2" class="h-1 flex-1 rounded-full bg-gray-200 transition-all"></div>
                        <div id="str-3" class="h-1 flex-1 rounded-full bg-gray-200 transition-all"></div>
                        <div id="str-4" class="h-1 flex-1 rounded-full bg-gray-200 transition-all"></div>
                    </div>
                    <p id="str-label" class="text-xs text-gray-400"></p>
                </div>

                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Guardar nueva contraseña
                </button>
            </form>
            <?php endif; ?>

            <!-- ── PASO 4: Éxito ── -->
            <?php if ($step === 4): ?>
            <div class="text-center py-4">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-extrabold text-gray-900 mb-2">Contraseña actualizada</h2>
                <p class="text-sm text-gray-500 mb-8">Podés iniciar sesión con tu nueva contraseña.</p>
                <a href="login.php"
                    class="inline-flex items-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Ir a iniciar sesión
                </a>
            </div>
            <?php endif; ?>

        </div>

        <!-- Link volver -->
        <?php if ($step < 4): ?>
        <p class="text-center text-sm text-gray-500 mt-6">
            ¿Recordaste tu contraseña?
            <a href="login.php" class="text-blue-600 hover:text-blue-800 font-semibold transition">Iniciá sesión</a>
        </p>
        <?php endif; ?>

    </div>
</main>

<footer class="bg-white border-t border-gray-200 text-gray-600 py-8 px-3">
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
    function togglePass(inputId, showId, hideId) {
        const inp  = document.getElementById(inputId);
        const show = document.getElementById(showId);
        const hide = document.getElementById(hideId);
        if (inp.type === 'password') {
            inp.type = 'text';
            show.classList.add('hidden');
            hide.classList.remove('hidden');
        } else {
            inp.type = 'password';
            show.classList.remove('hidden');
            hide.classList.add('hidden');
        }
    }

    // Indicador de fortaleza
    const pass1 = document.getElementById('pass1');
    if (pass1) {
        pass1.addEventListener('input', function () {
            const v = this.value;
            let score = 0;
            if (v.length >= 6)  score++;
            if (v.length >= 10) score++;
            if (/[A-Z]/.test(v) && /[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            const colors = ['bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-green-500'];
            const labels = ['Muy débil', 'Débil', 'Aceptable', 'Fuerte'];

            for (let i = 1; i <= 4; i++) {
                const bar = document.getElementById('str-' + i);
                bar.className = 'h-1 flex-1 rounded-full transition-all ' +
                    (i <= score ? colors[score - 1] : 'bg-gray-200');
            }
            const lbl = document.getElementById('str-label');
            lbl.textContent = v.length > 0 ? labels[score - 1] || '' : '';
            lbl.className   = 'text-xs transition-all ' +
                (['text-red-500','text-orange-500','text-yellow-600','text-green-600'][score - 1] || 'text-gray-400');
        });
    }
</script>

</body>
</html>