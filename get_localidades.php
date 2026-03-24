<?php
include("conexion.php");

header('Content-Type: application/json');

$id_provincia = isset($_GET['id_provincia']) ? intval($_GET['id_provincia']) : 0;

if ($id_provincia > 0) {
    $sql = "SELECT id_localidad, nombre_localidad 
            FROM localidades 
            WHERE id_provincia = $id_provincia 
            ORDER BY nombre_localidad";
    
    $resultado = mysqli_query($conexion, $sql);
    
    $localidades = [];
    while ($loc = mysqli_fetch_assoc($resultado)) {
        $localidades[] = $loc;
    }
    
    echo json_encode($localidades);
} else {
    echo json_encode([]);
}
?>