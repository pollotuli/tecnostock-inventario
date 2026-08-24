<?php
// producto_desactivar.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/conexion.php';

$id_producto = $_GET['id'] ?? null;
$activar     = isset($_GET['activar']) ? 1 : 0;

if ($id_producto) {
    // Baja lógica (Regla de negocio: nunca DELETE físico)
    $stmt = $pdo->prepare("UPDATE productos SET estado = :estado WHERE id_producto = :id");
    $stmt->execute([
        'estado' => $activar,
        'id'     => $id_producto
    ]);
}

header("Location: productos.php");
exit();