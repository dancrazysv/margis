<?php
// Configuración de la Base de Datos
$host = "127.0.0.1";
$db   = "marginaciones";
$user = "root";
$pass = ""; // Coloca aquí tu contraseña si tienes una configurada en MySQL
$charset = 'utf8mb4';

// Conexión mediante MySQLi
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres para evitar problemas con tildes y ñ
$conn->set_charset($charset);

// Configuración de Zona Horaria (Importante para FechaC y HoraC)
date_default_timezone_set('America/El_Salvador');

// Constantes del Sistema
define('SISTEMA_NOMBRE', 'REVFA Digital');
define('ANIO_VIGENTE', date('Y')); 

/**
 * Iniciar sesión de forma segura si no ha sido iniciada
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Función global para limpiar entradas y evitar Inyecciones SQL básicas
 */
function limpiar($data) {
    global $conn;
    if ($data === null) return "";
    return $conn->real_escape_string(trim($data));
}

/**
 * Función global para definir colores de estado en todo el sistema
 * @param string $estado El nombre del estado del registro
 * @return array Clases de Bootstrap para fondo, borde y texto
 */
function getEstadoColor($estado) {
    switch (strtoupper($estado)) {
        case 'CERRADO':    
            return ['bg' => 'bg-success', 'border' => 'border-success', 'text' => 'text-white'];
        case 'EN_REVISION': 
            return ['bg' => 'bg-info', 'border' => 'border-info', 'text' => 'text-white'];
        case 'DIGITADO':   
            return ['bg' => 'bg-warning', 'border' => 'border-warning', 'text' => 'text-dark'];
        case 'OBSERVADO':  
            return ['bg' => 'bg-danger', 'border' => 'border-danger', 'text' => 'text-white'];
        default:           
            return ['bg' => 'bg-secondary', 'border' => 'border-secondary', 'text' => 'text-white'];
    }
}
?>