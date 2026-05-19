<?php
// actions/procesar_calidad.php
ob_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificación de seguridad
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_area'], ['CONTROL_CALIDAD', 'SUPERVISOR', 'ADMINISTRADOR'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada o sin permisos']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $accion = $_POST['accion'] ?? '';
    $obs = isset($_POST['observacion']) ? limpiar($_POST['observacion']) : '';
    $revisor = $_SESSION['iniciales'] ?? 'QC';
    $fecha_actual = date('d/m/Y H:i A');

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID no válido']);
        exit;
    }

    if ($accion === 'APROBAR') {
        $nota_aprobado = "--- APROBADO ($fecha_actual) por $revisor ---\n\n";
        
        // REVISIÓN: Asegúrate de que estas columnas existan en tu tabla 'margi'
        $sql = "UPDATE margi SET 
                estado = 'CERRADO', 
                usuario_reviso = ?, 
                fecha_revision = NOW(),
                observaciones_qc = CONCAT(?, IFNULL(observaciones_qc, '')) 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // Depuración: Esto te dirá si falló por una columna mal escrita
            echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ssi", $revisor, $nota_aprobado, $id);

    } elseif ($accion === 'DEVOLVER') {
        $separador = "------------------------------------------\n";
        $nueva_nota = "CORRECCIÓN ($fecha_actual) - REVISOR: $revisor\n";
        $nueva_nota .= "OBSERVACIÓN: " . $obs . "\n";
        $nueva_nota .= $separador;
        
        $sql = "UPDATE margi SET 
                estado = 'OBSERVADO', 
                observaciones_qc = CONCAT(?, IFNULL(observaciones_qc, '')) 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("si", $nueva_nota, $id);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        exit;
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            // Si entra aquí, el ID existe pero el estado ya era el mismo o no se cambió nada
            echo json_encode(['status' => 'error', 'message' => 'No se detectaron cambios en el registro.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar: ' . $stmt->error]);
    }
}
ob_end_flush();