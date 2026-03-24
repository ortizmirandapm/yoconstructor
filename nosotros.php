<?php
include_once("conexion.php");
// Estadísticas reales de la BD
$total_trabajadores = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM users WHERE tipo=2 AND estado='activo'"))['c'] ?? 0;
$total_empresas     = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM empresa WHERE estado='activo'"))['c'] ?? 0;
$total_ofertas      = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM ofertas_laborales WHERE estado='Activa'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nosotros - YoConstructor</title>
    <!-- Misma fuente que el index -->
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
<body class="bg-white text-gray-800 font-sans antialiased">

<?php include_once("navbar-trabajador.php"); ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="w-full px-6 py-3 bg-gray-50 border-b border-gray-200">
    <ol class="flex items-center space-x-2 text-sm text-gray-500 max-w-7xl mx-auto">
        <li><a href="index.php" class="hover:text-blue-600 transition">Inicio</a></li>
        <li class="text-gray-300">/</li>
        <li class="text-gray-700 font-semibold">Nosotros</li>
    </ol>
</nav>

<main>

    <!-- Hero nosotros -->
    <div id="about" class="relative bg-white overflow-hidden mt-10">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <svg class="hidden lg:block absolute right-0 inset-y-0 h-full w-48 text-white transform translate-x-1/2"
                    fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <polygon points="50,0 100,0 50,100 0,100"></polygon>
                </svg>
                <div class="mt-10 mx-auto max-w-7xl px-6 sm:mt-12 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <h2 class="my-6 text-3xl md:text-4xl tracking-tight font-extrabold text-gray-900">
                            Nosotros
                        </h2>
                        <p class="text-gray-500 text-base leading-relaxed max-w-xl">
                            <span class="text-blue-600">YoConstructor</span> es una plataforma fundada en 2025 dedicada a conectar empresas del sector
                            construcción con trabajadores calificados de todo el país. Nuestro objetivo es simplificar
                            el proceso de búsqueda y selección de talento, garantizando calidad tanto para quienes
                            publican ofertas como para quienes buscan oportunidades laborales.
                        </p>
                        <p class="text-gray-500 text-base leading-relaxed max-w-xl mt-4">
                            Contamos con un equipo de profesionales comprometidos con la satisfacción de cada usuario,
                            desde el registro hasta la contratación exitosa.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <img class="h-56 w-full object-cover object-top sm:h-72 md:h-96 lg:w-full lg:h-full"
                src="img/imagen-constructora.jpg" alt="YoConstructor">
        </div>
    </div>

    <!-- Estadísticas reales -->
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-gray-900">
                    La plataforma <span class="text-blue-600">en números</span>
                </h2>
                <p class="mt-3 text-gray-500 text-lg">Datos actualizados de nuestra comunidad</p>
            </div>

            <div class="mx-auto max-w-4xl">
                <dl class="rounded-2xl bg-white shadow-sm border border-gray-100 sm:grid sm:grid-cols-3 overflow-hidden">

                    <!-- Trabajadores -->
                    <div class="flex flex-col items-center border-b border-gray-100 p-8 text-center sm:border-b-0 sm:border-r group hover:bg-blue-50 transition-colors duration-300">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-3 group-hover:bg-blue-200 transition-colors">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <dd class="text-5xl font-extrabold tracking-tight text-blue-600">
                            <?= number_format($total_trabajadores) ?>
                        </dd>
                        <dt class="mt-2 text-base font-medium text-gray-500">Trabajadores activos</dt>
                    </div>

                    <!-- Empresas -->
                    <div class="flex flex-col items-center border-b border-gray-100 p-8 text-center sm:border-b-0 sm:border-r group hover:bg-blue-50 transition-colors duration-300">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-3 group-hover:bg-blue-200 transition-colors">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <dd class="text-5xl font-extrabold tracking-tight text-blue-600">
                            <?= number_format($total_empresas) ?>
                        </dd>
                        <dt class="mt-2 text-base font-medium text-gray-500">Empresas registradas</dt>
                    </div>

                    <!-- Ofertas activas -->
                    <div class="flex flex-col items-center p-8 text-center group hover:bg-blue-50 transition-colors duration-300">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-3 group-hover:bg-blue-200 transition-colors">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <dd class="text-5xl font-extrabold tracking-tight text-blue-600">
                            <?= number_format($total_ofertas) ?>
                        </dd>
                        <dt class="mt-2 text-base font-medium text-gray-500">Ofertas laborales activas</dt>
                    </div>

                </dl>
            </div>
        </div>
    </section>

    <!-- Sección Fundador -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block text-xs font-bold tracking-widest text-blue-600 uppercase bg-blue-50 border border-blue-100 px-4 py-1.5 rounded-full mb-4">
                    El equipo
                </span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight">
                    Quién está detrás de <span class="text-blue-600">YoConstructor</span>
                </h2>
            </div>

            <div class="max-w-3xl mx-auto">
                <div class="group bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                    <!-- Barra top azul animada -->
                    <div class="h-1 bg-blue-600 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>

                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-8 p-8">

                        <!-- Foto del fundador — reemplazar src con la imagen real -->
                        <div class="flex-shrink-0">
                            <div class="w-32 h-32 rounded-2xl bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                                <!-- REEMPLAZAR src con la ruta de tu imagen, ej: src="img/fundador.jpg" -->
                                <img src="img/Yo.jpeg"
                                     alt="Fundador de YoConstructor"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <!-- Placeholder SVG (se oculta si la imagen carga bien) -->
                                <div class="w-full h-full bg-blue-600 items-center justify-center hidden">
                                    <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 text-center sm:text-left">
                            <h3 class="text-xl font-extrabold text-gray-900">Pablo Martin Ortiz Miranda</h3>
                            <p class="text-blue-600 font-semibold text-sm mt-1">Fundador &amp; CEO</p>

                            <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                                Apasionado por la tecnología y el sector de la construcción, creó YoConstructor
                                con la visión de modernizar la forma en que las empresas encuentran talento
                                y los trabajadores acceden a oportunidades laborales en Argentina.
                            </p>

                            <!-- Datos extra -->
                            <div class="flex flex-wrap justify-center sm:justify-start gap-3 mt-5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Argentina
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full border border-blue-100">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Desde 2025
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- Footer — igual al del index -->
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

</body>
</html>