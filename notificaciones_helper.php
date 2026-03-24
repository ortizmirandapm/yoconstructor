<?php
/**
 * notificaciones_helper.php
 * Incluir con: include_once("notificaciones_helper.php");
 * Requiere que $conexion ya esté disponible.
 */

// ============================================================
// 1. INSERTAR UNA NOTIFICACIÓN (evita duplicados por día)
// ============================================================
function insertar_notificacion($conexion, $id_usuario, $tipo, $titulo, $mensaje, $url_accion = null) {
    // Evitar duplicar la misma notificación en el mismo día
    $titulo_esc   = mysqli_real_escape_string($conexion, $titulo);
    $url_esc      = $url_accion ? "'" . mysqli_real_escape_string($conexion, $url_accion) . "'" : "NULL";
    $tipo_esc     = mysqli_real_escape_string($conexion, $tipo);
    $id_usuario   = intval($id_usuario);

    $existe = mysqli_query($conexion,
        "SELECT id_notificacion FROM notificaciones
         WHERE id_usuario = $id_usuario
           AND tipo = '$tipo_esc'
           AND titulo = '$titulo_esc'
           AND DATE(fecha_creacion) = CURDATE()
         LIMIT 1"
    );
    if ($existe && mysqli_num_rows($existe) > 0) return false; // Ya existe hoy

    $mensaje_esc = mysqli_real_escape_string($conexion, $mensaje);
    mysqli_query($conexion,
        "INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url_accion, leida)
         VALUES ($id_usuario, '$tipo_esc', '$titulo_esc', '$mensaje_esc', $url_esc, 0)"
    );
    return mysqli_affected_rows($conexion) > 0;
}

// ============================================================
// 2. CHEQUEAR PERFIL INCOMPLETO (para tipo 2 = trabajador)
// ============================================================
function chequear_perfil_incompleto($conexion, $id_usuario, $id_persona) {
    $id_persona = intval($id_persona);
    $id_usuario = intval($id_usuario);

    // Traer datos de la persona
    $res = mysqli_query($conexion,
        "SELECT dni, apellido, nombre, telefono, fecha_nacimiento,
                nombre_titulo, domicilio, id_provincia_preferencia,
                id_localidad_preferencia, descripcion_persona
         FROM persona WHERE id_persona = $id_persona LIMIT 1"
    );
    if (!$res || mysqli_num_rows($res) === 0) return;
    $p = mysqli_fetch_assoc($res);

    // Campos obligatorios y sus etiquetas legibles
    $campos = [
        'dni'                       => 'DNI',
        'apellido'                  => 'Apellido',
        'nombre'                    => 'Nombre',
        'telefono'                  => 'Teléfono',
        'fecha_nacimiento'          => 'Fecha de nacimiento',
        'nombre_titulo'             => 'Título / Oficio',
        'domicilio'                 => 'Domicilio',
        'id_provincia_preferencia'  => 'Provincia preferida',
        'id_localidad_preferencia'  => 'Localidad preferida',
        'descripcion_persona'       => 'Descripción personal',
    ];

    $faltantes = [];
    foreach ($campos as $campo => $etiqueta) {
        if (empty($p[$campo])) {
            $faltantes[] = $etiqueta;
        }
    }

    // Chequear si tiene al menos una especialidad
    $res_esp = mysqli_query($conexion,
        "SELECT id_persona FROM persona_especialidades WHERE id_persona = $id_persona LIMIT 1"
    );
    if (!$res_esp || mysqli_num_rows($res_esp) === 0) {
        $faltantes[] = 'Especialidades';
    }

    if (empty($faltantes)) return; // Perfil completo, no notificar

    // Construir mensaje según cuántos campos faltan
    $cant = count($faltantes);
    if ($cant === 1) {
        $detalle = "Falta completar: " . $faltantes[0] . ".";
    } elseif ($cant <= 3) {
        $detalle = "Faltan completar: " . implode(', ', $faltantes) . ".";
    } else {
        $primeros = array_slice($faltantes, 0, 3);
        $resto    = $cant - 3;
        $detalle  = "Faltan completar: " . implode(', ', $primeros) . " y $resto campo(s) más.";
    }

    insertar_notificacion(
        $conexion,
        $id_usuario,
        'sistema',
        'Completá tu perfil para mejorar tus chances',
        $detalle . ' Un perfil completo tiene más visibilidad ante las empresas.',
        'perfil-trabajador.php'
    );
}

// ============================================================
// 3. CHEQUEAR NUEVAS OFERTAS COMPATIBLES (por especialidad)
// ============================================================
function chequear_ofertas_compatibles($conexion, $id_usuario, $id_persona) {
    $id_persona = intval($id_persona);
    $id_usuario = intval($id_usuario);

    // Traer las especialidades del trabajador
    $res_esp = mysqli_query($conexion,
        "SELECT id_especialidad FROM persona_especialidades WHERE id_persona = $id_persona"
    );
    if (!$res_esp || mysqli_num_rows($res_esp) === 0) return;

    $especialidades = [];
    while ($e = mysqli_fetch_assoc($res_esp)) {
        $especialidades[] = intval($e['id_especialidad']);
    }
    $ids_esp = implode(',', $especialidades);

    // Buscar ofertas activas compatibles publicadas en los últimos 7 días
    // que el trabajador NO haya visto notificadas aún
    $res_ofertas = mysqli_query($conexion,
        "SELECT ol.id_oferta, ol.titulo, e.nombre_especialidad
         FROM ofertas_laborales ol
         INNER JOIN especialidades e ON ol.id_especialidad = e.id_especialidad
         WHERE ol.id_especialidad IN ($ids_esp)
           AND ol.estado = 'Activa'
           AND ol.fecha_publicacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
           AND ol.id_oferta NOT IN (
               SELECT CAST(SUBSTRING_INDEX(url_accion, '=', -1) AS UNSIGNED)
               FROM notificaciones
               WHERE id_usuario = $id_usuario
                 AND tipo = 'oferta'
                 AND url_accion IS NOT NULL
           )
         ORDER BY ol.fecha_publicacion DESC
         LIMIT 5"
    );
    if (!$res_ofertas) return;

    while ($oferta = mysqli_fetch_assoc($res_ofertas)) {
        insertar_notificacion(
            $conexion,
            $id_usuario,
            'oferta',
            'Nueva oferta compatible: ' . $oferta['titulo'],
            'Hay una nueva oferta de ' . $oferta['nombre_especialidad'] . ' que coincide con tu perfil.',
            'ver-oferta.php?id=' . $oferta['id_oferta']
        );
    }
}

// ============================================================
// 4. NOTIFICAR CAMBIO DE ESTADO EN POSTULACIÓN
//    Llamar desde postulantes.php cuando empresa cambia estado
// ============================================================
function notificar_estado_postulacion($conexion, $id_postulacion, $nuevo_estado) {
    $id_postulacion = intval($id_postulacion);

    // Solo notificar Aceptada y Rechazada
    if (!in_array($nuevo_estado, ['Aceptada', 'Rechazada'])) return;

    // Obtener datos de la postulación y oferta
    $res = mysqli_query($conexion,
        "SELECT p.id_persona, ol.titulo AS titulo_oferta,
                u.id_usuario
         FROM postulaciones p
         INNER JOIN ofertas_laborales ol ON p.id_oferta = ol.id_oferta
         INNER JOIN users u ON u.id_persona = p.id_persona AND u.tipo = 2
         WHERE p.id_postulacion = $id_postulacion
         LIMIT 1"
    );
    if (!$res || mysqli_num_rows($res) === 0) return;
    $datos = mysqli_fetch_assoc($res);

    $id_usuario    = intval($datos['id_usuario']);
    $titulo_oferta = $datos['titulo_oferta'];

    if ($nuevo_estado === 'Aceptada') {
        $titulo_noti  = '¡Tu postulación fue aceptada!';
        $mensaje_noti = "Felicitaciones, tu postulación para \"$titulo_oferta\" fue aceptada. La empresa se pondrá en contacto pronto.";
    } else {
        $titulo_noti  = 'Tu postulación no fue seleccionada';
        $mensaje_noti = "Tu postulación para \"$titulo_oferta\" no fue seleccionada esta vez. ¡Seguí intentando!";
    }

    insertar_notificacion(
        $conexion,
        $id_usuario,
        'postulacion',
        $titulo_noti,
        $mensaje_noti,
        'mis-postulaciones.php'
    );
}