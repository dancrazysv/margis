<?php
// actions/guardar_combo.php
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
$anio_digital = ANIO_VIGENTE;
$usuario = $_SESSION['iniciales'] ?? 'SIS';
$num_tramite = $_POST['tramite_anio'] . '-' . $_POST['tramite_correlativo'];

// Datos comunes
$tipo_p = limpiar($_POST['tipo_p']);
$lugar = limpiar($_POST['lugar']);
$anio_p = limpiar($_POST['anio_p']);
$libro_p = limpiar($_POST['libro_p']);
$folio_o = limpiar($_POST['folio_o']);

// Obtener combo y sus plantillas
$stmt = $conn->prepare("SELECT * FROM combos_marginaciones WHERE id = ?");
$stmt->bind_param("i", $combo_id);
$stmt->execute();
$combo = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("
    SELECT cp.*, pt.nombre_tramite, pt.cuerpo_legal, pt.requiere_conyuge, pt.requiere_leyenda
    FROM combo_plantillas cp
    JOIN plantillas_textos pt ON cp.plantilla_id = pt.id
    WHERE cp.combo_id = ?
    ORDER BY cp.orden
");
$stmt->bind_param("i", $combo_id);
$stmt->execute();
$plantillas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Función para obtener siguiente ID digital
function siguienteId($conn, $anio) {
    $stmt = $conn->prepare("SELECT MAX(CAST(NMargi1 AS UNSIGNED)) as u FROM margi WHERE LibroO = ?");
    $stmt->bind_param("s", $anio);
    $stmt->execute();
    return ($stmt->get_result()->fetch_assoc()['u'] ?? 0) + 1;
}

// Función para guardar una marginación
function guardarMarginacion($conn, $datos, $anio_digital, $usuario, $tipo_p, $lugar, $anio_p, $libro_p, $folio_o, $num_tramite) {
    $id_digital = siguienteId($conn, $anio_digital);
    $concatenado = $anio_digital . "--" . $id_digital;
    
    $sql = "INSERT INTO margi (
        LibroO, NMargi1, TxtMargi1, TipoP, lugar, AnioP, LibroP, NPartida, FolioO, 
        num_tramite, estado, usuario_creo, Iniciales1, libro_nmargi_concat, FechaC
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DIGITADO', ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssss", 
        $anio_digital, $id_digital, $datos['texto'], $tipo_p, $lugar, $anio_p, 
        $libro_p, $datos['partida'], $folio_o, $num_tramite, $usuario, $usuario, $concatenado
    );
    
    return $stmt->execute() ? $id_digital : false;
}

// Función auxiliar para fechas
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

function armarLeyendaApellido($tipo, $nombre_el) {
    switch ($tipo) {
        case 'soltera': return "seguirá usando sus apellidos de soltera, ";
        case 'con_de': return "usará el apellido del cónyuge precedido de la partícula DE, ";
        case 'sin_de': return "usará el apellido del cónyuge sin la partícula DE, ";
        default: return "";
    }
}

// Procesar cada plantilla
$exitos = [];
$errores = [];

foreach ($plantillas as $plantilla) {
    $texto_base = $plantilla['cuerpo_legal'];
    $nombre_tramite = strtoupper($plantilla['nombre_tramite']);
    
    // Determinar datos según el tipo de plantilla
    if (strpos($nombre_tramite, 'ÉL') !== false || strpos($nombre_tramite, 'EL') !== false) {
        $partida = $_POST['n_partida_el'] ?? '';
        $nombre_inscrito = $_POST['nombre_el'] ?? '';
        $nombre_conyuge = $_POST['nombre_ella'] ?? '';
        $sujeto = 'EL';
        $leyenda = '';
    } elseif (strpos($nombre_tramite, 'ELLA') !== false) {
        $partida = $_POST['n_partida_ella'] ?? '';
        $nombre_inscrito = $_POST['nombre_ella'] ?? '';
        $nombre_conyuge = $_POST['nombre_el'] ?? '';
        $sujeto = 'ELLA';
        $leyenda_tipo = $_POST['leyenda_tipo'] ?? 'soltera';
        $leyenda = armarLeyendaApellido($leyenda_tipo, $_POST['nombre_el'] ?? '');
    } elseif (strpos($nombre_tramite, 'DEFUNCIÓN') !== false) {
        $partida = $_POST['partida_defuncion'] ?? '';
        $nombre_inscrito = $_POST['nombre_fallecido'] ?? '';
        $nombre_conyuge = '';
        $sujeto = 'EL';
        $leyenda = '';
    } else {
        $partida = $_POST['n_partida_nacimiento'] ?? '';
        $nombre_inscrito = $_POST['nombre_fallecido'] ?? $_POST['nombre_el'] ?? '';
        $nombre_conyuge = '';
        $sujeto = 'EL';
        $leyenda = '';
    }
    
    // Construir reemplazos
    $reemplazos = [
        '{o_a}' => ($sujeto === 'EL') ? 'o' : 'a',
        '{nombre_el}' => ($sujeto === 'EL') ? $nombre_inscrito : $nombre_conyuge,
        '{nombre_ella}' => ($sujeto === 'ELLA') ? $nombre_inscrito : $nombre_conyuge,
        '{nombre_conyuge}' => $nombre_conyuge,
        '{leyenda_apellido}' => $leyenda,
        '{fecha_hoy_letras}' => fechaHoyEnLetras(),
        '{lugar}' => $lugar,
        '{lugar_boda}' => $_POST['lugar_boda'] ?? '',
        '{fecha_boda_letras}' => fechaALetras($_POST['fecha_boda'] ?? ''),
        '{datos_funcionario}' => armarFuncionario($_POST['cargo_funcionario'] ?? '', $_POST['nombre_funcionario'] ?? ''),
        '{referencia_legal}' => $_POST['referencia_legal'] ?? '',
        '{numero_oficio}' => $_POST['numero_oficio'] ?? '',
        '{fecha_sentencia_letras}' => fechaALetras($_POST['fecha_sentencia'] ?? ''),
        '{juzgado}' => $_POST['juzgado'] ?? '',
        '{partida_defuncion}' => $_POST['partida_defuncion'] ?? '',
        '{fecha_defuncion_letras}' => fechaALetras($_POST['fecha_defuncion'] ?? ''),
    ];
    
    $texto_final = str_replace(array_keys($reemplazos), array_values($reemplazos), $texto_base);
    
    // Guardar
    $id_generado = guardarMarginacion($conn, [
        'texto' => $texto_final,
        'partida' => $partida
    ], $anio_digital, $usuario, $tipo_p, $lugar, $anio_p, $libro_p, $folio_o, $num_tramite);
    
    if ($id_generado) {
        $exitos[] = ['plantilla' => $plantilla['nombre_tramite'], 'id' => $anio_digital . '-' . $id_generado];
    } else {
        $errores[] = $plantilla['nombre_tramite'];
    }
}

// Redireccionar con resultado
if (count($errores) == 0) {
    $_SESSION['ultimo_combo'] = [
        'exitos' => $exitos,
        'total' => count($plantillas)
    ];
    header("Location: ../public/dashboard.php?msj=combo_exitoso&total=" . count($exitos));
} else {
    header("Location: ../public/combos.php?combo_id=$combo_id&error=parcial&errores=" . urlencode(implode(',', $errores)));
}
?>