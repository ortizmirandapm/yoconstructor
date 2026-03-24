<?php
include("conexion.php");

// ── Estadísticas para la sección de cobertura ────────────────────────────────
$stat_rubros   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM rubros"))['total'] ?? 0;
$stat_esps     = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM especialidades"))['total'] ?? 0;
$stat_ofertas  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM ofertas_laborales WHERE estado = 'Activa'"))['total'] ?? 0;
$stat_empresas = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM empresa WHERE estado = 'activo'"))['total'] ?? 0;

// ── Empresas destacadas para la sección ──────────────────────────────────────
$empresas_destacadas = mysqli_query(
    $conexion,
    "SELECT e.id_empresa, e.nombre_empresa, e.logo, e.descripcion_empresa AS descripcion,
            r.nombre AS rubro_nombre,
            COUNT(o.id_oferta) AS ofertas_activas
     FROM empresa e
     LEFT JOIN rubros r ON e.id_rubro = r.id_rubro
     LEFT JOIN ofertas_laborales o ON o.id_empresa = e.id_empresa AND o.estado = 'Activa'
     WHERE e.estado = 'activo'
     GROUP BY e.id_empresa
     ORDER BY ofertas_activas DESC, e.nombre_empresa ASC
     LIMIT 3"
);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YoConstructor - Bolsa de Trabajo</title>
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

    <?php include("navbar-trabajador.php"); ?>

    <main>
        <div class="relative flex flex-col items-center max-w-screen-xl px-4 mx-auto md:flex-row sm:px-6 p-8">
            <div class="flex items-center py-5 md:w-1/2 md:pb-20 md:pt-10 md:pr-10">
                <div class="text-left">
                    <h2 class="text-4xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl md:text-6xl">
                        Conectamos talento con <span class="text-blue-600">oportunidades.</span>
                    </h2>
                    <p class="max-w-md mx-auto mt-3 text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                        Regístrate hoy para encontrar empleo o publicar tu oferta laboral en el sector de la construcción.
                    </p>
                    <!-- 
                <div class="mt-5 sm:flex md:mt-8">
                    <div class="rounded-md shadow">
                        <a href="ofertas-laborales.php"
                            class="flex items-center justify-center w-full px-8 py-3 text-base font-semibold leading-6 text-white transition duration-150 ease-in-out bg-blue-600 border border-transparent rounded-xl hover:bg-blue-700 focus:outline-none md:py-4 md:text-lg md:px-10">
                            Conseguir empleo
                        </a>
                    </div>
                    <div class="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
                        <a href="registrarme.php"
                            class="flex items-center justify-center w-full px-8 py-3 text-base font-semibold leading-6 text-blue-600 transition duration-150 ease-in-out bg-white border border-blue-100 rounded-xl hover:bg-blue-50 focus:outline-none md:py-4 md:text-lg md:px-10">
                            Publicar oferta
                        </a>
                    </div>
                </div>
   -->
                </div>
            </div>
            <div class="flex items-center py-5 md:w-1/2 md:pb-20 md:pt-10 md:pl-10">
                <div class="relative w-full p-3 rounded md:p-8">
                    <div class="rounded-2xl overflow-hidden shadow-2xl">
                        <img src="img/trabajador-masculino-hablando-telefono-fabrica_107420-96556.avif" alt="Trabajador" class="w-full object-cover" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
         COBERTURA SECTORIAL
    ════════════════════════════════════════════════════════════════ -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <div class="max-w-5xl mx-auto">

                    <!-- Encabezado -->
                    <div class="text-center mb-14">
                        <span class="inline-block text-xs font-bold tracking-widest text-blue-600 uppercase bg-blue-50 border border-blue-100 px-4 py-1.5 rounded-full mb-4">
                            Cobertura sectorial
                        </span>
                        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight">
                            Todo el sector construcción,<br class="hidden md:block">
                            <span class="text-blue-600">en un solo lugar</span>
                        </h2>
                        <p class="mt-4 text-gray-500 text-lg max-w-xl mx-auto">
                            Cubrimos todas las especialidades y rubros del sector. Encontrá la oferta que se adapte a tu perfil o publicá la tuya.
                        </p>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-14">
                        <?php
                        $stats_cob = [
                            [
                                'value' => $stat_rubros,
                                'label' => 'Rubros',
                                'path'  => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-3h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12a2.25 2.25 0 0 1 2.25 2.25V21h3.75V3',
                            ],
                            [
                                'value' => $stat_esps,
                                'label' => 'Especializaciones',
                                'path'  => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z',
                            ],
                            [
                                'value' => $stat_ofertas,
                                'label' => 'Ofertas activas',
                                'path'  => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z',
                            ],
                            [
                                'value' => $stat_empresas,
                                'label' => 'Empresas registradas',
                                'path'  => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
                            ],
                        ];
                        foreach ($stats_cob as $s): ?>
                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 text-center hover:border-blue-200 hover:bg-blue-50/40 transition-all duration-300">
                                <div class="flex justify-center mb-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $s['path'] ?>" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-3xl font-extrabold text-gray-900"><?= $s['value'] ?>+</p>
                                <p class="text-sm text-gray-500 mt-1 font-medium"><?= $s['label'] ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- CTAs -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- CTA Trabajador -->
                        <a href="ofertas-laborales.php"
                            class="group relative bg-white border border-gray-200 rounded-2xl p-6 hover:border-blue-300 hover:shadow-lg transition-all duration-300 flex items-center gap-5 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                            <div class="relative flex-shrink-0 w-14 h-14 bg-blue-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                                </svg>
                            </div>
                            <div class="relative min-w-0">
                                <p class="text-xs font-bold text-blue-600 uppercase tracking-wide mb-1">Soy trabajador</p>
                                <h3 class="font-bold text-gray-900 text-base leading-tight">Explorá las ofertas laborales</h3>
                                <p class="text-sm text-gray-500 mt-1">Encontrá trabajo en construcción según tu especialidad y zona.</p>
                            </div>
                            <svg class="relative flex-shrink-0 w-5 h-5 text-gray-300 group-hover:text-blue-500 transition-colors ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        <!-- CTA Empresa -->
                        <a href="registrarme.php"
                            class="group relative bg-white border border-gray-200 rounded-2xl p-6 hover:border-blue-300 hover:shadow-lg transition-all duration-300 flex items-center gap-5 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                            <div class="relative flex-shrink-0 w-14 h-14 bg-gray-900 rounded-xl flex items-center justify-center shadow-md group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                            </div>
                            <div class="relative min-w-0">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Soy empresa</p>
                                <h3 class="font-bold text-gray-900 text-base leading-tight">Registrá tu empresa gratis</h3>
                                <p class="text-sm text-gray-500 mt-1">Publicá ofertas y encontrá el perfil ideal para tu obra.</p>
                            </div>
                            <svg class="relative flex-shrink-0 w-5 h-5 text-gray-300 group-hover:text-blue-500 transition-colors ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════
         3 PASOS
    ════════════════════════════════════════════════════════════════ -->
        <div class="bg-gray-50 p-4">
            <div class="container mx-auto pt-12 pb-20">
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 text-center mb-12">
                    Cree su oferta laboral en <span class="text-blue-600">3 simples pasos.</span>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 hover:shadow-md transition-shadow">
                        <div class="text-blue-600 font-black text-4xl mb-4">01</div>
                        <h3 class="font-bold text-gray-900 text-xl mb-4">Cree una cuenta gratis</h3>
                        <p class="text-gray-500 leading-relaxed">Solo necesita su dirección de email para registar su empresa y comenzar a darle forma a su busqueda.</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 hover:shadow-md transition-shadow">
                        <div class="text-blue-600 font-black text-4xl mb-4">02</div>
                        <h3 class="font-bold text-gray-900 text-xl mb-4">Cree su publicación</h3>
                        <p class="text-gray-500 leading-relaxed">Luego agregue el título, la descripción, requisitos, provincia y localidad de su publicación de empleo, ¡y listo!</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 hover:shadow-md transition-shadow">
                        <div class="text-blue-600 font-black text-4xl mb-4">03</div>
                        <h3 class="font-bold text-gray-900 text-xl mb-4">Publique su empleo</h3>
                        <p class="text-gray-500 leading-relaxed">Luego de publicar el empleo, use nuestras herramientas para encontrar y filtrar a los mejores talentos.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
         EMPRESAS DESTACADAS
    ════════════════════════════════════════════════════════════════ -->
        <section class="py-20 bg-white relative overflow-hidden" id="empresas">
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-50 rounded-full opacity-60 blur-3xl"></div>
                <div class="absolute bottom-0 -left-20 w-72 h-72 bg-blue-100 rounded-full opacity-50 blur-3xl"></div>
            </div>

            <div class="container mx-auto px-4 relative">
                <div class="text-center mb-14">
                    <span class="inline-block text-xs font-bold tracking-widest text-blue-600 uppercase bg-blue-50 border border-blue-100 px-4 py-1.5 rounded-full mb-4">
                        Empresas activas
                    </span>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight">
                        Empresas que buscan talento<br class="hidden md:block">
                        <span class="text-blue-600">en nuestra plataforma</span>
                    </h2>
                    <p class="mt-4 text-gray-500 text-lg max-w-xl mx-auto">
                        Conéctate con las mejores empresas del sector construcción de Argentina.
                    </p>
                </div>

                <?php if ($empresas_destacadas && mysqli_num_rows($empresas_destacadas) > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($emp = mysqli_fetch_assoc($empresas_destacadas)):
                            $color_bg = 'bg-blue-600';
                            $inicial = strtoupper(substr($emp['nombre_empresa'], 0, 1));
                        ?>
                            <a href="perfil-empresa-publica.php?id=<?= $emp['id_empresa'] ?>"
                                class="group relative bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col gap-4 overflow-hidden">

                                <div class="absolute top-0 left-0 right-0 h-1 bg-[#2563eb] scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left rounded-t-2xl"></div>

                                <div class="flex items-center gap-4">
                                    <?php if (!empty($emp['logo'])): ?>
                                        <img src="<?= htmlspecialchars($emp['logo']) ?>"
                                            alt="<?= htmlspecialchars($emp['nombre_empresa']) ?>"
                                            class="w-14 h-14 rounded-xl object-contain border border-gray-100 bg-white p-1 flex-shrink-0 shadow-sm">
                                    <?php else: ?>
                                        <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl font-bold flex-shrink-0 <?= $color_bg ?> text-white">
                                            <?= $inicial ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 text-base leading-tight truncate group-hover:text-blue-600 transition-colors">
                                            <?= htmlspecialchars($emp['nombre_empresa']) ?>
                                        </h3>
                                        <?php if ($emp['rubro_nombre']): ?>
                                            <span class="inline-block mt-1 text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                                <?= htmlspecialchars($emp['rubro_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-500 line-clamp-2 leading-relaxed">
                                    <?= !empty($emp['descripcion']) ? htmlspecialchars($emp['descripcion']) : '<span class="italic text-gray-400">Sin descripción disponible.</span>' ?>
                                </p>

                                <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
                                    <?php if ($emp['ofertas_activas'] > 0): ?>
                                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                            <?= $emp['ofertas_activas'] ?> oferta<?= $emp['ofertas_activas'] > 1 ? 's' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Sin ofertas activas</span>
                                    <?php endif; ?>

                                    <span class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 group-hover:bg-[#2563eb] group-hover:text-white text-gray-400 transition-all duration-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <div class="text-center mt-12">
                        <a href="ofertas-laborales.php"
                            class="inline-flex items-center gap-2 px-8 py-3.5 bg-[#2563eb] hover:bg-blue-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                            Ver todas las ofertas
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16 text-gray-400 border border-dashed border-gray-200 rounded-2xl">
                        <p>No hay empresas registradas aún.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <!-- ═══════════════════════════════════════════════════════════════
         POR QUÉ ELEGIRNOS
    ════════════════════════════════════════════════════════════════ -->
        <section class="py-16 bg-gray-50">
            <div class="container mx-auto px-5">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-extrabold text-gray-900">¿Por qué elegirnos?</h2>
                    <p class="text-gray-500 mt-3 max-w-xl mx-auto">
                        YoConstructor es la plataforma pensada específicamente para el sector de la construcción en Argentina.
                    </p>
                </div>
                <div class="flex flex-wrap text-center justify-center">
                    <?php
                    $razones = [
                        [
                            'Tecnología moderna',
                            'Plataforma rápida e intuitiva, accesible desde cualquier dispositivo.',
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2563eb" class="w-12 h-12">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                        </svg>'
                        ],
                        [
                            'Tarifas accesibles',
                            'Registrarse es gratis. Tanto empresas como trabajadores pueden usarla sin barreras.',
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2563eb" class="w-12 h-12">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 1.5v.75m0 1.5v.75m1.5-1.5h.75m-1.5 1.5h.75m1.5-1.5h.75m1.5 1.5h.75m1.5-1.5h.75m1.5 1.5h.75m1.5-1.5h.75M3.75 1.5h16.5a2.25 2.25 0 0 1 2.25 2.25v16.5a2.25 2.25 0 0 1-2.25 2.25H3.75a2.25 2.25 0 0 1-2.25-2.25V3.75A2.25 2.25 0 0 1 3.75 1.5Zm12.75 12a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>'
                        ],
                        [
                            'Rapidez y eficiencia',
                            'Conectamos empresas con el talento adecuado de forma directa y sin vueltas.',
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2563eb" class="w-12 h-12">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>'
                        ],
                        [
                            'Experiencia sectorial',
                            'Diseñada para la construcción: especialidades adaptadas a la realidad local.',
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2563eb" class="w-12 h-12">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-3h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12a2.25 2.25 0 0 1 2.25 2.25V21h3.75V3" />
                        </svg>'
                        ]
                    ];
                    foreach ($razones as $razon): ?>
                        <div class="p-4 md:w-1/4 sm:w-1/2">
                            <div class="px-4 py-8 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 group">
                                <div class="flex justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <?= $razon[2] ?>
                                </div>
                                <h3 class="font-bold text-xl text-gray-900 mb-2"><?= $razon[0] ?></h3>
                                <p class="text-sm text-gray-500 leading-relaxed"><?= $razon[1] ?></p>
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
                    <li><a href="contacto.php" class="hover:text-blue-600">Contacto</a></li>
                    <li><a href="#" class="hover:text-blue-600">Privacidad</a></li>
                    <li><a href="#" class="hover:text-blue-600">Términos</a></li>
                </ul>
            </div>
        </div>
    </footer>

</body>

</html>