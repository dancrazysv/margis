<?php 
require_once '../config/db.php'; 
include '../templates/header.php'; 
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Administrador de Modelos Legales (Plantillas)</h5>
        </div>
        <div class="card-body">
            <form action="../actions/guardar_plantilla.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre del Trámite</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Rectificación por Notario" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Texto Legal del Modelo</label>
                    <div class="alert alert-info small">
                        Escribe el texto y encierra entre llaves <code>{ }</code> los datos que quieres que el sistema te pida. 
                        <br>Ejemplo: <i>"ante los oficios del notario {nombre_notario} en fecha {fecha_escritura}..."</i>
                    </div>
                    <textarea name="cuerpo" class="form-control" rows="10" style="font-family: 'Courier New', monospace;"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Guardar Modelo</button>
                </div>
            </form>
        </div>
    </div>
</div>