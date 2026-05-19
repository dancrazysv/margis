<?php
require_once '../config/db.php';
require_once '../core/Plantillas.php';

// Esta función se llama desde un bucle si el usuario marcó "Aplicar a ambos cónyuges"
function crearEntradaMarginacion($conn, $plantilla_id, $datos_partida, $vars_texto, $anio_digital) {
    // 1. Obtener correlativo anual
    $stmt = $conn->prepare("SELECT MAX(CAST(NMargi1 AS UNSIGNED)) as ultimo FROM margi WHERE LibroO = ?");
    $stmt->bind_param("s", $anio_digital);
    $stmt->execute();
    $siguiente = ($stmt->get_result()->fetch_assoc()['ultimo'] ?? 0) + 1;

    // 2. Procesar plantilla
    $p = $conn->query("SELECT cuerpo_legal, nombre_tramite FROM plantillas_textos WHERE id = $plantilla_id")->fetch_assoc();
    $texto_final = PlantillaManager::renderizarTexto($p['cuerpo_legal'], $vars_texto);

    // 3. Insertar
    $sql = "INSERT INTO margi (NMargi1, TxtMargi1, AnioP, LibroP, NPartida, LibroO, TipoMargi, estado, FechaC, HoraC) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'ABIERTO', CURDATE(), CURTIME())";
    $ins = $conn->prepare($sql);
    $ins->bind_param("sssssss", $siguiente, $texto_final, $datos_partida['anio'], $datos_partida['libro'], $datos_partida['partida'], $anio_digital, $p['nombre_tramite']);
    $ins->execute();
    
    return $anio_digital . "-" . $siguiente;
}
?>