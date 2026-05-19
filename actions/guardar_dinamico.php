<?php
// actions/guardar_dinamico.php - VERSIÓN DEFINITIVA
header('Content-Type: application/json');

// Desactivar errores en pantalla
error_reporting(0);
ini_set('display_errors', 0);

// Limpiar cualquier salida previa
if (ob_get_level()) ob_end_clean();

require_once '../config/db.php';
require_once '../core/plantillas.php';

// Función para enviar respuesta JSON
function responder($status, $mensaje, $extra = []) {
    $respuesta = array_merge(['status' => $status, 'message' => $mensaje], $extra);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    responder('error', 'Sesión no iniciada');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder('error', 'Método no permitido');
}

// =====================================================
// RECOLECTAR DATOS
// =====================================================
$confirmar_duplicado = isset($_POST['confirmar_duplicado']) && $_POST['confirmar_duplicado'] == 1;

// Datos básicos
$libro_o = trim($_POST['anio_digital'] ?? date('Y'));
$nmargi1 = trim($_POST['num_digital'] ?? '0');
$n_partida = trim($_POST['n_partida_1'] ?? '');
$anio_p = trim($_POST['anio_p_1'] ?? '');
$libro_p = trim($_POST['libro_p_1'] ?? '');
$folio_o = trim($_POST['folio_p_1'] ?? '');
$tomo_p = trim($_POST['tomo_p_1'] ?? '');
$tipo_p_nombre = trim($_POST['tipo_p_1'] ?? '');
$lugar = trim($_POST['lugar'] ?? '');
$nombre_el = trim($_POST['nombre_el'] ?? '');
$nombre_ella = trim($_POST['nombre_ella'] ?? '');
$plantilla_id = (int)($_POST['plantilla_id'] ?? 0);

// Número de trámite
$num_tramite = trim($_POST['num_tramite'] ?? '');
if (empty($num_tramite) && isset($_POST['tramite_anio']) && isset($_POST['tramite_correlativo'])) {
    $num_tramite = trim($_POST['tramite_anio']) . '-' . trim($_POST['tramite_correlativo']);
}

// Fecha evento
$fecha_evento = '';
if (!empty($_POST['vars']['fecha_boda'])) {
    $fecha_evento = $_POST['vars']['fecha_boda'];
} elseif (!empty($_POST['fecha_boda'])) {
    $fecha_evento = $_POST['fecha_boda'];
}

// =====================================================
// OBTENER INICIALES DEL TIPO DE PARTIDA
// =====================================================
$iniciales_partida = '';
if (!empty($tipo_p_nombre)) {
    $stmt = $conn->prepare("SELECT iniciales_partida FROM tipo_partida WHERE nombre_partida = ?");
    if ($stmt) {
        $stmt->bind_param("s", $tipo_p_nombre);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) $iniciales_partida = $row['iniciales_partida'];
        $stmt->close();
    }
}
if (empty($iniciales_partida)) {
    $iniciales_partida = substr(preg_replace('/[^A-Za-z]/', '', $tipo_p_nombre), 0, 3);
}

// =====================================================
// VERIFICAR DUPLICADOS (si aplica)
// =====================================================
if (!$confirmar_duplicado && !empty($fecha_evento) && !empty($n_partida)) {
    $check = $conn->prepare("SELECT id FROM margi WHERE NPartida = ? AND LibroO = ? AND fechaevento = ? LIMIT 5");
    if ($check) {
        $check->bind_param("sss", $n_partida, $libro_o, $fecha_evento);
        $check->execute();
        $duplicados = $check->get_result()->fetch_all(MYSQLI_ASSOC);
        $check->close();
        
        if (count($duplicados) > 0) {
            responder('duplicado', 'Se encontraron registros duplicados', [
                'duplicados' => $duplicados,
                'datos_pendientes' => [
                    'n_partida_1' => $n_partida,
                    'anio_digital' => $libro_o,
                    'fecha_evento' => $fecha_evento,
                    'iniciales_partida' => $iniciales_partida
                ]
            ]);
        }
    }
}

// =====================================================
// RENDERIZAR TEXTO DE LA PLANTILLA
// =====================================================
$texto_final = '';
if ($plantilla_id > 0) {
    $plantilla = PlantillaManager::getPlantillaCompleta($conn, $plantilla_id);
    if ($plantilla) {
        $texto_final = $plantilla['cuerpo_legal'];
        
        // Reemplazar variables básicas
        $texto_final = str_replace('{nombre_el}', $nombre_el, $texto_final);
        $texto_final = str_replace('{nombre_ella}', $nombre_ella, $texto_final);
        $texto_final = str_replace('{lugar}', $lugar, $texto_final);
        
        // Fecha actual
        $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $fecha_hoy = date('j') . ' de ' . $meses[date('n')-1] . ' de ' . date('Y');
        $texto_final = str_replace('{fecha_hoy_letras}', $fecha_hoy, $texto_final);
        
        // Limpiar variables no reemplazadas
        $texto_final = preg_replace('/\{[^}]+\}/', '', $texto_final);
        $texto_final = preg_replace('/\s+/', ' ', $texto_final);
        $texto_final = trim($texto_final);
    }
}

// =====================================================
// PREPARAR DATOS PARA INSERTAR
// =====================================================
$fecha_actual = date('Y-m-d');
$hora_actual = date('H:i:s');
$usuario = $_SESSION['iniciales'] ?? 'SIS';
$concatenado = $libro_o . '--' . $nmargi1;
$anio_marginacion = (int)$libro_o;
$busquedalf = $nombre_el . ' ' . $nombre_ella . ' ' . $n_partida;
$tipo_marginacion = 'MODIFICACION';
$estado = 'DIGITADO';

// Verificar ID único
$check = $conn->prepare("SELECT id FROM margi WHERE libro_nmargi_concat = ?");
if ($check) {
    $check->bind_param("s", $concatenado);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        responder('error', 'El ID digital ya existe: ' . $concatenado);
    }
    $check->close();
}

// Obtener correlativo
$correlativo_anual = 1;
$stmt_corr = $conn->prepare("SELECT MAX(correlativo_anual) as max_corr FROM margi WHERE anio_marginacion = ?");
if ($stmt_corr) {
    $stmt_corr->bind_param("i", $anio_marginacion);
    $stmt_corr->execute();
    $row = $stmt_corr->get_result()->fetch_assoc();
    $correlativo_anual = ($row['max_corr'] ?? 0) + 1;
    $stmt_corr->close();
}

// =====================================================
// INSERTAR
// =====================================================
$sql = "INSERT INTO margi SET
    anio_marginacion = ?,
    correlativo_anual = ?,
    NMargi1 = ?,
    TxtMargi1 = ?,
    AnioP = ?,
    LibroP = ?,
    NPartida = ?,
    FolioO = ?,
    TomoP = ?,
    TipoP = ?,
    lugar = ?,
    num_tramite = ?,
    busquedalf = ?,
    FechaC = ?,
    HoraC = ?,
    Iniciales1 = ?,
    TipoMargi = ?,
    LibroO = ?,
    estado = ?,
    usuario_creo = ?,
    libro_nmargi_concat = ?,
    fechaevento = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('error', 'Error preparando consulta: ' . $conn->error);
}

$stmt->bind_param(
    "iissssssssssssssssssss",
    $anio_marginacion,
    $correlativo_anual,
    $nmargi1,
    $texto_final,
    $anio_p,
    $libro_p,
    $n_partida,
    $folio_o,
    $tomo_p,
    $iniciales_partida,
    $lugar,
    $num_tramite,
    $busquedalf,
    $fecha_actual,
    $hora_actual,
    $usuario,
    $tipo_marginacion,
    $libro_o,
    $estado,
    $usuario,
    $concatenado,
    $fecha_evento
);

if ($stmt->execute()) {
    responder('success', 'Marginación guardada exitosamente', ['id' => $concatenado]);
} else {
    responder('error', 'Error al guardar: ' . $stmt->error);
}
?>