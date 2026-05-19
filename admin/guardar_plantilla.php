<?php
// admin/guardar_plantilla.php
require_once '../config/db.php';
session_start();

if ($_SESSION['user_area'] !== 'ADMINISTRADOR') {
    header("Location: ../public/dashboard.php?error=sin_permiso");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre_tramite = limpiar($_POST['nombre_tramite']);
    $tipo_asiento = limpiar($_POST['tipo_asiento']);
    $tipo_acto = limpiar($_POST['tipo_acto']);
    $tipo_marginacion = limpiar($_POST['tipo_marginacion']);
    $cuerpo_legal = $_POST['cuerpo_legal']; // No usar limpiar aquí para mantener formato
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $requiere_conyuge = isset($_POST['requiere_conyuge']) ? 1 : 0;
    $requiere_leyenda = isset($_POST['requiere_leyenda']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Verificar que el cuerpo legal tenga al menos una variable
    if (preg_match_all('/\{([^}]+)\}/', $cuerpo_legal, $matches)) {
        $variables_encontradas = $matches[1];
    } else {
        $variables_encontradas = [];
    }
    
    if ($id > 0) {
        // Actualizar plantilla existente
        $sql = "UPDATE plantillas_textos SET 
                nombre_tramite = ?, 
                tipo_asiento = ?, 
                tipo_acto = ?, 
                tipo_marginacion = ?,
                cuerpo_legal = ?,
                descripcion = ?,
                requiere_conyuge = ?,
                requiere_leyenda = ?,
                activo = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiiii", 
            $nombre_tramite, $tipo_asiento, $tipo_acto, $tipo_marginacion,
            $cuerpo_legal, $descripcion, $requiere_conyuge, $requiere_leyenda, 
            $activo, $id);
    } else {
        // Insertar nueva plantilla
        $sql = "INSERT INTO plantillas_textos (
                    nombre_tramite, tipo_asiento, tipo_acto, tipo_marginacion, 
                    cuerpo_legal, descripcion, requiere_conyuge, requiere_leyenda, 
                    activo, id_tipo_margi
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiii", 
            $nombre_tramite, $tipo_asiento, $tipo_acto, $tipo_marginacion,
            $cuerpo_legal, $descripcion, $requiere_conyuge, $requiere_leyenda, 
            $activo);
    }
    
    if ($stmt->execute()) {
        $nuevo_id = ($id > 0) ? $id : $conn->insert_id;
        
        // Si es una nueva plantilla, mostrar mensaje para configurar campos
        if ($id == 0) {
            header("Location: plantillas.php?accion=campos&id=$nuevo_id&msg=creada");
        } else {
            header("Location: plantillas.php?accion=listar&msg=actualizada");
        }
    } else {
        die("Error al guardar plantilla: " . $conn->error);
    }
} else {
    header("Location: plantillas.php?accion=listar");
}
?>