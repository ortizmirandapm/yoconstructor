<?php
$page      = 'configuracion-empresa';
$pageTitle = 'Configuración';
include("conexion.php");

$id_empresa = $_SESSION['idempresa'] ?? null;
$id_usuario = $_SESSION['idusuario']  ?? null;
if (!$id_empresa || !$id_usuario) { header("Location: login.php"); exit; }

$ok_msg  = '';
$err_msg = '';

// ── 1. CAMBIAR CONTRASEÑA ────────────────────────────────────────────────────
if (isset($_POST['cambiar_password'])) {
    $actual    = $_POST['pass_actual']    ?? '';
    $nueva     = $_POST['pass_nueva']     ?? '';
    $confirmar = $_POST['pass_confirmar'] ?? '';

    $res = mysqli_query($conexion, "SELECT contrasena FROM users WHERE id_usuario = $id_usuario LIMIT 1");
    $row = mysqli_fetch_assoc($res);
    $hash = $row['contrasena'] ?? '';

    if (empty($actual) || empty($nueva) || empty($confirmar)) {
        $err_msg = 'Completá todos los campos de contraseña.';
    } elseif ($actual !== $hash) {
        // TODO: Reemplazar por password_verify cuando se active el hashing:
        // } elseif (!password_verify($actual, $hash)) {
        $err_msg = 'La contraseña actual no es correcta.';
    } elseif (strlen($nueva) < 6) {
        $err_msg = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $err_msg = 'Las contraseñas nuevas no coinciden.';
    } else {
        // Guardar en texto plano (temporal)
        $nueva_esc = mysqli_real_escape_string($conexion, $nueva);
        mysqli_query($conexion, "UPDATE users SET contrasena = '$nueva_esc' WHERE id_usuario = $id_usuario");

        // TODO: Activar hashing cuando se migre:
        // $hash_nueva = password_hash($nueva, PASSWORD_DEFAULT);
        // $stmt = mysqli_prepare($conexion, "UPDATE users SET contrasena = ? WHERE id_usuario = ?");
        // mysqli_stmt_bind_param($stmt, 'si', $hash_nueva, $id_usuario);
        // mysqli_stmt_execute($stmt);
        // mysqli_stmt_close($stmt);

        $ok_msg = 'Contraseña actualizada correctamente.';
    }
}

// ── 2. PRIVACIDAD ────────────────────────────────────────────────────────────
if (isset($_POST['guardar_privacidad'])) {
    $perfil_publico = isset($_POST['perfil_publico']) ? 1 : 0;

    $tbl = mysqli_query($conexion, "SHOW COLUMNS FROM empresa LIKE 'perfil_publico'");
    if ($tbl && mysqli_num_rows($tbl) > 0) {
        mysqli_query($conexion, "UPDATE empresa SET perfil_publico = $perfil_publico WHERE id_empresa = $id_empresa");
    }
    $ok_msg = 'Configuración de privacidad guardada.';
}

// ── 3. DAR DE BAJA ───────────────────────────────────────────────────────────
if (isset($_POST['dar_de_baja'])) {
    $confirm_text = trim($_POST['confirm_text'] ?? '');
    if ($confirm_text === 'ELIMINAR') {
        mysqli_query($conexion, "UPDATE ofertas_laborales SET estado = 'Inactiva' WHERE id_empresa = $id_empresa");
        mysqli_query($conexion, "UPDATE empresa SET estado = 'inactivo' WHERE id_empresa = $id_empresa");
        mysqli_query($conexion, "UPDATE users SET estado = 'inactivo' WHERE id_usuario = $id_usuario");
        session_destroy();
        header("Location: login.php?cuenta=baja");
        exit;
    } else {
        $err_msg = 'Texto de confirmación incorrecto. Escribí exactamente ELIMINAR.';
        // Abrir zona peligrosa si hay error
        $open_danger = true;
    }
}

// ── Cargar config actual ──────────────────────────────────────────────────────
$res_cfg = mysqli_query($conexion, "SELECT * FROM empresa WHERE id_empresa = $id_empresa LIMIT 1");
$cfg     = $res_cfg ? mysqli_fetch_assoc($res_cfg) : [];
$perfil_publico_val = $cfg['perfil_publico'] ?? 1;
$open_danger = $open_danger ?? false;

// ── Include sidebar DESPUÉS de todo el procesamiento POST ────────────────────
include("sidebar-empresa.php");
?>

<div class="min-h-screen bg-gray-50 p-6 md:p-8">

    <!-- Encabezado -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Configuración</h1>
        <p class="text-gray-500 mt-0.5 text-sm">Administrá las preferencias de tu cuenta de empresa</p>
    </div>

    <!-- Alertas -->
    <?php if ($ok_msg): ?>
    <div class="flex items-center gap-3 mb-6 px-5 py-3.5 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm font-medium">
        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= htmlspecialchars($ok_msg) ?>
    </div>
    <?php endif; ?>
    <?php if ($err_msg): ?>
    <div class="flex items-center gap-3 mb-6 px-5 py-3.5 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm font-medium">
        <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= htmlspecialchars($err_msg) ?>
    </div>
    <?php endif; ?>

    <div class="max-w-3xl space-y-5">

        <!-- ═══ CONTRASEÑA ══════════════════════════════════════════════════════ -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <button type="button" onclick="toggleSection('pass')"
                class="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition text-left">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Cambiar contraseña</p>
                        <p class="text-xs text-gray-400">Actualizá la contraseña de acceso a tu cuenta</p>
                    </div>
                </div>
                <svg id="arrow-pass" class="w-5 h-5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div id="section-pass" class="hidden border-t border-gray-100">
                <form method="POST" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Contraseña actual</label>
                        <input type="password" name="pass_actual" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                            placeholder="••••••••">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nueva contraseña</label>
                            <input type="password" name="pass_nueva" required minlength="6"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                placeholder="Mínimo 6 caracteres">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirmar contraseña</label>
                            <input type="password" name="pass_confirmar" required
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                placeholder="Repetí la contraseña">
                        </div>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="submit" name="cambiar_password"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Actualizar contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═══ PRIVACIDAD ═════════════════════════════════════════════════════ -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <button type="button" onclick="toggleSection('priv')"
                class="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition text-left">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-cyan-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Privacidad</p>
                        <p class="text-xs text-gray-400">Controlá la visibilidad de tu empresa</p>
                    </div>
                </div>
                <svg id="arrow-priv" class="w-5 h-5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div id="section-priv" class="hidden border-t border-gray-100">
                <form method="POST" class="px-6 py-5">
                    <div class="flex items-start justify-between gap-6 py-2">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">Perfil de empresa público</p>
                            <p class="text-xs text-gray-400 mt-1 leading-relaxed">
                                Cuando está activo, los trabajadores pueden ver tu perfil, logo, descripción y ofertas publicadas.
                                Si lo desactivás, tu empresa no será visible para ningún trabajador.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                            <input type="checkbox" name="perfil_publico" value="1"
                                <?= $perfil_publico_val ? 'checked' : '' ?>
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer
                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                after:content-[''] after:absolute after:top-[2px] after:start-[2px]
                                after:bg-white after:border-gray-300 after:border after:rounded-full
                                after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500"></div>
                        </label>
                    </div>
                    <div class="flex justify-end pt-5">
                        <button type="submit" name="guardar_privacidad"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═══ ZONA PELIGROSA ════════════════════════════════════════════════ -->
        <div class="bg-white border border-red-200 rounded-2xl shadow-sm overflow-hidden">
            <button type="button" onclick="toggleSection('danger')"
                class="w-full flex items-center justify-between px-6 py-4 hover:bg-red-50 transition text-left">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-red-700">Zona peligrosa</p>
                        <p class="text-xs text-red-400">Acciones irreversibles sobre tu cuenta</p>
                    </div>
                </div>
                <svg id="arrow-danger" class="w-5 h-5 text-red-300 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div id="section-danger" class="<?= $open_danger ? '' : 'hidden' ?> border-t border-red-100">
                <div class="px-6 py-5">

                    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <p class="text-sm font-semibold text-red-800 mb-2">Al dar de baja tu cuenta:</p>
                        <ul class="text-xs text-red-700 space-y-1 ml-4 list-disc">
                            <li>Todas tus ofertas publicadas pasarán a estado <strong>Inactiva</strong></li>
                            <li>Los trabajadores no podrán ver tu perfil ni postularse</li>
                            <li>Tus reclutadores perderán acceso al sistema</li>
                            <li>Esta acción <strong>no se puede deshacer</strong> sin contactar soporte</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                    Para confirmar, escribí <span class="font-mono bg-red-100 text-red-700 px-1.5 py-0.5 rounded text-xs">ELIMINAR</span>
                                </label>
                                <input type="text" name="confirm_text" required
                                    placeholder="ELIMINAR"
                                    autocomplete="off"
                                    class="w-full sm:w-56 px-3 py-2.5 border border-red-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-400 transition font-mono tracking-widest">
                            </div>
                            <button type="button" onclick="confirmarBaja()"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Dar de baja la cuenta
                            </button>
                        </div>
                        <input type="hidden" name="dar_de_baja" value="1" id="baja-hidden" disabled>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal confirmación baja -->
<div id="modalBaja" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">¿Dar de baja la cuenta?</h3>
                <p class="text-xs text-gray-400">Esta acción no se puede deshacer</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-600 leading-relaxed">Tu cuenta quedará desactivada, todas tus ofertas pasarán a inactivo y perderás acceso al sistema. Para reactivarla deberás contactar a soporte.</p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
            <button onclick="cerrarModalBaja()"
                class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button onclick="ejecutarBaja()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Sí, dar de baja
            </button>
        </div>
    </div>
</div>

        </main>
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
    
function toggleSection(id) {
    const section = document.getElementById('section-' + id);
    const arrow   = document.getElementById('arrow-' + id);
    const isOpen  = !section.classList.contains('hidden');
    section.classList.toggle('hidden', isOpen);
    arrow.classList.toggle('rotate-180', !isOpen);
}

function confirmarBaja() {
    const txt = document.querySelector('input[name="confirm_text"]').value.trim();
    if (txt !== 'ELIMINAR') {
        alert('Primero escribí ELIMINAR en el campo de confirmación.');
        return;
    }
    document.getElementById('modalBaja').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalBaja() {
    document.getElementById('modalBaja').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
function ejecutarBaja() {
    document.getElementById('baja-hidden').disabled = false;
    document.getElementById('baja-hidden').closest('form').submit();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalBaja(); });
document.getElementById('modalBaja').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalBaja();
});

// Si hay error en zona peligrosa, scrollear a ella
<?php if ($open_danger): ?>
document.getElementById('section-danger').classList.remove('hidden');
document.getElementById('arrow-danger').classList.add('rotate-180');
document.getElementById('section-danger').scrollIntoView({ behavior: 'smooth', block: 'center' });
<?php endif; ?>
</script>