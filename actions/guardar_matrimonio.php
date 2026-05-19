<?php
ob_start();
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit;
}

/**
 * Genera la fecha actual en formato legal salvadoreño
 */
function fechaHoyEnLetras() {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    $d = (int)date('d');
    $m = $meses[(int)date('n')];
    $a = date('Y'); 
    return "$d de $m de dos mil veintiséis";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Datos de Foliación Digital
    $libro_o  = $_POST['anio_digital'] ?? date('Y');
    $nmargi1  = $_POST['num_digital'] ?? '0';

    // 2. Datos de Foliación Física
    $tipo_p    = limpiar($_POST['tipo_p_1']);
    $distrito  = limpiar($_POST['distrito_p_1']);
    $anio_p    = limpiar($_POST['anio_p_1']);
    $libro_p   = limpiar($_POST['libro_p_1']); 
    $n_partida = limpiar($_POST['n_partida_1']);
    $folio_o   = limpiar($_POST['folio_p_1']);
    $tomo_p    = limpiar($_POST['tomo_p_1'] ?? '');
    
    $sujeto_mat  = $_POST['sujeto_mat'] ?? 'EL';
    $plantilla_id = (int)$_POST['plantilla_id'];
    
    $variables = $_POST['vars'] ?? [];

    // 3. PROCESAMIENTO DEL FUNCIONARIO (Lógica del "Ante")
    if (!empty($variables['datos_funcionario'])) {
        $nombre_f = mb_convert_case(trim($variables['datos_funcionario']), MB_CASE_UPPER, "UTF-8");
        // Si el check estaba marcado, concatenamos "ante "
        if (isset($_POST['es_ante_notario'])) {
            $variables['datos_funcionario'] = "ante " . $nombre_f;
        } else {
            $variables['datos_funcionario'] = $nombre_f;
        }
    }

    // 4. LÓGICA DE PREVENCIÓN DE DUPLICADOS (Basado en fecha evento si existe)
    $fecha_evento = "";
    foreach($variables as $key => $val) {
        if(strpos($key, 'fecha') !== false && !empty($val)) { $fecha_evento = $val; break; }
    }

    $sql_check = "SELECT id FROM margi WHERE TipoP = ? AND AnioP = ? AND NPartida = ? AND FolioO = ? AND fechaevento = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("sssss", $tipo_p, $anio_p, $n_partida, $folio_o, $fecha_evento);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: ../public/nueva.php?p=$plantilla_id&error=duplicado");
        exit;
    }

    // 5. RENDERIZACIÓN DEL TEXTO
    $stmt_p = $conn->prepare("SELECT cuerpo_legal FROM plantillas_textos WHERE id = ?");
    $stmt_p->bind_param("i", $plantilla_id);
    $stmt_p->execute();
    $cuerpo_base = $stmt_p->get_result()->fetch_assoc()['cuerpo_legal'];

    $nombre_el   = mb_convert_case(trim($_POST['nombre_el'] ?? ''), MB_CASE_UPPER, "UTF-8");
    $nombre_ella = mb_convert_case(trim($_POST['nombre_ella'] ?? ''), MB_CASE_UPPER, "UTF-8");
    
    $reemplazos = [
        '{nombre_el}'   => $nombre_el,
        '{nombre_ella}' => $nombre_ella,
        '{fecha_today}' => mb_strtoupper(fechaHoyEnLetras(), 'UTF-8'),
        '{fecha_hoy_letras}' => mb_strtoupper(fechaHoyEnLetras(), 'UTF-8')
    ];

    if ($sujeto_mat === 'EL') {
        $reemplazos['{o_a}'] = 'o';
        $reemplazos['{nombre_conyuge}'] = $nombre_ella;
        $reemplazos['{leyenda_apellido}'] = "";
    } else {
        $reemplazos['{o_a}'] = 'a';
        $reemplazos['{nombre_conyuge}'] = $nombre_el;
        $reemplazos['{leyenda_apellido}'] = armarLeyendaApellido($_POST['leyenda_tipo'], $nombre_el, $_POST['apellidos_ext'] ?? '');
    }

    // Reemplazar resto de variables
    foreach ($variables as $key => $value) {
        $reemplazos['{' . $key . '}'] = $value;
    }

    $texto_final = str_replace(array_keys($reemplazos), array_values($reemplazos), $cuerpo_base);
    
    // Limpieza de puntuación y espacios
    $texto_final = str_replace(', .', '.', $texto_final);
    $texto_final = preg_replace('/\s+/', ' ', $texto_final);
    $texto_final = trim($texto_final);

    // 6. INSERCIÓN FINAL
    $usuario = $_SESSION['iniciales'];
    $concatenado = $libro_o . "--" . $nmargi1;

    $sql_ins = "INSERT INTO margi (
        LibroO, NMargi1, TxtMargi1, TipoP, lugar, AnioP, LibroP, NPartida, FolioO, TomoP, 
        fechaevento, estado, usuario_creo, Iniciales1, libro_nmargi_concat, FechaC
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DIGITADO', ?, ?, ?, NOW())";

    $stmt_ins = $conn->prepare($sql_ins);
    $stmt_ins->bind_param("sssssssssssssss", 
        $libro_o, $nmargi1, $texto_final, $tipo_p, $distrito, $anio_p, $libro_p, 
        $n_partida, $folio_o, $tomo_p, $fecha_evento, $usuario, $usuario, $concatenado
    );

    if ($stmt_ins->execute()) {
        header("Location: ../public/dashboard.php?msj=guardado");
    } else {
        die("Error: " . $conn->error);
    }
}

function armarLeyendaApellido($tipo, $nombre_el, $manual = "") {
    if ($tipo === 'soltera') return "seguirá usando sus apellidos de soltera, ";
    if ($tipo === 'con_de') return "usará el apellido del cónyuge precedido de la partícula DE, ";
    if ($tipo === 'sin_de') return "usará el apellido del cónyuge sin la partícula DE, ";
    if ($tipo === 'exterior') return mb_strtoupper($manual, 'UTF-8') . ", ";
    return "";
}
ob_end_flush();