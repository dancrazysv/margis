<?php
// actions/guardar_acto_multiple.php
require_once '../config/db.php';
require_once '../core/plantillas.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit;
}

$grupo = $_POST['grupo'] ?? '';
$anio_digital = ANIO_VIGENTE;
$usuario = $_SESSION['iniciales'] ?? 'SIS';
$num_tramite = $_POST['tramite_anio'] . '-' . $_POST['tramite_correlativo'];

// Datos comunes de foliación
$tipo_p = limpiar($_POST['tipo_p']);
$lugar = limpiar($_POST['lugar']);
$anio_p = limpiar($_POST['anio_p']);
$libro_p = limpiar($_POST['libro_p']);
$folio_o = limpiar($_POST['folio_o']);

// Función para obtener siguiente ID digital
function siguienteId($conn, $anio) {
    $stmt = $conn->prepare("SELECT MAX(CAST(NMargi1 AS UNSIGNED)) as u FROM margi WHERE LibroO = ?");
    $stmt->bind_param("s", $anio);
    $stmt->execute();
    return ($stmt->get_result()->fetch_assoc()['u'] ?? 0) + 1;
}

// Función para guardar una marginación
function guardarMarginacion($conn, $datos) {
    global $anio_digital, $usuario, $tipo_p, $lugar, $anio_p, $libro_p, $folio_o, $num_tramite;
    
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
    
    return $stmt->execute();
}

// Obtener plantillas del grupo
$stmt = $conn->prepare("SELECT id, nombre_tramite, cuerpo_legal, requiere_conyuge, requiere_leyenda 
                        FROM plantillas_textos 
                        WHERE grupo_multiple = ? AND activo = 1");
$stmt->bind_param("s", $grupo);
$stmt->execute();
$plantillas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$errores = [];
$exitos = 0;

foreach ($plantillas as $plantilla) {
    $datos_marginacion = [];
    $texto_base = $plantilla['cuerpo_legal'];
    
    // Determinar si es para ÉL o para ELLA según el nombre de la plantilla
    $nombre_tramite = strtoupper($plantilla['nombre_tramite']);
    
    if (strpos($nombre_tramite, 'EL') !== false) {
        // Marginación para ÉL
        $partida = $_POST['n_partida_el'] ?? '';
        $nombre_inscrito = $_POST['nombre_el'] ?? '';
        $nombre_conyuge = $_POST['nombre_ella'] ?? '';
        $sujeto = 'EL';
        $leyenda = '';
    } elseif (strpos($nombre_tramite, 'ELLA') !== false) {
        // Marginación para ELLA
        $partida = $_POST['n_partida_ella'] ?? '';
        $nombre_inscrito = $_POST['nombre_ella'] ?? '';
        $nombre_conyuge = $_POST['nombre_el'] ?? '';
        $sujeto = 'ELLA';
        
        // Leyenda de apellidos
        $leyenda_tipo = $_POST['leyenda_tipo'] ?? 'soltera';
        $leyenda = armarLeyendaApellido($leyenda_tipo, $_POST['nombre_el'] ?? '');
    } else {
        // Otras marginaciones
        $partida = $_POST['n_partida'] ?? '';
        $nombre_inscrito = $_POST['nombre_inscrito'] ?? '';
        $nombre_conyuge = '';
        $sujeto = 'EL';
        $leyenda = '';
    }
    
    // Construcción del texto
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
    ];
    
    $texto_final = str_replace(array_keys($reemplazos), array_values($reemplazos), $texto_base);
    
    // Guardar
    if (guardarMarginacion($conn, ['texto' => $texto_final, 'partida' => $partida])) {
        $exitos++;
    } else {
        $errores[] = $plantilla['nombre_tramite'];
    }
}

if ($exitos > 0) {
    header("Location: ../public/dashboard.php?msj=multiple&exitos=$exitos&total=" . count($plantillas));
} else {
    header("Location: ../public/acto_multiple.php?grupo=$grupo&error=fallo");
}

function armarLeyendaApellido($tipo, $nombre_el) {
    switch ($tipo) {
        case 'soltera': return "seguirá usando sus apellidos de soltera, ";
        case 'con_de': return "usará el apellido del cónyuge precedido de la partícula DE, ";
        case 'sin_de': return "usará el apellido del cónyuge sin la partícula DE, ";
        default: return "";
    }
}

function armarFuncionario($cargo, $nombre) {
    if (empty($cargo) && empty($nombre)) return "";
    return "ante " . trim($cargo . " " . $nombre);
}

function fechaHoyEnLetras() {
    $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
              5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
              9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $d = (int)date('d');
    $m = $meses[(int)date('n')];
    $a = date('Y');
    return "$d de $m de $a";
}

function fechaALetras($fecha) {
    if (empty($fecha)) return "";
    $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
              5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
              9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $d = (int)date('d', strtotime($fecha));
    $m = $meses[(int)date('n', strtotime($fecha))];
    $a = date('Y', strtotime($fecha));
    return "$d de $m de $a";
}
?>