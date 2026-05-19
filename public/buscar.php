<?php
require_once '../config/db.php';
include '../templates/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-search"></i> Buscar Marginaciones</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="dashboard.php" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="q" class="form-control form-control-lg" 
                           placeholder="Buscar por ID Digital, Número de Trámite, Partida, Libro o Texto..." 
                           value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>