<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Capturar todos los errores y warnings
//error_reporting(E_ALL);
//ini_set('display_errors', 0); // No mostrar en pantalla
//ini_set('log_errors', 1);

// Handler personalizado para errores y warnings
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $tipo = 'warn';
    $nombre = 'Warning';
    
    switch ($errno) {
        case E_ERROR:
        case E_USER_ERROR:
            $tipo = 'error';
            $nombre = 'Error';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $tipo = 'warn';
            $nombre = 'Warning';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $tipo = 'info';
            $nombre = 'Notice';
            break;
    }
    
    $mensaje = addslashes($errstr ?? '');
    $archivo = addslashes(basename($errfile ?? ''));
    
    echo "<script>console.{$tipo}('🔴 PHP {$nombre}:', '{$mensaje}');</script>";
    echo "<script>console.{$tipo}('📁 Archivo: {$archivo} | Línea: {$errline}');</script>";
    
    return false;
});





// Función para mostrar en consola del navegador
if (!function_exists('console_log')) {
function console_log($data, $tipo = 'log') {
    if (is_array($data) || is_object($data)) {
        $output = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        $output = json_encode($data ?? '', JSON_UNESCAPED_UNICODE);
    }
    echo "<script>console.{$tipo}({$output});</script>";
    } 
}

// Función para mostrar errores SQL
function console_error_sql($conexion, $mensaje = '') {
    if (mysqli_error($conexion)) {
        $error = addslashes(mysqli_error($conexion) ?? '');
        echo "<script>console.error('⚠️ Error SQL: {$mensaje}', '{$error}');</script>";
    }
}

session_name();
session_start();

$dbhost = "localhost";
$dbusuario = "root";
$dbpassword = "";
$db = "yo_constructor";

$conexion = mysqli_connect($dbhost, $dbusuario, $dbpassword, $db);


// Configurar charset UTF-8
mysqli_set_charset($conexion, "utf8");

// --- FUNCIÓN AUDITORÍA ---
if (!function_exists('registrar_auditoria')) {
    function registrar_auditoria($conexion, $id_usuario, $id_empresa, $accion, $entidad, $id_entidad, $detalle = '') {
        $detalle    = mysqli_real_escape_string($conexion, $detalle);
        $accion     = mysqli_real_escape_string($conexion, $accion);
        $entidad    = mysqli_real_escape_string($conexion, $entidad);
        $id_usuario = intval($id_usuario);
        $id_empresa = intval($id_empresa);
        $id_entidad = intval($id_entidad);
        mysqli_query($conexion,
            "INSERT INTO auditoria (id_usuario, id_empresa, accion, entidad, id_entidad, detalle)
             VALUES ($id_usuario, $id_empresa, '$accion', '$entidad', $id_entidad, '$detalle')"
        );
    }
}

?>