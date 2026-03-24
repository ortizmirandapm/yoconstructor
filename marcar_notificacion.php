<?php
/**
 * marcar_notificacion.php
 * Endpoint AJAX — marca una notificación como leída.
 * Llamado con fetch() desde navbar-trabajador.php
 */
include_once("conexion.php");

if (!isset($_SESSION['idusuario']) || $_SESSION['tipo'] != 2) {
    http_response_code(403);
    exit;
}

$id_usuario     = intval($_SESSION['idusuario']);
$id_notificacion = intval($_POST['id_notificacion'] ?? 0);

if ($id_notificacion > 0) {
    mysqli_query($conexion,
        "UPDATE notificaciones SET leida = 1
         WHERE id_notificacion = $id_notificacion
           AND id_usuario = $id_usuario"
    );
}

http_response_code(200);