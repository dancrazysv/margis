<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $tipo_asiento = limpiar($_POST['tipo_margi']);
    $cuerpo = limpiar($_POST['cuerpo']);

    // Insertar en la tabla que creamos al inicio
    $stmt = $conn->prepare("INSERT INTO plantillas_textos (nombre_tramite, cuerpo_legal, id_tipo_margi) VALUES (?, ?, ?)");
    
    // Aquí podrías buscar el ID real de la tabla tipo_marginacion si prefieres vincularlos
    // Por ahora usamos un ID genérico o el nombre del tipo
    $temp_id_tipo = 1; 

    $stmt->bind_param("ssi", $nombre, $cuerpo, $temp_id_tipo);

    if ($stmt->execute()) {
        header("Location: ../public/admin_plantillas.php?msg=success");
    } else {
        echo "Error al guardar plantilla: " . $conn->error;
    }
}
?>