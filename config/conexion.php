<?php
// config/conexion.php

$host = 'localhost';
$dbname = 'tecnostock_db';
$usuario = 'root';
$password = ''; // Por defecto en XAMPP viene vacío
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Reportar errores como excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devolver arreglos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Consultas preparadas reales (seguridad nativa)
];

try {
    $pdo = new PDO($dsn, $usuario, $password, $opciones);
} catch (PDOException $e) {
    // Manejo seguro de error sin exponer credenciales al usuario
    die("Error de conexión a la base de datos: " . $e->getMessage());
}