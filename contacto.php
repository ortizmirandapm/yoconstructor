<?php
$page      = 'contacto';
$pageTitle = 'Contacto';

include("conexion.php");

$ok_msg  = '';
$err_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre  = trim($_POST['nombre']  ?? '');
    $email   = trim($_POST['email']   ?? '');
    $asunto  = trim($_POST['asunto']  ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (!$nombre || !$email || !$asunto || !$mensaje) {
        $err_msg = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err_msg = 'El email no tiene un formato válido.';
    } else {
        // Aquí podría enviarse un email o guardarse en BD
        // Por ahora solo mostramos éxito
        $ok_msg = '✓ Tu mensaje fue enviado correctamente. Te responderemos a la brevedad.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - YoConstructor</title>
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

<?php include("navbar-trabajador.php"); ?>

<main class="flex-1">

    <!-- Hero -->
    <div class="bg-gray-50 border-b border-gray-200">
        <div class="container mx-auto px-4 py-14 text-center">
            <span class="inline-block text-xs font-bold tracking-widest text-blue-600 uppercase bg-blue-50 border border-blue-100 px-4 py-1.5 rounded-full mb-4">
                Contacto
            </span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 leading-tight mb-4">
                ¿En qué podemos <span class="text-blue-600">ayudarte?</span>
            </h1>
            <p class="text-gray-500 text-lg max-w-xl mx-auto">
                Completá el formulario y nuestro equipo te responderá a la brevedad.
            </p>
        </div>
    </div>

    <!-- Contenido principal -->
    <section class="py-16 relative overflow-hidden">

        <!-- Decoración de fondo igual al index -->
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-50 rounded-full opacity-60 blur-3xl"></div>
            <div class="absolute bottom-0 -left-20 w-72 h-72 bg-blue-100 rounded-full opacity-50 blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 relative">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 max-w-5xl mx-auto">

                <!-- Info lateral -->
                <div class="lg:col-span-2 flex flex-col gap-6">

                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Email</h3>
                        <p class="text-sm text-gray-500">contacto@yoconstructor.com.ar</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Teléfono</h3>
                        <p class="text-sm text-gray-500">+54 383 400-0000</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Ubicación</h3>
                        <p class="text-sm text-gray-500">Catamarca, Argentina</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Horario de atención</h3>
                        <p class="text-sm text-gray-500">Lunes a viernes · 9:00 a 18:00 hs</p>
                    </div>

                </div>

                <!-- Formulario -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">

                        <h2 class="text-xl font-extrabold text-gray-900 mb-6">Envianos un mensaje</h2>

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

                        <form method="POST" class="space-y-5">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre completo <span class="text-red-500">*</span></label>
                                    <input type="text" name="nombre"
                                        value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                                        placeholder="Juan Pérez"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="email"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                        placeholder="tu@email.com"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Asunto <span class="text-red-500">*</span></label>
                                <select name="asunto" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-white">
                                    <option value="">Seleccioná un asunto</option>
                                    <option value="consulta_general"     <?= ($_POST['asunto'] ?? '') === 'consulta_general'     ? 'selected' : '' ?>>Consulta general</option>
                                    <option value="problema_tecnico"     <?= ($_POST['asunto'] ?? '') === 'problema_tecnico'     ? 'selected' : '' ?>>Problema técnico</option>
                                    <option value="cuenta_empresa"       <?= ($_POST['asunto'] ?? '') === 'cuenta_empresa'       ? 'selected' : '' ?>>Mi cuenta de empresa</option>
                                    <option value="cuenta_trabajador"    <?= ($_POST['asunto'] ?? '') === 'cuenta_trabajador'    ? 'selected' : '' ?>>Mi cuenta de trabajador</option>
                                    <option value="sugerencia"           <?= ($_POST['asunto'] ?? '') === 'sugerencia'           ? 'selected' : '' ?>>Sugerencia</option>
                                    <option value="otro"                 <?= ($_POST['asunto'] ?? '') === 'otro'                 ? 'selected' : '' ?>>Otro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Mensaje <span class="text-red-500">*</span></label>
                                <textarea name="mensaje" rows="5"
                                    placeholder="Contanos tu consulta con el mayor detalle posible..."
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none"><?= htmlspecialchars($_POST['mensaje'] ?? '') ?></textarea>
                            </div>

                            <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Enviar mensaje
                            </button>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- FAQ rápido -->
    <section class="py-16 bg-gray-50 border-t border-gray-200">
        <div class="container mx-auto px-4 max-w-3xl">
            <div class="text-center mb-10">
                <h2 class="text-2xl font-extrabold text-gray-900">Preguntas frecuentes</h2>
                <p class="text-gray-500 mt-2">Algunas respuestas antes de escribirnos.</p>
            </div>
            <div class="space-y-4">
                <?php
                $faqs = [
                    ['¿Es gratuito registrarse?',
                     'Sí, tanto trabajadores como empresas pueden crear su cuenta sin costo.'],
                    ['¿Cómo publico una oferta laboral?',
                     'Registrá tu empresa, completá tu perfil y desde el panel podés crear ofertas en pocos minutos.'],
                    ['¿Puedo postularme desde el celular?',
                     'Sí, la plataforma es completamente responsiva y funciona en cualquier dispositivo.'],
                    ['¿Cuánto tarda en aprobarse mi cuenta?',
                     'La activación es inmediata. Podés empezar a usar la plataforma apenas terminás el registro.'],
                ];
                foreach ($faqs as $i => $faq): ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                    <button type="button" onclick="toggleFaq(<?= $i ?>)"
                        class="w-full flex items-center justify-between px-6 py-4 text-left">
                        <span class="font-semibold text-gray-900 text-sm"><?= $faq[0] ?></span>
                        <svg id="faq-icon-<?= $i ?>" class="w-4 h-4 text-blue-600 flex-shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="faq-body-<?= $i ?>" class="hidden px-6 pb-5">
                        <p class="text-sm text-gray-500 leading-relaxed"><?= $faq[1] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>

<footer class="bg-white border-t border-gray-200 text-gray-600 py-8 px-3">
    <div class="container mx-auto flex flex-wrap items-center justify-between">
        <div class="w-full md:w-1/2 text-center md:text-left mb-4 md:mb-0">
            <p class="text-sm font-medium">Copyright 2026 &copy; YoConstructor</p>
        </div>
        <div class="w-full md:w-1/2">
            <ul class="flex justify-center md:justify-end gap-6 text-sm font-semibold">
                <li><a href="contacto.php" class="text-blue-600">Contacto</a></li>
                <li><a href="#" class="hover:text-blue-600">Privacidad</a></li>
                <li><a href="#" class="hover:text-blue-600">Términos</a></li>
            </ul>
        </div>
    </div>
</footer>

<script>
    function toggleFaq(i) {
        const body = document.getElementById('faq-body-' + i);
        const icon = document.getElementById('faq-icon-' + i);
        const open = !body.classList.contains('hidden');
        // Cerrar todos
        document.querySelectorAll('[id^="faq-body-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('[id^="faq-icon-"]').forEach(el => el.classList.remove('rotate-180'));
        // Abrir el clickeado si estaba cerrado
        if (!open) {
            body.classList.remove('hidden');
            icon.classList.add('rotate-180');
        }
    }
</script>

</body>
</html>