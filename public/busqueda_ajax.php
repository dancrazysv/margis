<?php
require_once '../config/db.php';

$term = $_GET['term'] ?? '';

// Búsqueda rápida usando el índice FULLTEXT de tu BD
$query = "SELECT LibroO, NMargi1, TxtMargi1 
          FROM margi 
          WHERE MATCH(busquedalf, TxtMargi1) AGAINST(? IN NATURAL LANGUAGE MODE) 
          LIMIT 10";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $term);
$stmt->execute();
$result = $stmt->get_result();

$json = [];
while($row = $result->fetch_assoc()) {
    $json[] = [
        'id' => $row['LibroO'] . "-" . $row['NMargi1'],
        'label' => $row['LibroO'] . "-" . $row['NMargi1'] . " - " . substr($row['TxtMargi1'], 0, 50) . "...",
        'value' => $row['LibroO'] . "-" . $row['NMargi1']
    ];
}
echo json_encode($json);
?>