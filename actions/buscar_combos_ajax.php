<?php
// actions/buscar_combos_ajax.php
ob_start(); // Capturar cualquier salida accidental
require_once '../config/db.php';
session_start();

// Limpiar buffers y establecer cabecera JSON
ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SESSION['user_area'] !== 'MARGINADOR' && $_SESSION['user_area'] !== 'ADMINISTRADOR') {
    echo json_encode(['success' => false, 'message' => 'Permisos insuficientes']);
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $sql = "SELECT id, nombre_combo, descripcion, orden FROM combos_marginaciones WHERE activo = 1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND (nombre_combo LIKE ? OR descripcion LIKE ?)";
        $search_param = "%$search%";
        $params = [$search_param, $search_param];
        $types = "ss";
    }

    $sql .= " ORDER BY orden";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);

    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $combos = [];
    while ($row = $result->fetch_assoc()) {
        $combos[] = [
            'id' => $row['id'],
            'nombre_combo' => $row['nombre_combo'],
            'descripcion' => $row['descripcion'],
            'orden' => $row['orden']
        ];
    }

    echo json_encode(['success' => true, 'data' => $combos, 'total' => count($combos)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>