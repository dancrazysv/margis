<?php
// admin/guardar_combo_plantillas.php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['user_area'] !== 'ADMINISTRADOR') {
    header("Location: ../public/dashboard.php?error=sin_permiso");
    exit;
}

$combo_id = (int)($_POST['combo_id'] ?? 0);
$plantillas = $_POST['plantillas'] ?? [];

// Eliminar todas las plantillas actuales del combo
$delete = $conn->prepare("DELETE FROM combo_plantillas WHERE combo_id = ?");
$delete->bind_param("i", $combo_id);
$delete->execute();

// Insertar las nuevas plantillas seleccionadas
$insert = $conn->prepare("INSERT INTO combo_plantillas (combo_id, plantilla_id, orden, requiere_partida_propia) VALUES (?, ?, ?, ?)");

foreach ($plantillas as $plantilla_id => $datos) {
    if (isset($datos['seleccionado']) && $datos['seleccionado'] == 1) {
        $orden = (int)($datos['orden'] ?? 0);
        $requiere_partida = (int)($datos['requiere_partida'] ?? 1);
        
        $insert->bind_param("iiii", $combo_id, $plantilla_id, $orden, $requiere_partida);
        $insert->execute();
    }
}

header("Location: combos.php?accion=listar&msg=plantillas_guardadas");
?>