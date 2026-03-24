<?php
// sidebar-admin.php — Panel Admin (tipo=1)
// Misma estructura visual que sidebar-empresa.php

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['idusuario']) || $_SESSION['tipo'] != 1) {
    header("Location: login.php"); exit;
}

$id_admin     = $_SESSION['idusuario'];
$admin_nombre = ucwords(strtolower($_SESSION['nombreusuario'] ?? 'Admin'));
$admin_email  = $_SESSION['emailusuario'] ?? '';

// ── Estadísticas para badges ──────────────────────────────────────────────────
$stats_admin = [];
$stats_admin['empresas']     = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM empresa WHERE estado='activo'"))['c'] ?? 0;
$stats_admin['trabajadores'] = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM users WHERE tipo=2 AND estado='activo'"))['c'] ?? 0;
$stats_admin['ofertas']      = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM ofertas_laborales WHERE estado='Activa'"))['c'] ?? 0;
// Crear tabla reportes si no existe aún
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS reportes (
    id_reporte INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('empresa','trabajador','oferta') NOT NULL,
    id_referencia INT NOT NULL,
    motivo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    id_usuario_reporta INT,
    estado ENUM('pendiente','revisado','resuelto','descartado') DEFAULT 'pendiente',
    accion_tomada TEXT,
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_revision DATETIME
)");
$stats_admin['reportes']     = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM reportes WHERE estado='pendiente'"))['c'] ?? 0;

// Submenú activo
$admin_submenus = [
    'usuarios'  => ['admin-empresas', 'admin-trabajadores', 'admin-reclutadores', 'admin-administradores'],
    'contenido' => ['admin-ofertas', 'admin-reportes', 'admin-rubros', 'admin-especialidades'],
];

function isActiveAdmin($id, $current) {
    return $id === $current
        ? 'bg-indigo-50 text-indigo-600 font-semibold'
        : 'text-gray-900 hover:bg-indigo-50 hover:text-indigo-600';
}
function iconActiveAdmin($id, $current) {
    return $id === $current ? 'text-indigo-600' : 'text-gray-500';
}
function isSubmenuActiveAdmin($id, $current) {
    return $id === $current ? 'bg-indigo-50 text-indigo-600 font-semibold' : '';
}
function submenuExpandedAdmin($ids, $current) {
    return in_array($current, $ids) ? 'block' : 'hidden';
}
function arrowRotatedAdmin($ids, $current) {
    return in_array($current, $ids) ? 'rotate-180' : '';
}

$pageTitle = $pageTitle ?? 'Admin';
$page      = $page      ?? 'admin-dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - YoConstructor Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full md:translate-x-0 bg-white shadow-lg">
    <div class="h-full px-3 py-4 overflow-y-auto">

        <!-- Logo -->
        <div class="mb-6 px-3">
            <h2 class="text-2xl font-bold text-indigo-600">YoConstructor</h2>
            <p class="text-xs text-gray-500 mt-1">¡Bienvenido, Admin!</p>
        </div>

        <ul class="space-y-1 font-medium">

            <!-- Dashboard -->
            <li>
                <a href="admin-dashboard.php" class="flex items-center p-3 rounded-lg transition-all group <?= isActiveAdmin('admin-dashboard', $page) ?>">
                    <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?= iconActiveAdmin('admin-dashboard', $page) ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                        <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                    </svg>
                    <span class="ml-3">Dashboard</span>
                </a>
            </li>

            <!-- Usuarios (submenu: Empresas + Trabajadores) -->
            <li>
                <button type="button" onclick="toggleSubmenu('usuarios')"
                    class="flex items-center w-full p-3 text-gray-900 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 group transition-all">
                    <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?= in_array($page, $admin_submenus['usuarios']) ? 'text-indigo-600' : 'text-gray-500' ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Usuarios</span>
                    <svg id="arrow-usuarios" class="w-4 h-4 transition-transform <?= arrowRotatedAdmin($admin_submenus['usuarios'], $page) ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul id="submenu-usuarios" class="<?= submenuExpandedAdmin($admin_submenus['usuarios'], $page) ?> py-1 space-y-1">
                    <li>
                        <a href="admin-empresas.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?= isSubmenuActiveAdmin('admin-empresas', $page) ?>">
                            <span>Empresas</span>
                         
                        </a>
                    </li>
                     <li>
                        <a href="admin-reclutadores.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?= isSubmenuActiveAdmin('admin-reclutadores', $page) ?>">
                            <span>Reclutadores</span>
                           
                        </a>
                    </li>
                    <li>
                        <a href="admin-trabajadores.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?= isSubmenuActiveAdmin('admin-trabajadores', $page) ?>">
                            <span>Trabajadores</span>
                           
                        </a>
                    </li>
                      <li>
                        <a href="admin-administradores.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?= isSubmenuActiveAdmin('admin-administradores', $page) ?>">
                            <span>Administradores</span>
                           
                        </a>
                    </li>
                   
                </ul>
            </li>

            <!-- Contenido  -->
            <li>
                <button type="button" onclick="toggleSubmenu('contenido')"
                    class="flex items-center w-full p-3 text-gray-900 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 group transition-all">
                    <svg class="w-5 h-5 transition duration-75 group-hover:text-indigo-600 <?= in_array($page, $admin_submenus['contenido']) ? 'text-indigo-600' : 'text-gray-500' ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="flex-1 ml-3 text-left">Contenido</span>
                    <svg id="arrow-contenido" class="w-4 h-4 transition-transform <?= arrowRotatedAdmin($admin_submenus['contenido'], $page) ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <ul id="submenu-contenido" class="<?= submenuExpandedAdmin($admin_submenus['contenido'], $page) ?> py-1 space-y-1">
                    <li>
                        <a href="admin-ofertas.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?= isSubmenuActiveAdmin('admin-ofertas', $page) ?>">
                            <span>Ofertas</span>
                          
                        </a>
                    </li>
                    <li>
                        <a href="admin-rubros.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?= isSubmenuActiveAdmin('admin-rubros', $page) ?>">
                            <span>Rubros</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin-especialidades.php" class="flex items-center justify-between w-full p-2 pl-11 text-gray-600 rounded-lg hover:bg-gray-100 hover:text-indigo-600 transition duration-75 <?= isSubmenuActiveAdmin('admin-especialidades', $page) ?>">
                            <span>Especialidades</span>
                        </a>
                    </li>
                </ul>
            </li>

        </ul>

        <div class="my-4 border-t border-gray-200"></div>

        <ul class="space-y-1 font-medium">
            <li>
                <a href="#" onclick="abrirModalSesion(); return false;" class="flex items-center p-3 text-red-600 rounded-lg hover:bg-red-50 group transition-all">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-3">Cerrar Sesión</span>
                </a>
            </li>
        </ul>

     
    </div>





</aside>

<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('-translate-x-full'); }
function toggleSubmenu(id) {
    const submenu = document.getElementById('submenu-' + id);
    const arrow   = document.getElementById('arrow-' + id);
    submenu.classList.toggle('hidden');
    submenu.classList.toggle('block');
    arrow.classList.toggle('rotate-180');
}
</script>

<div class="md:ml-64">
    <nav class="bg-white border-b border-gray-200 px-4 py-3 md:hidden">
        <div class="flex items-center justify-between">
            <button onclick="toggleSidebar()" class="text-gray-500 hover:bg-gray-100 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
            </button>
            <h1 class="text-xl font-bold text-indigo-600">YoConstructor</h1>
            <div class="w-10"></div>
        </div>
    </nav>

    <header class="bg-white shadow-sm">
        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Panel de administrador</h1>
              
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                    <?= strtoupper(substr($admin_nombre, 0, 1)) ?>
                </div>
                <div class="hidden md:block">
                    <p class="text-sm font-semibold"><?= htmlspecialchars($admin_nombre) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($admin_email) ?></p>
                </div>
            </div>
        </div>
    </header>

    <main>