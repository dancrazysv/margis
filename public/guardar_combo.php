<?php
// actions/guardar_combo.php - Procesar acto múltiple
require_once '../config/db.php';
require_once '../core/plantillas.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit;
}

$combo_id = (int)$_POST['combo_id'];
$total_plantillas = (int)$_POST['total_plantillas'];
$plantillas_data = $_POST['plantillas'] ?? [];
$anio_digital = ANIO_VIGENTE;
$usuario = $_SESSION['iniciales'] ?? 'SIS';
$num_tramite = $_POST['tramite_anio'] . '-' . $_POST['tramite_correlativo'];

// Datos comunes del acto
$lugar_boda = $_POST['lugar_boda'] ?? '';
$fecha_boda = $_POST['fecha_boda'] ?? '';
$cargo_funcionario = $_POST['cargo_funcionario'] ?? '';
$nombre_funcionario = $_POST['nombre_funcionario'] ?? '';
$referencia_legal = $_POST['referencia_legal'] ?? '';
$leyenda_tipo = $_POST['leyenda_tipo'] ?? 'soltera';
$apellidos_ext = $_POST['apellidos_ext'] ?? '';

// Función para obtener siguiente ID digital
function siguienteId($conn, $anio) {
    $stmt = $conn->prepare("SELECT MAX(CAST(NMargi1 AS UNSIGNED)) as u FROM margi WHERE LibroO = ?");
    $stmt->bind_param("s", $anio);
    $stmt->execute();
    return ($stmt->get_result()->fetch_assoc()['u'] ?? 0) + 1;
}

// Función auxiliar
function fechaALetras($fecha) {
    if (empty($fecha)) return "";
    $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
              5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
              9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $d = (int)date('d', strtotime($fecha));
    $m = $meses[(int)date('n', strtotime($fecha))];
    $a = date('Y', strtotime($fecha));
    return strtoupper("$d de $m de $a");
}

function fechaHoyEnLetras() {
    $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
              5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
              9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $d = (int)date('d');
    $m = $meses[(int)date('n')];
    $a = date('Y');
    return strtoupper("$d de $m de $a");
}

function armarFuncionario($cargo, $nombre) {
    if (empty($cargo) && empty($nombre)) return "";
    return "ante " . trim($cargo . " " . $nombre);
}

function armarLeyendaApellido($tipo, $nombre_el, $manual = "") {
    switch ($tipo) {
        case 'soltera': return "seguirá usando sus apellidos de soltera, ";
        case 'con_de': return "usará el apellido del cónyuge precedido de la partícula DE, ";
        case 'sin_de': return "usará el apellido del cónyuge sin la partícula DE, ";
        case 'exterior': return mb_strtoupper($manual, 'UTF-8') . ", ";
        default: return "";
    }
}

// Obtener las plantillas del combo con su cuerpo legal
$stmt = $conn->prepare("
    SELECT cp.*, pt.cuerpo_legal, pt.nombre_tramite, pt.requiere_conyuge, pt.requiere_leyenda
    FROM combo_plantillas cp
    JOIN plantillas_textos pt ON cp.plantilla_id = pt.id
    WHERE cp.combo_id = ?
    ORDER BY cp.orden
");
$stmt->bind_param("i", $combo_id);
$stmt->execute();
$plantillas_db = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$exitos = [];
$errores = [];

foreach ($plantillas_db as $index => $plantilla_db) {
    // Obtener los datos específicos de esta plantilla desde el POST
    $datos_plantilla = $plantillas_data[$index] ?? [];
    
    // Determinar lugar de la partida
    $lugar = $datos_plantilla['distrito'] ?? 'SAN SALVADOR';
    
    // Determinar partida
    $partida = $datos_plantilla['n_partida'] ?? '';
    
    // Determinar nombres según el tipo de plantilla
    $es_el = (strpos($plantilla_db['nombre_tramite'], 'ÉL') !== false);
    $es_ella = (strpos($plantilla_db['nombre_tramite'], 'ELLA') !== false);
    
    if ($es_el) {
        $nombre_el = $datos_plantilla['nombre'] ?? '';
        $nombre_ella = '';
        $sujeto = 'EL';
    } elseif ($es_ella) {
        $nombre_el = '';
        $nombre_ella = $datos_plantilla['nombre'] ?? '';
        $sujeto = 'ELLA';
    } else {
        $nombre_el = $datos_plantilla['nombre'] ?? '';
        $nombre_ella = '';
        $sujeto = 'EL';
    }
    
    // Nombre del cónyuge
    $nombre_conyuge = ($sujeto === 'EL') ? $nombre_ella : $nombre_el;
    
    // Leyenda de apellidos
    $leyenda = '';
    if ($es_ella && $plantilla_db['requiere_leyenda']) {
        $leyenda = armarLeyendaApellido($leyenda_tipo, $nombre_el, $apellidos_ext);
    }
    
    // Variables de reemplazo
    $reemplazos = [
        '{o_a}' => ($sujeto === 'EL') ? 'o' : 'a',
        '{nombre_el}' => $nombre_el,
        '{nombre_ella}' => $nombre_ella,
        '{nombre_conyuge}' => $nombre_conyuge,
        '{leyenda_apellido}' => $leyenda,
        '{fecha_hoy_letras}' => fechaHoyEnLetras(),
        '{lugar}' => $lugar,
        '{lugar_boda}' => $lugar_boda,
        '{fecha_boda_letras}' => fechaALetras($fecha_boda),
        '{datos_funcionario}' => armarFuncionario($cargo_funcionario, $nombre_funcionario),
        '{referencia_legal}' => $referencia_legal,
    ];
    
    // Variables adicionales del formulario
    if (isset($datos_plantilla['vars'])) {
        foreach ($datos_plantilla['vars'] as $key => $value) {
            $reemplazos['{' . $key . '}'] = $value;
        }
    }
    
    // Renderizar texto final
    $texto_final = str_replace(array_keys($reemplazos), array_values($reemplazos), $plantilla_db['cuerpo_legal']);
    
    // Limpieza
    $texto_final = str_replace(', .', '.', $texto_final);
    $texto_final = str_replace(' .', '.', $texto_final);
    $texto_final = preg_replace('/\s+/', ' ', $texto_final);
    $texto_final = trim($texto_final);
    
    // Obtener ID digital
    $id_digital = siguienteId($conn, $anio_digital);
    $concatenado = $anio_digital . "--" . $id_digital;
    
    // Insertar
    $sql = "INSERT INTO margi (
        LibroO, NMargi1, TxtMargi1, TipoP, lugar, AnioP, LibroP, NPartida, FolioO, TomoP,
        num_tramite, estado, usuario_creo, Iniciales1, libro_nmargi_concat, FechaC
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DIGITADO', ?, ?, ?, NOW())";
    
    $stmt_ins = $conn->prepare($sql);
    $stmt_ins->bind_param(
        "ssssssssssssss",
        $anio_digital, $id_digital, $texto_final, 
        $datos_plantilla['tipo_p'] ?? 'NACIMIENTO', $lugar,
        $datos_plantilla['anio_p'] ?? '', $datos_plantilla['libro_p'] ?? '',
        $partida, $datos_plantilla['folio'] ?? '', $datos_plantilla['tomo'] ?? '',
        $num_tramite, $usuario, $usuario, $concatenado
    );
    
    if ($stmt_ins->execute()) {
        $exitos[] = ['plantilla' => $plantilla_db['nombre_tramite'], 'id' => $anio_digital . '-' . $id_digital];
    } else {
        $errores[] = $plantilla_db['nombre_tramite'];
    }
}

if (count($errores) == 0) {
    header("Location: ../public/dashboard.php?msj=combo_exitoso&total=" . count($exitos));
} else {
    header("Location: ../public/combos.php?combo_id=$combo_id&error=parcial");
}
?>