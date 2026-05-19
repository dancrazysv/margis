<?php
require_once '../config/db.php';

$sujeto = $_POST['sujeto'];
$anio_digital = ANIO_VIGENTE;

// Función para obtener el siguiente ID Digital
function proximoID($conn, $anio) {
    $stmt = $conn->prepare("SELECT MAX(CAST(NMargi1 AS UNSIGNED)) as u FROM margi WHERE LibroO = ?");
    $stmt->bind_param("s", $anio);
    $stmt->execute();
    return ($stmt->get_result()->fetch_assoc()['u'] ?? 0) + 1;
}

// Lógica para crear el asiento
if ($sujeto == 'EL' || $sujeto == 'AMBOS') {
    $id_el = proximoID($conn, $anio_digital);
    $texto_el = "Modifíquese el presente asiento de nacimiento, en el sentido que la persona inscrita adquirió el estado familiar de casado por contraer matrimonio con {$_POST['nombre_ella']} en {$_POST['lugar_boda']}, en fecha {$_POST['fecha_boda']} ante {$_POST['funcionario']}, según {$_POST['referencia_legal']}."; [cite: 54]
    
    // Insertar para Él
    insertarMargi($conn, $id_el, $anio_digital, $texto_el, $_POST['n_partida_el'], $_POST['libro_el'], 'NACIMIENTO');
}

if ($sujeto == 'ELLA' || $sujeto == 'AMBOS') {
    $id_ella = proximoID($conn, $anio_digital);
    $texto_ella = "Modifíquese el presente asiento de nacimiento, en el sentido que la persona inscrita adquirió el estado familiar de casada por contraer matrimonio con {$_POST['nombre_el']}... (con uso de apellido de casada según Art. 21 Ley del Nombre)..."; [cite: 54, 84]
    
    // Insertar para Ella
    insertarMargi($conn, $id_ella, $anio_digital, $texto_ella, $_POST['n_partida_ella'], $_POST['libro_ella'], 'NACIMIENTO');
}

function insertarMargi($conn, $id, $anio_o, $texto, $partida, $libro, $tipo_p) {
    $sql = "INSERT INTO margi (NMargi1, LibroO, TxtMargi1, NPartida, LibroP, TipoP, Iniciales1, estado, FechaC, HoraC) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'ABIERTO', CURDATE(), CURTIME())";
    $st = $conn->prepare($sql);
    $st->bind_param("sssssss", $id, $anio_o, $texto, $partida, $libro, $tipo_p, $_SESSION['iniciales']);
    $st->execute();
}

header("Location: ../public/dashboard.php?msg=success");