<?php
require_once '../config/db.php';
require_once '../core/Plantillas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anio_actual = $_POST['anio']; // Ej: 2026
    $plantilla_id = $_POST['plantilla_id'];
    $vars = $_POST['vars']; // Array con los datos del formulario dynamic
    
    // 1. Obtener la plantilla original
    $stmt = $conn->prepare("SELECT cuerpo_legal, nombre_tramite FROM plantillas_textos WHERE id = ?");
    $stmt->bind_param("i", $plantilla_id);
    $stmt->execute();
    $plantilla = $stmt->get_result()->fetch_assoc();

    // 2. Procesar el texto final reemplazando las {variables}
    $texto_final = $plantilla['cuerpo_legal'];
    foreach ($vars as $key => $value) {
        $texto_final = str_replace("{" . $key . "}", strtoupper($value), $texto_final);
    }

    // 3. Generar el correlativo anual (2026-1, 2, 3...)
    $stmt = $conn->prepare("SELECT MAX(CAST(NMargi1 AS UNSIGNED)) as ultimo FROM margi WHERE LibroO = ?");
    $stmt->bind_param("s", $anio_actual);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $siguiente = ($res['ultimo'] ?? 0) + 1;

    // 4. Insertar en la tabla 'margi'
    $estado_inicial = 'ABIERTO'; // Usuario puede editar
    $tipo_p = $_POST['tipo_partida']; // Nacimiento, Defunción, etc.
    
    $sql = "INSERT INTO margi (NMargi1, TxtMargi1, AnioP, LibroP, NPartida, LibroO, TipoMargi, TipoP, estado, FechaC, HoraC) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())";
    
    $insert = $conn->prepare($sql);
    $insert->bind_param("sssssssss", 
        $siguiente, 
        $texto_final, 
        $_POST['anio_p'], 
        $_POST['libro_p'], 
        $_POST['partida'], 
        $anio_actual, 
        $plantilla['nombre_tramite'],
        $tipo_p,
        $estado_inicial
    );

    if ($insert->execute()) {
        header("Location: ../public/dashboard.php?msg=success&id=" . $anio_actual . "-" . $siguiente);
    } else {
        echo "Error al guardar: " . $conn->error;
    }
}
?>