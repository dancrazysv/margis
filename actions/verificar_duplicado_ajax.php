<?php
require_once '../config/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Recoger datos del formulario
$n_partida_1 = $_POST['n_partida_1'] ?? '';
$anio_digital = $_POST['anio_digital'] ?? '';
$tipo_asiento = $_POST['tipo_asiento'] ?? 'NACIMIENTO';
$iniciales_partida = $_POST['iniciales_partida'] ?? '';
$fecha_evento = $_POST['fecha_evento'] ?? '';

// Función para verificar duplicados
function verificarDuplicados($conn, $n_partida, $anio_digital, $iniciales_partida) {
    $sql = "SELECT id, libro_nmargi_concat, TxtMargi1, FechaC, estado 
            FROM margi 
            WHERE NPartida = ? 
            AND LibroO = ?
            AND iniciales_partida = ? 
            AND estado IN ('DIGITADO', 'EN_REVISION', 'OBSERVADO')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $n_partida, $anio_digital, $iniciales_partida);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $duplicados = [];
    while ($row = $result->fetch_assoc()) {
        $duplicados[] = $row;
    }
    
    return $duplicados;
}

$duplicados = verificarDuplicados($conn, $n_partida_1, $anio_digital, $iniciales_partida);

if (count($duplicados) > 0) {
    echo json_encode([
        'status' => 'duplicado',
        'duplicados' => $duplicados,
        'datos_pendientes' => [
            'n_partida_1' => $n_partida_1,
            'anio_digital' => $anio_digital,
            'fecha_evento' => $fecha_evento,
            'iniciales_partida' => $iniciales_partida
        ]
    ]);
} else {
    echo json_encode(['status' => 'success', 'message' => 'No hay duplicados']);
}
?>