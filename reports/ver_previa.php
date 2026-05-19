<?php
require_once '../config/db.php';

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM margi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc();

// Lógica de márgenes: Los folios impares tienen margen izquierdo ancho, los pares derecho.
// En el nuevo sistema digital, simulamos esto según el ID correlativo.
$es_par = ($reg['NMargi1'] % 2 == 0);
$margen_clase = $es_par ? "padding-left: 20mm; padding-right: 50mm;" : "padding-left: 50mm; padding-right: 20mm;";
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { size: legal; margin: 0; }
        body { font-family: 'Courier New', monospace; font-size: 11pt; }
        .hoja { 
            width: 215mm; height: 355mm; 
            <?php echo $margen_clase; ?>
            padding-top: 30mm;
            box-sizing: border-box;
            background: white;
        }
        .texto-marginacion { text-align: justify; white-space: pre-wrap; }
    </style>
</head>
<body onload="window.print()">
    <div class="hoja">
        <div class="texto-marginacion">
            <?php echo $reg['TxtMargi1']; [cite_start]?> [cite: 3]
        </div>
    </div>
</body>
</html>