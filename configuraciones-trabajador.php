<?php
include_once("conexion.php");

if (!isset($_SESSION['idusuario']) || $_SESSION['tipo'] != 2) {
    header("Location: login.php");
    exit;
}

$id_usuario = intval($_SESSION['idusuario']);

$sql_user = "SELECT u.id_usuario, u.estado, u.visible_busqueda,
                    p.id_persona, p.nombre, p.apellido, p.imagen_perfil
             FROM users u
             INNER JOIN persona p ON u.id_persona = p.id_persona
             WHERE u.id_usuario = $id_usuario";
$res_user = mysqli_query($conexion, $sql_user);
if (!$res_user || mysqli_num_rows($res_user) === 0) {
    header("Location: cerrar-session.php");
    exit;
}
$usuario = mysqli_fetch_assoc($res_user);

$es_visible   = intval($usuario['visible_busqueda'] ?? 0);
$mensaje      = '';
$tipo_mensaje = '';

if (isset($_POST['toggle_visibilidad'])) {
    $nuevo_valor = intval($_POST['visible_busqueda']);
    $nuevo_valor = ($nuevo_valor === 1) ? 1 : 0;
    if (mysqli_query($conexion, "UPDATE users SET visible_busqueda = $nuevo_valor WHERE id_usuario = $id_usuario")) {
        $es_visible   = $nuevo_valor;
        $mensaje      = $nuevo_valor ? '✓ Ahora aparecés en la búsqueda de empresas.' : '✓ Ya no aparecés en la búsqueda de empresas.';
        $tipo_mensaje = 'success';
    } else {
        $mensaje      = 'Error al actualizar: ' . mysqli_error($conexion);
        $tipo_mensaje = 'error';
    }
}

if (isset($_POST['eliminar_cuenta'])) {
    if (trim($_POST['confirmar_texto'] ?? '') === 'ELIMINAR') {
        if (mysqli_query($conexion, "UPDATE users SET estado = 'inactivo' WHERE id_usuario = $id_usuario")) {
            session_destroy();
            header("Location: index.php?cuenta=eliminada");
            exit;
        } else {
            $mensaje      = 'Error al eliminar la cuenta: ' . mysqli_error($conexion);
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje      = 'Escribí ELIMINAR exactamente para confirmar.';
        $tipo_mensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones - YoConstructor</title>
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
                        <a href="perfil-trabajador.php"
                           class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate">Mi perfil</span>
                        </a>
                        <a href="mis-postulaciones.php"
                           class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            <span class="truncate">Mis postulaciones</span>
                        </a>
                        <a href="notificaciones.php"
                           class="border-transparent text-gray-700 hover:bg-gray-100 hover:text-blue-600 group border-l-4 px-3 py-2 flex items-center text-sm font-medium rounded-r-xl transition-all">
                            <svg class="text-gray-400 group-hover:text-blue-500 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                             <span class="truncate">Notificaciones</span>
                        
                        </a>
                        <!-- Activo -->
                        <a href="configuraciones-trabajador.php"
                           class="bg-blue-50 border-blue-600 text-blue-700 group border-l-4 px-3 py-2 flex items-center text-sm font-semibold rounded-r-xl" aria-current="page">
                            <svg class="text-blue-600 flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate">Configuración</span>
                        </a>
                    </nav>
                </aside>

                <!-- CONTENIDO -->
                <div class="lg:col-span-9">
                    <div class="py-6 px-4 sm:p-6 lg:pb-8">

                        <!-- Header -->
                        <div class="mb-8">
                            <h2 class="text-2xl font-extrabold text-gray-900">Configuraciones de cuenta</h2>
                            <p class="mt-1 text-sm text-gray-500">Administrá tu visibilidad y opciones de cuenta.</p>
                        </div>

                        <?php if ($mensaje): ?>
                        <div class="flex items-center gap-3 p-4 rounded-xl border text-sm font-medium mb-6
                            <?= $tipo_mensaje === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700' ?>">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?= $tipo_mensaje === 'success'
                                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' ?>
                            </svg>
                            <?= htmlspecialchars($mensaje) ?>
                        </div>
                        <?php endif; ?>

                        <div class="space-y-6">

                            <!-- Card visibilidad -->
                            <div class="bg-gray-50 border border-gray-200 rounded-2xl overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-200 flex items-center gap-3">
                                    <div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-800">Visibilidad en búsquedas</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Controlá si las empresas pueden encontrarte</p>
                                    </div>
                                </div>
                                <div class="px-5 py-5">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-gray-700 mb-1">Aparecer en búsqueda de empresas</p>
                                            <p class="text-xs text-gray-500 leading-relaxed">
                                                Cuando está activado, las empresas pueden encontrar tu perfil. Desactivalo si no querés ser contactado por el momento.
                                            </p>
                                            <div class="mt-3">
                                                <?php if ($es_visible): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full border border-green-200">
                                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                                        Visible para empresas
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-500 text-xs font-semibold rounded-full border border-gray-200">
                                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                                        No visible
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="toggle_visibilidad" value="1">
                                            <input type="hidden" name="visible_busqueda" value="<?= $es_visible ? 0 : 1 ?>">
                                            <button type="submit"
                                                class="relative inline-flex h-7 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex-shrink-0 <?= $es_visible ? 'bg-blue-600' : 'bg-gray-300' ?>"
                                                style="width:52px;">
                                                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition-transform duration-300 <?= $es_visible ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Card eliminar cuenta -->
                            <div class="bg-gray-50 border border-red-100 rounded-2xl overflow-hidden">
                                <div class="px-5 py-4 border-b border-red-100 flex items-center gap-3">
                                    <div class="w-9 h-9 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-red-700">Eliminar cuenta</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Esta acción desactiva tu acceso permanentemente</p>
                                    </div>
                                </div>
                                <div class="px-5 py-5">
                                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
                                        <p class="text-sm text-red-700 font-medium mb-2">¿Qué pasa cuando eliminás tu cuenta?</p>
                                        <ul class="text-xs text-red-600 space-y-1.5">
                                            <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>Tu cuenta quedará desactivada y no podrás iniciar sesión</li>
                                            <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>Tu perfil dejará de aparecer en búsquedas de empresas</li>
                                            <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>Tus postulaciones activas quedarán sin efecto</li>
                                            <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg><span class="text-amber-700 font-medium">Podés reactivarla contactando al soporte</span></li>
                                        </ul>
                                    </div>
                                    <button onclick="abrirModalEliminar()"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Eliminar mi cuenta
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- Modal eliminar -->
<div id="modalEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900">¿Estás seguro?</h3>
                    <p class="text-sm text-gray-500">Esta acción desactivará tu cuenta</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Para confirmar, escribí <span class="font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-lg">ELIMINAR</span> en el campo de abajo:
            </p>
            <form method="POST">
                <input type="hidden" name="eliminar_cuenta" value="1">
                <input type="text" name="confirmar_texto" id="confirmarTexto"
                    placeholder="Escribí ELIMINAR" autocomplete="off"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 mb-4 transition bg-gray-50">
                <div class="flex gap-3">
                    <button type="button" onclick="cerrarModalEliminar()"
                        class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition text-sm font-medium">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarEliminar" disabled
                        class="flex-1 px-4 py-2.5 bg-red-300 text-white rounded-xl text-sm font-semibold cursor-not-allowed transition">
                        Confirmar eliminación
                    </button>
                </div>
            </form>
        </div>
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
function abrirModalEliminar() {
    document.getElementById('modalEliminar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('confirmarTexto').value = '';
    const btn = document.getElementById('btnConfirmarEliminar');
    btn.disabled = true;
    btn.className = 'flex-1 px-4 py-2.5 bg-red-300 text-white rounded-xl text-sm font-semibold cursor-not-allowed transition';
    setTimeout(() => document.getElementById('confirmarTexto').focus(), 100);
}
function cerrarModalEliminar() {
    document.getElementById('modalEliminar').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
document.getElementById('confirmarTexto').addEventListener('input', function() {
    const btn = document.getElementById('btnConfirmarEliminar');
    if (this.value === 'ELIMINAR') {
        btn.disabled = false;
        btn.className = 'flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold transition cursor-pointer';
    } else {
        btn.disabled = true;
        btn.className = 'flex-1 px-4 py-2.5 bg-red-300 text-white rounded-xl text-sm font-semibold cursor-not-allowed transition';
    }
});
document.getElementById('modalEliminar').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEliminar();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalEliminar();
});
</script>

</body>
</html>