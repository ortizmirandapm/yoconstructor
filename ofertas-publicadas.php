<?php
$page = 'ofertas-publicadas';
$pageTitle = 'Ofertas publicadas';
include("conexion.php");

$id_empresa  = $_SESSION['idempresa'] ?? null;
$id_usuario  = $_SESSION['idusuario'] ?? null;

// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_empresa) {
    $accion    = $_POST['accion'] ?? '';
    $id_oferta = intval($_POST['id_oferta'] ?? 0);
    $toast     = '';

    if ($id_oferta > 0) {
        if ($accion === 'toggle_estado') {
            $res = mysqli_query($conexion, "SELECT estado FROM ofertas_laborales WHERE id_oferta = $id_oferta AND id_empresa = $id_empresa");
            $row = mysqli_fetch_assoc($res);
            if ($row) {
                $nuevo = $row['estado'] === 'Activa' ? 'Pausada' : 'Activa';
                mysqli_query($conexion, "UPDATE ofertas_laborales SET estado = '$nuevo' WHERE id_oferta = $id_oferta AND id_empresa = $id_empresa");
                $toast = $nuevo === 'Pausada' ? 'ok_pausada' : 'ok_activada';
            }
        } elseif ($accion === 'eliminar') {
            $res_titulo    = mysqli_query($conexion, "SELECT titulo FROM ofertas_laborales WHERE id_oferta = $id_oferta AND id_empresa = $id_empresa");
            $row_titulo    = mysqli_fetch_assoc($res_titulo);
            $titulo_oferta = $row_titulo['titulo'] ?? "ID $id_oferta";

            mysqli_query($conexion, "UPDATE ofertas_laborales SET estado = 'Borrador' WHERE id_oferta = $id_oferta AND id_empresa = $id_empresa");

            if (mysqli_affected_rows($conexion) > 0) {
                registrar_auditoria(
                    $conexion,
                    $id_usuario,
                    $id_empresa,
                    'eliminar_oferta',
                    'oferta',
                    $id_oferta,
                    "Eliminó oferta: $titulo_oferta"
                );
                $toast = 'ok_eliminada';
            }
        } elseif ($accion === 'editar') {
            $titulo        = trim($_POST['titulo'] ?? '');
            $descripcion   = trim($_POST['descripcion'] ?? '');
            $requisitos    = trim($_POST['requisitos'] ?? '');
            $id_esp        = intval($_POST['id_especialidad'] ?? 0);
            $modalidad     = $_POST['modalidad'] ?? 'Presencial';
            $tipo_contrato = $_POST['tipo_contrato'] ?? 'Tiempo completo';
            $salario_min   = ($_POST['salario_min'] ?? '') !== '' ? floatval($_POST['salario_min']) : 'NULL';
            $salario_max   = ($_POST['salario_max'] ?? '') !== '' ? floatval($_POST['salario_max']) : 'NULL';
            $experiencia   = ($_POST['experiencia_requerida'] ?? '') !== '' ? intval($_POST['experiencia_requerida']) : 'NULL';
            $fecha_venc    = !empty($_POST['fecha_vencimiento']) ? "'" . mysqli_real_escape_string($conexion, $_POST['fecha_vencimiento']) . "'" : 'NULL';
            $id_provincia  = !empty($_POST['id_provincia']) ? intval($_POST['id_provincia']) : 'NULL';
            $id_localidad  = !empty($_POST['id_localidad']) ? intval($_POST['id_localidad']) : 'NULL';
            $titulo_esc    = mysqli_real_escape_string($conexion, $titulo);
            $desc_esc      = mysqli_real_escape_string($conexion, $descripcion);
            $req_esc       = mysqli_real_escape_string($conexion, $requisitos);
            $mod_esc       = mysqli_real_escape_string($conexion, $modalidad);
            $con_esc       = mysqli_real_escape_string($conexion, $tipo_contrato);

            if (!empty($titulo) && !empty($descripcion) && $id_esp > 0) {
                mysqli_query(
                    $conexion,
                    "UPDATE ofertas_laborales SET
                        titulo                = '$titulo_esc',
                        descripcion           = '$desc_esc',
                        requisitos            = '$req_esc',
                        id_especialidad       = $id_esp,
                        modalidad             = '$mod_esc',
                        tipo_contrato         = '$con_esc',
                        salario_min           = $salario_min,
                        salario_max           = $salario_max,
                        experiencia_requerida = $experiencia,
                        fecha_vencimiento     = $fecha_venc,
                        id_provincia          = $id_provincia,
                        id_localidad          = $id_localidad
                    WHERE id_oferta = $id_oferta AND id_empresa = $id_empresa"
                );

                if (mysqli_affected_rows($conexion) > 0) {
                    registrar_auditoria(
                        $conexion,
                        $id_usuario,
                        $id_empresa,
                        'editar_oferta',
                        'oferta',
                        $id_oferta,
                        "Editó oferta: $titulo"
                    );
                    $toast = 'ok_editada';
                } else {
                    $toast = 'ok_sin_cambios';
                }
            } else {
                $toast = 'err_editar';
            }
        }
    }

    $qs_params = $_GET;
    if ($toast) $qs_params['toast'] = $toast;
    $qs = http_build_query($qs_params);
    header('Location: ofertas-publicadas.php' . ($qs ? '?' . $qs : ''));
    exit;
}

include("sidebar-empresa.php");

// --- FILTROS GET ---
$search           = trim($_GET['q'] ?? '');
$filtro_esp       = intval($_GET['especialidad'] ?? 0);
$filtro_estado    = $_GET['estado'] ?? '';
$filtro_orden     = $_GET['orden'] ?? 'desc';
$filtro_provincia = intval($_GET['provincia'] ?? 0);
$toast_msg        = $_GET['toast'] ?? '';

// --- DATOS PARA SELECTS ---
$especialidades = [];
$res_esp = mysqli_query($conexion, "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
while ($r = mysqli_fetch_assoc($res_esp)) $especialidades[] = $r;

$provincias = [];
$res_prov = mysqli_query($conexion, "SELECT id_provincia, nombre FROM provincias ORDER BY nombre ASC");
while ($r = mysqli_fetch_assoc($res_prov)) $provincias[] = $r;

// ── Auto-cerrar ofertas vencidas ───────────────────────────────────────────
if ($id_empresa) {
    mysqli_query(
        $conexion,
        "UPDATE ofertas_laborales
         SET estado = 'Cerrada'
         WHERE id_empresa = $id_empresa
           AND estado IN ('Activa','Pausada')
           AND fecha_vencimiento IS NOT NULL
           AND fecha_vencimiento < CURDATE()"
    );
}

// --- QUERY PRINCIPAL ---
$where = ["o.id_empresa = $id_empresa", "o.estado != 'Borrador'"];

if ($search !== '') {
    $s       = mysqli_real_escape_string($conexion, $search);
    $where[] = "(o.titulo LIKE '%$s%' OR o.descripcion LIKE '%$s%')";
}
if ($filtro_esp > 0)    $where[] = "o.id_especialidad = $filtro_esp";
if (in_array($filtro_estado, ['Activa', 'Pausada', 'Cerrada']))
    $where[] = "o.estado = '" . mysqli_real_escape_string($conexion, $filtro_estado) . "'";
if ($filtro_provincia > 0) $where[] = "o.id_provincia = $filtro_provincia";

$order     = $filtro_orden === 'asc' ? 'ASC' : 'DESC';
$where_sql = implode(' AND ', $where);

$sql = "SELECT o.*, e.nombre_especialidad, p.nombre AS nombre_provincia,
               (SELECT COUNT(*) FROM postulaciones po WHERE po.id_oferta = o.id_oferta) AS total_postulantes
        FROM ofertas_laborales o
        LEFT JOIN especialidades e ON o.id_especialidad = e.id_especialidad
        LEFT JOIN provincias p ON o.id_provincia = p.id_provincia
        WHERE $where_sql
        ORDER BY o.fecha_publicacion $order";

$result  = mysqli_query($conexion, $sql);
$ofertas = [];
while ($r = mysqli_fetch_assoc($result)) $ofertas[] = $r;

// --- HELPERS ---
function estadoBadge($estado)
{
    return match ($estado) {
        'Activa'  => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Activa</span>',
        'Pausada' => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700"><span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>Pausada</span>',
        'Cerrada' => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Cerrada</span>',
        default   => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">' . htmlspecialchars($estado) . '</span>',
    };
}

$ic = "w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="min-h-screen bg-gray-50 p-6 md:p-10">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Mis ofertas publicadas</h1>
            <p class="text-gray-500 mt-1 text-sm">
                <?= count($ofertas) ?> oferta<?= count($ofertas) !== 1 ? 's' : '' ?> encontrada<?= count($ofertas) !== 1 ? 's' : '' ?>
            </p>
        </div>
        <a href="nueva-oferta.php"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva oferta
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET" action="" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Buscar por título..."
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
            </div>
            <select name="especialidad" class="<?= $ic ?>">
                <option value="">Todas las especialidades</option>
                <?php foreach ($especialidades as $esp): ?>
                    <option value="<?= $esp['id_especialidad'] ?>" <?= $filtro_esp == $esp['id_especialidad'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($esp['nombre_especialidad']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="estado" class="<?= $ic ?>">
                <option value="">Todos los estados</option>
                <option value="Activa"  <?= $filtro_estado === 'Activa'  ? 'selected' : '' ?>>Activa</option>
                <option value="Pausada" <?= $filtro_estado === 'Pausada' ? 'selected' : '' ?>>Pausada</option>
                <option value="Cerrada" <?= $filtro_estado === 'Cerrada' ? 'selected' : '' ?>>Cerrada</option>
            </select>
            <select name="provincia" class="<?= $ic ?>">
                <option value="">Todas las provincias</option>
                <?php foreach ($provincias as $prov): ?>
                    <option value="<?= $prov['id_provincia'] ?>" <?= $filtro_provincia == $prov['id_provincia'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prov['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="orden" class="<?= $ic ?>">
                <option value="desc" <?= $filtro_orden !== 'asc' ? 'selected' : '' ?>>Más recientes primero</option>
                <option value="asc"  <?= $filtro_orden === 'asc'  ? 'selected' : '' ?>>Más antiguas primero</option>
            </select>
        </div>
        <div class="flex gap-3 mt-4">
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">Filtrar</button>
            <a href="ofertas-publicadas.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-5 py-2 rounded-lg transition">Limpiar</a>
        </div>
    </form>

    <!-- Cards -->
    <?php if (empty($ofertas)): ?>
        <div class="bg-white border border-dashed border-gray-300 rounded-xl p-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500 font-medium">No hay ofertas que coincidan con los filtros.</p>
            <a href="nueva-oferta.php" class="inline-block mt-4 text-cyan-600 hover:underline text-sm font-medium">Publicar tu primera oferta →</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($ofertas as $o): ?>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition flex flex-col">

                    <!-- Header card -->
                    <div class="p-5 border-b border-gray-100">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-800 text-sm leading-snug truncate" title="<?= htmlspecialchars($o['titulo']) ?>">
                                    <?= htmlspecialchars($o['titulo']) ?>
                                </h3>
                                <p class="text-xs text-cyan-600 font-medium mt-0.5">
                                    <?= htmlspecialchars($o['nombre_especialidad'] ?? '—') ?>
                                </p>
                            </div>
                            <?= estadoBadge($o['estado']) ?>
                        </div>
                    </div>

                    <!-- Body card -->
                    <div class="p-5 flex-1 space-y-3">
                        <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-gray-500">
                            <?php if ($o['nombre_provincia']): ?>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <?= htmlspecialchars($o['nombre_provincia']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <?= htmlspecialchars($o['tipo_contrato']) ?>
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Publicado: <?= date('d/m/Y', strtotime($o['fecha_publicacion'])) ?>
                            </span>
                        </div>

                        <?php if ($o['salario_min'] || $o['salario_max']): ?>
                            <div class="text-xs text-gray-600 font-medium bg-gray-50 rounded-lg px-3 py-2">
                                
                                <?php if ($o['salario_min'] && $o['salario_max']): ?>
                                    $<?= number_format($o['salario_min'], 0, ',', '.') ?> – $<?= number_format($o['salario_max'], 0, ',', '.') ?> ARS
                                <?php elseif ($o['salario_min']): ?>
                                    Desde $<?= number_format($o['salario_min'], 0, ',', '.') ?> ARS
                                <?php else: ?>
                                    Hasta $<?= number_format($o['salario_max'], 0, ',', '.') ?> ARS
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5 bg-cyan-50 text-cyan-700 text-xs font-semibold px-3 py-1.5 rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?= $o['total_postulantes'] ?> postulante<?= $o['total_postulantes'] !== '1' ? 's' : '' ?>
                            </div>
                            <?php if ($o['fecha_vencimiento']): ?>
                                <?php $vencida = strtotime($o['fecha_vencimiento']) < strtotime('today'); ?>
                                <span class="text-xs <?= $vencida ? 'text-red-500 font-semibold' : 'text-gray-400' ?>">
                                    <?= $vencida ? 'Cerrada ' : 'Cierra: ' ?>
                                    <?= date('d/m/Y', strtotime($o['fecha_vencimiento'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                        <div class="grid grid-cols-2 gap-2">

                            <button type="button"
                                onclick="abrirModalVer(<?= htmlspecialchars(json_encode($o), ENT_QUOTES) ?>)"
                                class="flex items-center justify-center gap-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 px-3 py-2 rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Ver / Editar
                            </button>

                            <a href="postulantes.php?id=<?= $o['id_oferta'] ?>"
                                class="flex items-center justify-center gap-1.5 text-xs font-medium text-cyan-700 bg-cyan-50 border border-cyan-200 hover:bg-cyan-100 px-3 py-2 rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Postulantes
                            </a>

                            <form method="POST" action="">
                                <input type="hidden" name="accion" value="toggle_estado">
                                <input type="hidden" name="id_oferta" value="<?= $o['id_oferta'] ?>">
                                <button type="submit" class="w-full flex items-center justify-center gap-1.5 text-xs font-medium px-3 py-2 rounded-lg border transition
                                <?= $o['estado'] === 'Activa'
                                    ? 'text-yellow-700 bg-yellow-50 border-yellow-200 hover:bg-yellow-100'
                                    : 'text-green-700 bg-green-50 border-green-200 hover:bg-green-100' ?>">
                                    <?php if ($o['estado'] === 'Activa'): ?>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Pausar
                                    <?php else: ?>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Activar
                                    <?php endif; ?>
                                </button>
                            </form>

                            <button type="button"
                                onclick="abrirModalEliminar(<?= $o['id_oferta'] ?>, '<?= htmlspecialchars(addslashes($o['titulo'])) ?>')"
                                class="flex items-center justify-center gap-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 hover:bg-red-100 px-3 py-2 rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Eliminar
                            </button>

                        </div>
                    </div>

                </div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ===== MODAL VER OFERTA ===== -->
<div id="modalVer" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarModalVer()"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-800 leading-snug" id="ver_titulo"></h2>
                        <p class="text-sm text-blue-600 font-medium mt-0.5" id="ver_especialidad"></p>
                    </div>
                </div>
                <button onclick="cerrarModalVer()" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100 flex-shrink-0 ml-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body scrollable -->
            <div class="overflow-y-auto flex-1 p-6 space-y-5">

                <!-- Badges de estado / contrato / modalidad -->
                <div class="flex flex-wrap gap-2" id="ver_badges"></div>

                <!-- Chips de info rápida -->
                <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-500" id="ver_chips"></div>

                <!-- Salario -->
                <div id="ver_salario_wrap" class="hidden">
                    <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm font-semibold text-green-800" id="ver_salario"></div>
                </div>

                <!-- Descripción -->
                <div>
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Descripción</h4>
                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line" id="ver_descripcion"></p>
                </div>

                <!-- Requisitos -->
                <div id="ver_requisitos_wrap" class="hidden">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Requisitos</h4>
                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line" id="ver_requisitos"></p>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-between flex-shrink-0">
                <p class="text-xs text-gray-400" id="ver_fecha_publicacion"></p>
                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalVer()"
                        class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        Cerrar
                    </button>
                    <button type="button" id="btn_ver_editar"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-cyan-600 hover:bg-cyan-700 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar oferta
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ===== MODAL EDITAR ===== -->
<div id="modalEditar" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarModal()"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-cyan-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-800">Editar oferta</h2>
                </div>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="" id="formEditar" class="overflow-y-auto flex-1 flex flex-col">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_oferta" id="edit_id_oferta">
                <div class="p-6 space-y-5 flex-1">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Título <span class="text-red-500">*</span></label>
                        <input type="text" name="titulo" id="edit_titulo" required maxlength="200" class="<?= $ic ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Descripción <span class="text-red-500">*</span></label>
                        <textarea name="descripcion" id="edit_descripcion" rows="4" required class="<?= $ic ?> resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Requisitos</label>
                        <textarea name="requisitos" id="edit_requisitos" rows="3" class="<?= $ic ?> resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Especialidad <span class="text-red-500">*</span></label>
                            <select name="id_especialidad" id="edit_id_especialidad" required class="<?= $ic ?>">
                                <option value="">-- Seleccioná --</option>
                                <?php foreach ($especialidades as $esp): ?>
                                    <option value="<?= $esp['id_especialidad'] ?>"><?= htmlspecialchars($esp['nombre_especialidad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Años de experiencia</label>
                            <input type="number" name="experiencia_requerida" id="edit_experiencia" min="0" max="50" class="<?= $ic ?>">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Tipo de contrato</label>
                            <select name="tipo_contrato" id="edit_tipo_contrato" class="<?= $ic ?>">
                                <option value="Tiempo completo">Tiempo completo</option>
                                <option value="Medio tiempo">Medio tiempo</option>
                                <option value="Por proyecto">Por proyecto</option>
                                <option value="Pasantía">Pasantía</option>
                                <option value="Temporal">Temporal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Modalidad</label>
                            <select name="modalidad" id="edit_modalidad" class="<?= $ic ?>">
                                <option value="Presencial">Presencial</option>
                                <option value="Remoto">Remoto</option>
                                <option value="Híbrido">Híbrido</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Salario mínimo (ARS)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                <input type="number" name="salario_min" id="edit_salario_min" min="0"
                                    class="w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Salario máximo (ARS)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                <input type="number" name="salario_max" id="edit_salario_max" min="0"
                                    class="w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Provincia</label>
                            <select name="id_provincia" id="edit_id_provincia" onchange="cargarLocalidadesModal(this.value)" class="<?= $ic ?>">
                                <option value="">-- Seleccioná provincia --</option>
                                <?php foreach ($provincias as $prov): ?>
                                    <option value="<?= $prov['id_provincia'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Localidad</label>
                            <select name="id_localidad" id="edit_id_localidad" class="<?= $ic ?>">
                                <option value="">-- Seleccioná primero una provincia --</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Cierre de postulaciones</label>
                        <input type="date" name="fecha_vencimiento" id="edit_fecha_vencimiento" min="<?= date('Y-m-d') ?>" class="<?= $ic ?>">
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-end gap-3 flex-shrink-0">
                    <button type="button" onclick="cerrarModal()"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">Cancelar</button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-cyan-600 hover:bg-cyan-700 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL ELIMINAR ===== -->
<div id="modalEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Eliminar oferta</h3>
                <p class="text-xs text-gray-400 truncate max-w-xs" id="modal-elim-nombre"></p>
            </div>
        </div>
        <form method="POST" action="" id="form-eliminar-global">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_oferta" id="elim-hidden-id">
            <div class="px-6 py-5">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <p class="text-xs text-amber-700">La oferta pasará a <span class="font-semibold">Borradores</span> y dejará de ser visible para los trabajadores. Podés restaurarla desde esa sección cuando quieras.</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEliminar()"
                    class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Sí, eliminar oferta
                </button>
            </div>
        </form>
    </div>
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
                class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-100 transition">Cancelar</button>
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

<!-- Toast container — abajo a la derecha -->
<div id="toast-container" class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 items-end pointer-events-none [&>*]:pointer-events-auto"></div>

<script>
// ── Toast ─────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const id  = 'toast-' + Date.now();
    const cfg = {
        success: { border: 'border-green-200', bar: 'bg-green-500', icon: `<svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>` },
        warning: { border: 'border-yellow-200', bar: 'bg-yellow-400', icon: `<svg class="w-5 h-5 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>` },
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

// ── Disparar toast desde query param ─────────────────────────────────────
const toastMap = {
    ok_editada:    ['✓ Oferta actualizada correctamente.',          'success'],
    ok_eliminada:  ['✓ Oferta movida a Borradores.',               'success'],
    ok_pausada:    ['⏸ Oferta pausada correctamente.',             'warning'],
    ok_activada:   ['▶ Oferta activada correctamente.',            'success'],
    ok_sin_cambios:['Sin cambios detectados.',                     'warning'],
    err_editar:    ['Error: completá los campos obligatorios.',    'error'],
};
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const key    = params.get('toast');
    if (key && toastMap[key]) {
        showToast(...toastMap[key]);
        // Limpiar el param de la URL sin recargar
        params.delete('toast');
        const qs = params.toString();
        history.replaceState({}, '', 'ofertas-publicadas.php' + (qs ? '?' + qs : ''));
    }
});

// ── Sesión ────────────────────────────────────────────────────────────────
function abrirModalSesion()  { document.getElementById('modalCerrarSesion').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function cerrarModalSesion() { document.getElementById('modalCerrarSesion').classList.add('hidden');    document.body.style.overflow = 'auto';   }
document.getElementById('modalCerrarSesion').addEventListener('click', function(e) { if (e.target === this) cerrarModalSesion(); });

// ── Eliminar ──────────────────────────────────────────────────────────────
function abrirModalEliminar(id, titulo) {
    document.getElementById('modal-elim-nombre').textContent = titulo;
    document.getElementById('elim-hidden-id').value = id;
    document.getElementById('modalEliminar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalEliminar() {
    document.getElementById('modalEliminar').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
document.getElementById('modalEliminar').addEventListener('click', function(e) { if (e.target === this) cerrarModalEliminar(); });

// ── Ver oferta ────────────────────────────────────────────────────────────
let _ofertaActual = null;

function abrirModalVer(o) {
    _ofertaActual = o;

    document.getElementById('ver_titulo').textContent      = o.titulo ?? '';
    document.getElementById('ver_especialidad').textContent = o.nombre_especialidad ?? '';
    document.getElementById('ver_descripcion').textContent  = o.descripcion ?? '';

    // Requisitos
    const reqWrap = document.getElementById('ver_requisitos_wrap');
    if (o.requisitos && o.requisitos.trim()) {
        document.getElementById('ver_requisitos').textContent = o.requisitos;
        reqWrap.classList.remove('hidden');
    } else {
        reqWrap.classList.add('hidden');
    }

    // Badges: estado + contrato + modalidad
    const estadoColor = {
        'Activa':  'bg-green-100 text-green-700',
        'Pausada': 'bg-yellow-100 text-yellow-700',
        'Cerrada': 'bg-red-100 text-red-600',
    };
    const sc = estadoColor[o.estado] || 'bg-gray-100 text-gray-500';
    let badges = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${sc}">${o.estado ?? ''}</span>`;
    if (o.tipo_contrato) badges += `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">${o.tipo_contrato}</span>`;
    if (o.modalidad)    badges += `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600">${o.modalidad}</span>`;
    document.getElementById('ver_badges').innerHTML = badges;

    // Chips: provincia · localidad · experiencia · cierre
    let chips = '';
    if (o.nombre_provincia) chips += `<span class="flex items-center gap-1"><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>${o.nombre_provincia}</span>`;
    if (o.experiencia_requerida) chips += `<span class="flex items-center gap-1"><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>${o.experiencia_requerida} año${o.experiencia_requerida != 1 ? 's' : ''} de experiencia</span>`;
    if (o.fecha_vencimiento) {
        const vencida = new Date(o.fecha_vencimiento) < new Date(new Date().toDateString());
        const fmtDate = new Date(o.fecha_vencimiento).toLocaleDateString('es-AR', {day:'2-digit', month:'2-digit', year:'numeric', timeZone:'UTC'});
        chips += `<span class="flex items-center gap-1 ${vencida ? 'text-red-500 font-semibold' : ''}"><svg class="w-4 h-4 ${vencida ? 'text-red-400' : 'text-gray-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>${vencida ? 'Cerrada: ' : 'Cierre: '}${fmtDate}</span>`;
    }
    document.getElementById('ver_chips').innerHTML = chips;

    // Salario
    const salWrap = document.getElementById('ver_salario_wrap');
    if (o.salario_min || o.salario_max) {
        let sal = ' ';
        const fmt = n => Number(n).toLocaleString('es-AR');
        if (o.salario_min && o.salario_max) sal += `$${fmt(o.salario_min)} – $${fmt(o.salario_max)} ARS`;
        else if (o.salario_min)             sal += `Desde $${fmt(o.salario_min)} ARS`;
        else                                sal += `Hasta $${fmt(o.salario_max)} ARS`;
        document.getElementById('ver_salario').textContent = sal;
        salWrap.classList.remove('hidden');
    } else {
        salWrap.classList.add('hidden');
    }

    // Fecha publicación
    const fp = o.fecha_publicacion
        ? 'Publicada el ' + new Date(o.fecha_publicacion.replace(' ', 'T')).toLocaleDateString('es-AR', {day:'2-digit', month:'2-digit', year:'numeric'})
        : '';
    document.getElementById('ver_fecha_publicacion').textContent = fp;

    // Botón editar → cierra ver y abre editar
    document.getElementById('btn_ver_editar').onclick = function() {
        cerrarModalVer();
        abrirModalEditar(_ofertaActual);
    };

    document.getElementById('modalVer').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModalVer() {
    document.getElementById('modalVer').classList.add('hidden');
    document.body.style.overflow = '';
}
document.getElementById('modalVer').addEventListener('click', function(e) { if (e.target === this) cerrarModalVer(); });

// ── Editar ────────────────────────────────────────────────────────────────
let localidadPendiente = null;

function abrirModalEditar(o) {
    document.getElementById('edit_id_oferta').value = o.id_oferta;
    document.getElementById('edit_titulo').value = o.titulo ?? '';
    document.getElementById('edit_descripcion').value = o.descripcion ?? '';
    document.getElementById('edit_requisitos').value = o.requisitos ?? '';
    document.getElementById('edit_experiencia').value = o.experiencia_requerida ?? '';
    document.getElementById('edit_salario_min').value = o.salario_min ?? '';
    document.getElementById('edit_salario_max').value = o.salario_max ?? '';
    document.getElementById('edit_fecha_vencimiento').value = o.fecha_vencimiento ?? '';
    setSelect('edit_id_especialidad', o.id_especialidad);
    setSelect('edit_tipo_contrato', o.tipo_contrato);
    setSelect('edit_modalidad', o.modalidad);
    setSelect('edit_id_provincia', o.id_provincia);
    if (o.id_provincia) {
        localidadPendiente = o.id_localidad;
        cargarLocalidadesModal(o.id_provincia);
    } else {
        document.getElementById('edit_id_localidad').innerHTML = '<option value="">-- Seleccioná primero una provincia --</option>';
    }
    document.getElementById('modalEditar').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function cerrarModal() {
    document.getElementById('modalEditar').classList.add('hidden');
    document.body.style.overflow = '';
}
function setSelect(id, value) {
    const sel = document.getElementById(id);
    for (let opt of sel.options) opt.selected = (opt.value == value);
}
function cargarLocalidadesModal(idProvincia) {
    const sel = document.getElementById('edit_id_localidad');
    sel.innerHTML = '<option value="">Cargando...</option>';
    if (!idProvincia) { sel.innerHTML = '<option value="">-- Seleccioná primero una provincia --</option>'; return; }
    fetch('get_localidades.php?id_provincia=' + idProvincia)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">-- Todas las localidades --</option>';
            data.forEach(loc => {
                const opt = document.createElement('option');
                opt.value = loc.id_localidad;
                opt.textContent = loc.nombre_localidad;
                sel.appendChild(opt);
            });
            if (localidadPendiente) { setSelect('edit_id_localidad', localidadPendiente); localidadPendiente = null; }
        })
        .catch(() => { sel.innerHTML = '<option value="">Error al cargar</option>'; });
}
document.getElementById('formEditar').addEventListener('submit', function(e) {
    const min = parseFloat(document.getElementById('edit_salario_min').value) || 0;
    const max = parseFloat(document.getElementById('edit_salario_max').value) || 0;
    if (min > 0 && max > 0 && min > max) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Salario inválido', text: 'El mínimo no puede ser mayor al máximo.', confirmButtonColor: '#0891b2' });
    }
});

// ── Escape global ─────────────────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarModalVer(); cerrarModal(); cerrarModalEliminar(); cerrarModalSesion(); }
});
</script>
</body>
</html>

<?php
if (isset($_SESSION)) {
    echo "<script>console.group('🔐 Variables de Sesión');</script>";
    foreach ($_SESSION as $key => $value) {
        if (is_array($value) || is_object($value)) {
            $val = json_encode($value);
            echo "<script>console.log('{$key}:', {$val});</script>";
        } else {
            $val = $value !== null ? addslashes($value) : 'null';
            echo "<script>console.log('{$key}:', '{$val}');</script>";
        }
    }
    echo "<script>console.groupEnd();</script>";
}
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $mensaje = addslashes($error['message'] ?? '');
        $archivo = addslashes(basename($error['file'] ?? ''));
        $linea   = $error['line'] ?? 0;
        echo "<script>console.error('💥 Error Fatal:', '{$mensaje}');</script>";
        echo "<script>console.error('📁 Archivo: {$archivo} | Línea: {$linea}');</script>";
    }
});