<?php 
require_once '../config/db.php'; 
include '../templates/header.php'; 
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="container mt-4">
    <div class="card shadow border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-ring"></i> Registro de Marginación por Matrimonio</h5>
        </div>
        <div class="card-body">
            <form action="../actions/guardar_matrimonio.php" method="POST">
                
                <div class="row g-3 bg-light p-3 rounded mb-4">
                    <h6 class="text-primary border-bottom pb-2 fw-bold">1. DATOS DEL MATRIMONIO (ACTA/ESCRITURA)</h6>
                    
                    <div class="col-md-3">
                        <label class="form-label small">Fecha de Boda</label>
                        <input type="date" name="fecha_boda" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small">Cargo del Funcionario</label>
                        <select name="cargo" class="form-select select2-init" required>
                            <option value="">-- Seleccione Cargo --</option>
                            <?php 
                            $cargos = $conn->query("SELECT cargo FROM cargo_juridico ORDER BY cargo ASC");
                            while($c = $cargos->fetch_assoc()) {
                                echo "<option value='".htmlspecialchars($c['cargo'])."'>".htmlspecialchars($c['cargo'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small">Nombre del Funcionario</label>
                        <select name="funcionario" class="form-select select2-init" required>
                            <option value="">-- Busque el Nombre --</option>
                            <?php 
                            $notarios = $conn->query("SELECT nombre FROM notarios ORDER BY nombre ASC");
                            while($n = $notarios->fetch_assoc()) {
                                echo "<option value='".htmlspecialchars($n['nombre'])."'>".htmlspecialchars($n['nombre'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small">Régimen Patrimonial</label>
                        <select name="regimen" class="form-select select2-init">
                            <?php 
                            $reg = $conn->query("SELECT regimen FROM regimenes ORDER BY regimen ASC");
                            while($r = $reg->fetch_assoc()) echo "<option value='".htmlspecialchars($r['regimen'])."'>".htmlspecialchars($r['regimen'])."</option>";
                            ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small">Referencia Legal (Testimonio/Acta)</label>
                        <input type="text" name="referencia" class="form-control" placeholder="Ej: Testimonio de Escritura Pública número diez..." required>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold text-secondary border-bottom pb-2">DATOS DE ÉL</h6>
                        <div class="mb-3">
                            <label class="small">Nombre Completo (Él)</label>
                            <input type="text" name="nombre_el" class="form-control mb-2" placeholder="Nombres y Apellidos">
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small">N° Partida</label>
                                <input type="text" name="n_partida_el" class="form-control" placeholder="000">
                            </div>
                            <div class="col-md-4">
                                <label class="small">Libro</label>
                                <input type="text" name="libro_el" class="form-control" placeholder="Libro">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-bold text-secondary border-bottom pb-2">DATOS DE ELLA</h6>
                        <div class="mb-3">
                            <label class="small">Nombre Completo (Ella)</label>
                            <input type="text" name="nombre_ella" class="form-control mb-2" placeholder="Nombres y Apellidos">
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small">N° Partida</label>
                                <input type="text" name="n_partida_ella" class="form-control" placeholder="000">
                            </div>
                            <div class="col-md-4">
                                <label class="small">Libro</label>
                                <input type="text" name="libro_ella" class="form-control" placeholder="Libro">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-light p-3 rounded text-center">
                    <label class="fw-bold mb-2 d-block">¿Qué marginaciones desea generar en el sistema?</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="opcion" id="opt_el" value="el" required>
                        <label class="btn btn-outline-primary px-4" for="opt_el">Solo Él</label>

                        <input type="radio" class="btn-check" name="opcion" id="opt_ella" value="ella">
                        <label class="btn btn-outline-primary px-4" for="opt_ella">Solo Ella</label>

                        <input type="radio" class="btn-check" name="opcion" id="opt_ambos" value="ambos">
                        <label class="btn btn-outline-primary px-4" for="opt_ambos">Ambas (Él y Ella)</label>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <a href="dashboard.php" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5 btn-lg">Guardar Marginación(es)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-init').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Escriba para buscar --'
    });
});
</script>