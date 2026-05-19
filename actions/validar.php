<?php
require_once '../config/db.php';

$id = $_POST['id_registro'];
$accion = $_POST['accion']; // 'APROBAR' o 'RECHAZAR'
$observaciones = $_POST['observaciones'] ?? '';

if ($accion === 'APROBAR') {
    $nuevo_estado = 'CERRADO';
    $revestado = 'VALIDADO';
} else {
    $nuevo_estado = 'DEVUELTO'; // Vuelve al usuario para editar
    $revestado = 'OBSERVADO';
}

$stmt = $conn->prepare("UPDATE margi SET estado = ?, revestado = ?, observaciones_qc = ? WHERE id = ?");
$stmt->bind_param("sssi", $nuevo_estado, $revestado, $observaciones, $id);

if ($stmt->execute()) {
    header("Location: ../public/dashboard.php?msg=updated");
}
?>