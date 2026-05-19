<?php
require_once '../config/db.php';

$id_registro = $_GET['id'] ?? null;

if (!$id_registro) die("ID no válido.");

// Consultar la marginación por ID único
$stmt = $conn->prepare("SELECT * FROM margi WHERE id = ?");
$stmt->bind_param("i", $id_registro);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc();

if (!$reg) die("Registro no encontrado.");

// Determinar si es folio par o impar para los márgenes
// Históricamente, el sistema usaba 4 marginaciones por folio
$folio_virtual = ceil($reg['NMargi1'] / 4);
$es_par = ($folio_virtual % 2 == 0);

// Configuración de márgenes según el lado del folio
$margen_izq = $es_par ? "20mm" : "50mm";
$margen_der = $es_par ? "50mm" : "20mm";
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { size: legal; margin: 0; }
        body { 
            margin: 0; 
            padding: 0; 
            background-color: #ccc; 
            display: flex; 
            justify-content: center; 
        }
        .hoja-digital {
            background-color: white;
            width: 215.9mm; /* Ancho Legal */
            height: 355.6mm; /* Largo Legal */
            padding-top: 40mm;
            padding-left: <?php echo $margen_izq; ?>;
            padding-right: <?php echo $margen_der; ?>;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11pt;
            line-height: 1.5;
            text-align: justify;
        }
        @media print {
            body { background: none; }
            .hoja-digital { box-shadow: none; }
        }
    </style>
</head>
<body title="Vista Previa de Impresión">
    <div class="hoja-digital">
        <?php echo nl2br(htmlspecialchars($reg['TxtMargi1'])); ?>
    </div>
    <script>
        // Opción para imprimir automáticamente al cargar
        // window.print();
    </script>
</body>
</html>