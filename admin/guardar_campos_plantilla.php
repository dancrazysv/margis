<?php
// admin/guardar_campos_plantilla.php
require_once '../config/db.php';
session_start();

if ($_SESSION['user_area'] !== 'ADMINISTRADOR') {
    header("Location: ../public/dashboard.php?error=sin_permiso");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plantilla_id = (int)$_POST['plantilla_id'];
    $campos = $_POST['campos'] ?? [];
    
    // Verificar que la plantilla existe
    $check = $conn->prepare("SELECT id FROM plantillas_textos WHERE id = ?");
    $check->bind_param("i", $plantilla_id);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        header("Location: plantillas.php?accion=listar&error=plantilla_no_existe");
        exit;
    }
    
    // Obtener IDs actuales de campos en la base de datos
    $stmt = $conn->prepare("SELECT id FROM plantillas_campos WHERE plantilla_id = ?");
    $stmt->bind_param("i", $plantilla_id);
    $stmt->execute();
    $ids_actuales = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids_actuales[] = $row['id'];
    }
    
    // IDs que vienen en el POST
    $ids_recibidos = [];
    foreach ($campos as $campo) {
        if (isset($campo['id']) && $campo['id'] > 0) {
            $ids_recibidos[] = (int)$campo['id'];
        }
    }
    
    // Eliminar campos que ya no están en el POST
    $ids_eliminar = array_diff($ids_actuales, $ids_recibidos);
    if (!empty($ids_eliminar)) {
        $placeholders = implode(',', array_fill(0, count($ids_eliminar), '?'));
        $delete = $conn->prepare("DELETE FROM plantillas_campos WHERE id IN ($placeholders)");
        $delete->bind_param(str_repeat('i', count($ids_eliminar)), ...$ids_eliminar);
        $delete->execute();
    }
    
    // Insertar o actualizar campos
    foreach ($campos as $campo) {
        $id = isset($campo['id']) ? (int)$campo['id'] : 0;
        $nombre_campo = trim($campo['nombre_campo']);
        $etiqueta = trim($campo['etiqueta']);
        $tipo_campo = $campo['tipo_campo'];
        $requerido = isset($campo['requerido']) ? (int)$campo['requerido'] : 1;
        $orden = isset($campo['orden']) ? (int)$campo['orden'] : 0;
        $opciones = !empty($campo['opciones']) ? $campo['opciones'] : null;
        $ayuda = isset($campo['ayuda']) ? trim($campo['ayuda']) : null;
        
        // Validar que el nombre del campo no tenga espacios ni caracteres especiales
        $nombre_campo = preg_replace('/[^a-z0-9_]/i', '_', $nombre_campo);
        $nombre_campo = strtolower($nombre_campo);
        
        if ($id > 0) {
            // Actualizar campo existente
            $sql = "UPDATE plantillas_campos SET 
                    nombre_campo = ?, 
                    etiqueta = ?, 
                    tipo_campo = ?, 
                    opciones = ?,
                    requerido = ?,
                    orden = ?,
                    ayuda = ?
                    WHERE id = ? AND plantilla_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssissi", 
                $nombre_campo, $etiqueta, $tipo_campo, $opciones, 
                $requerido, $orden, $ayuda, $id, $plantilla_id);
        } else {
            // Insertar nuevo campo
            $sql = "INSERT INTO plantillas_campos (
                        plantilla_id, nombre_campo, etiqueta, tipo_campo, 
                        opciones, requerido, orden, ayuda
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssis", 
                $plantilla_id, $nombre_campo, $etiqueta, $tipo_campo, 
                $opciones, $requerido, $orden, $ayuda);
        }
        
        if (!$stmt->execute()) {
            die("Error al guardar campo: " . $stmt->error);
        }
    }
    
    header("Location: plantillas.php?accion=listar&msg=campos_guardados");
} else {
    header("Location: plantillas.php?accion=listar");
}
?>