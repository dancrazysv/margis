<?php
// admin/guardar_combo.php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['user_area'] !== 'ADMINISTRADOR') {
    header("Location: ../public/dashboard.php?error=sin_permiso");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$nombre_combo = limpiar($_POST['nombre_combo']);
$descripcion = limpiar($_POST['descripcion'] ?? '');
$activo = (int)($_POST['activo'] ?? 1);
$orden = (int)($_POST['orden'] ?? 0);

if ($id > 0) {
    // Actualizar combo existente
    $sql = "UPDATE combos_marginaciones SET nombre_combo = ?, descripcion = ?, activo = ?, orden = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiii", $nombre_combo, $descripcion, $activo, $orden, $id);
} else {
    // Crear nuevo combo
    $sql = "INSERT INTO combos_marginaciones (nombre_combo, descripcion, activo, orden) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $nombre_combo, $descripcion, $activo, $orden);
}

if ($stmt->execute()) {
    header("Location: combos.php?accion=listar&msg=guardado");
} else {
    die("Error: " . $conn->error);
}
?>