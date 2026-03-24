<?php
include("conexion.php");

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ofertas-laborales.php");
    exit;
}

// Debe estar logueado y ser trabajador (tipo 2)
if (!isset($_SESSION['idusuario']) || intval($_SESSION['tipo'] ?? 0) !== 2) {
    header("Location: login.php");
    exit;
}

$id_oferta  = intval($_POST['id_oferta'] ?? 0);
$id_persona = intval($_SESSION['idpersona'] ?? 0);

if ($id_oferta <= 0 || $id_persona <= 0) {
    header("Location: ofertas-laborales.php");
    exit;
}

// Verificar que la oferta existe y está activa
$check_oferta = mysqli_query($conexion, "SELECT id_oferta FROM ofertas_laborales WHERE id_oferta = $id_oferta AND estado = 'Activa'");
if (!$check_oferta || mysqli_num_rows($check_oferta) === 0) {
    header("Location: ofertas-laborales.php?error=oferta_no_disponible");
    exit;
}

// Verificar que no se haya postulado ya
$check_dup = mysqli_query($conexion, "SELECT id_postulacion FROM postulaciones WHERE id_oferta = $id_oferta AND id_persona = $id_persona");
if ($check_dup && mysqli_num_rows($check_dup) > 0) {
    header("Location: ofertas-laborales.php?ver=$id_oferta&error=ya_postulado");
    exit;
}

// Insertar postulación
$sql = "INSERT INTO postulaciones (id_oferta, id_persona) VALUES ($id_oferta, $id_persona)";
$result = mysqli_query($conexion, $sql);

if ($result) {
    header("Location: ofertas-laborales.php?ver=$id_oferta&exito=postulado");
} else {
    header("Location: ofertas-laborales.php?ver=$id_oferta&error=fallo");
}
exit;
?>