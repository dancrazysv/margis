<?php
// actions/actualizar.php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)$_POST['id_registro'];
    $texto    = limpiar($_POST['texto_final']);
    $partida  = limpiar($_POST['partida']);
    $tipo_p   = limpiar($_POST['tipo_p']);
    $libro    = limpiar($_POST['libro_p']);
    $folio    = limpiar($_POST['folio']);
    $tomo     = limpiar($_POST['tomo']);
    $lugar    = limpiar($_POST['distrito_partida']);
    $accion   = $_POST['accion'];
    
    // NUEVO: Captura del número de trámite desde el formulario
    $tramite  = limpiar($_POST['tramite_num']); 

    // 1. Lógica de estados
    $res_check = $conn->query("SELECT estado FROM margi WHERE id = $id");
    $reg_check = $res_check->fetch_assoc();
    $estado_actual = $reg_check['estado'];

    if ($accion === 'ENVIAR') {
        $nuevo_estado = 'EN_REVISION';
        $revestado    = 'VALIDAR';
    } else {
        $nuevo_estado = $estado_actual;
        $revestado    = 'EDICION';
    }

    // 2. SQL - Se agrega la columna num_tramite a la consulta UPDATE
    $sql = "UPDATE margi SET 
            TxtMargi1 = ?, 
            NPartida = ?, 
            TipoP = ?,
            LibroP = ?, 
            FolioO = ?, 
            TomoP = ?,
            lugar = ?, 
            num_tramite = ?, 
            estado = ?, 
            revestado = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación del SQL: " . $conn->error);
    }

    // Se agrega una "s" adicional en el bind_param para el trámite (total 10 strings, 1 entero)
    $stmt->bind_param("ssssssssssi", 
        $texto, 
        $partida, 
        $tipo_p,
        $libro, 
        $folio, 
        $tomo,
        $lugar,
        $tramite,
        $nuevo_estado, 
        $revestado, 
        $id
    );

    if ($stmt->execute()) {
        $msg = ($accion === 'ENVIAR') ? "enviado" : "actualizado";
        header("Location: ../public/dashboard.php?msg=" . $msg);
        exit;
    } else {
        die("Error al ejecutar: " . $stmt->error);
    }
}