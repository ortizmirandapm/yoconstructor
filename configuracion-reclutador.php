<?php
$page      = 'configuracion-reclutador';
$pageTitle = 'Configuracion';
include_once("conexion.php");

// Solo para tipo 4 (reclutador)
if (!isset($_SESSION['idusuario']) || $_SESSION['tipo'] != 4) {
    header("Location: login.php");
    exit;
}

$id_usuario = intval($_SESSION['idusuario']);
$actualizacion_exitosa = false;
$mensaje_error         = '';
$pass_exito            = false;
$pass_error            = '';

// ── Cargar datos del reclutador ────────────────────────────────────────────
function cargarReclutador($conexion, $id_usuario)
{
    $sql = "SELECT r.*, u.email, u.estado, u.fecha_creacion,
                   e.nombre_empresa, e.logo AS logo_empresa, e.id_empresa
            FROM reclutadores r
            INNER JOIN users   u ON r.id_usuario = u.id_usuario
            INNER JOIN empresa e ON r.id_empresa  = e.id_empresa
            WHERE r.id_usuario = $id_usuario
            LIMIT 1";
    $res = mysqli_query($conexion, $sql);
    return ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
}

$rec = cargarReclutador($conexion, $id_usuario);
if (!$rec) {
    die("<p class='p-8 text-red-500'>No se encontró el perfil de reclutador.</p>");
}

// ── ACCIÓN: Cambiar contraseña ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $actual  = $_POST['password_actual']    ?? '';
    $nueva   = $_POST['password_nueva']     ?? '';
    $confirm = $_POST['password_confirmar'] ?? '';

    $res_pass = mysqli_query($conexion, "SELECT contrasena FROM users WHERE id_usuario = $id_usuario");
    $row_pass = mysqli_fetch_assoc($res_pass);

    // TODO: reemplazar por password_verify() cuando se hasheen las contraseñas
    if ($actual !== $row_pass['contrasena']) {
        $pass_error = 'La contraseña actual no es correcta.';
    } elseif (strlen($nueva) < 6) {
        $pass_error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $confirm) {
        $pass_error = 'Las contraseñas nuevas no coinciden.';
    } else {
        mysqli_query($conexion, "UPDATE users SET contrasena = '$nueva' WHERE id_usuario = $id_usuario");
        $pass_exito = true;
    }
}

// Nombre completo e iniciales para avatar
$nombre_completo = ucwords(strtolower(trim(($rec['nombre'] ?? '') . ' ' . ($rec['apellido'] ?? ''))));
$initials = strtoupper(
    substr($rec['nombre']   ?? 'R', 0, 1) .
    substr($rec['apellido'] ?? '?', 0, 1)
);

include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50">

    <!-- ── Header ── -->
    <div class="bg-white border-b border-gray-200 px-6 py-5">
        <div class="flex items-center justify-between max-w-4xl">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configuracion</h1>
                <p class="text-sm text-gray-500 mt-0.5">Administracion de datos de acceso</p>
            </div>
        </div>
    </div>

    <div class="px-6 py-6 max-w-4xl space-y-5">

        <!-- ── Card cambiar contraseña ── -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-5 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Cambiar contraseña
            </h3>

            <?php if ($pass_error): ?>
                <div class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl mb-4">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($pass_error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Contraseña actual</label>
                    <input type="password" name="password_actual" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                        placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nueva contraseña</label>
                    <input type="password" name="password_nueva" required minlength="6"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                        placeholder="Mínimo 6 caracteres">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirmar nueva</label>
                    <input type="password" name="password_confirmar" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                        placeholder="Repetí la contraseña">
                </div>
                <div class="sm:col-span-3 flex justify-end">
                    <button type="submit" name="cambiar_password"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Actualizar contraseña
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<!-- ── Modal editar perfil ── -->
<div id="modalEditar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar perfil
            </h3>
            <button onclick="cerrarModalEditar()" class="text-white hover:text-indigo-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</div>

</main>
</div>

<!-- Toast container — abajo a la derecha -->
<div id="toast-container" class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 items-end pointer-events-none [&>*]:pointer-events-auto"></div>

<!-- ── Modal cerrar sesión ── -->
<div id="modalCerrarSesion" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sí, cerrar sesión
            </a>
        </div>
    </div>
</div>

<script>
// ── Modales ────────────────────────────────────────────────────────────────
function abrirModalEditar()  { document.getElementById('modalEditar').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function cerrarModalEditar() { document.getElementById('modalEditar').classList.add('hidden');    document.body.style.overflow = 'auto';   }
function abrirModalSesion()  { document.getElementById('modalCerrarSesion').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function cerrarModalSesion() { document.getElementById('modalCerrarSesion').classList.add('hidden');    document.body.style.overflow = 'auto';   }

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarModalEditar(); cerrarModalSesion(); }
});
document.getElementById('modalEditar')?.addEventListener('click',        function(e) { if (e.target === this) cerrarModalEditar(); });
document.getElementById('modalCerrarSesion')?.addEventListener('click',  function(e) { if (e.target === this) cerrarModalSesion(); });

// ── Toast (abajo a la derecha) ─────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const id  = 'toast-' + Date.now();
    const cfg = {
        success: { border: 'border-green-200', bar: 'bg-green-500', icon: `<svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` },
        error:   { border: 'border-red-200',   bar: 'bg-red-400',   icon: `<svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` }
    };
    const c = cfg[type] || cfg.success;
    const t = document.createElement('div');
    t.id        = id;
    t.className = `flex items-center gap-3 bg-white border ${c.border} rounded-2xl shadow-lg px-4 py-3.5 min-w-[280px] max-w-sm translate-x-full opacity-0 transition-all duration-300 ease-out relative overflow-hidden`;
    t.innerHTML = `${c.icon}<p class="text-sm font-medium text-gray-800 flex-1">${msg}</p>
    <button onclick="removeToast('${id}')" class="text-gray-400 hover:text-gray-600 ml-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <div class="absolute bottom-0 left-0 h-0.5 w-full ${c.bar} origin-left" id="bar-${id}"></div>`;
    document.getElementById('toast-container').appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        t.classList.replace('translate-x-full', 'translate-x-0');
        t.classList.replace('opacity-0', 'opacity-100');
    }));
    document.getElementById('bar-' + id).style.cssText = 'transition:transform 4s linear;transform:scaleX(0)';
    setTimeout(() => removeToast(id), 4200);
}
function removeToast(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('translate-x-full', 'opacity-0');
    setTimeout(() => el.remove(), 300);
}

// ── Disparar toast desde PHP ───────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    <?php if ($pass_exito): ?>
        showToast('✓ Contraseña actualizada correctamente.', 'success');
    <?php endif; ?>
    <?php if ($pass_error): ?>
        showToast('<?= addslashes($pass_error) ?>', 'error');
    <?php endif; ?>
    <?php if (!empty($mensaje_error)): ?>
        abrirModalEditar();
        showToast('<?= addslashes($mensaje_error) ?>', 'error');
    <?php endif; ?>
});
</script>