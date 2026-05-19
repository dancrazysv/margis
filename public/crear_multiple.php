<?php 
require_once '../config/db.php';
include '../templates/header.php'; 
?>

<div class="container mt-4">
    <h3>Generador de Acto Múltiple (Divorcio / Viudez)</h3>
    <form action="../actions/guardar_multiple.php" method="POST">
        <div class="card mb-3">
            <div class="card-header bg-dark text-white">Datos Comunes (Sentencia / Resolución)</div>
            <div class="card-body row">
                <div class="col-md-6">
                    <label>Número de Oficio/Resolución</label>
                    <input type="text" name="comun[num_doc]" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Fecha del Documento</label>
                    <input type="date" name="comun[fecha_doc]" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card p-2">
                    <h6>Marginación Nacimiento (Él)</h6>
                    <input type="text" name="partida[1][num]" class="form-control mb-2" placeholder="Partida">
                    <input type="text" name="partida[1][libro]" class="form-control" placeholder="Libro">
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-2">
                    <h6>Marginación Nacimiento (Ella)</h6>
                    <input type="text" name="partida[2][num]" class="form-control mb-2" placeholder="Partida">
                    <input type="text" name="partida[2][libro]" class="form-control" placeholder="Libro">
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-2">
                    <h6>Cancelación Matrimonio</h6>
                    <input type="text" name="partida[3][num]" class="form-control mb-2" placeholder="Partida">
                    <input type="text" name="partida[3][libro]" class="form-control" placeholder="Libro">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-4">Generar los 3 Asientos</button>
    </form>
</div>