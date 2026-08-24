<?php
// producto_form.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/conexion.php';

// Obtener categorías activas para el select
$stmtCat = $pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre ASC");
$categorias = $stmtCat->fetchAll();

$id_producto = $_GET['id'] ?? null;
$es_edicion  = !empty($id_producto);

$codigo       = '';
$nombre       = '';
$descripcion  = '';
$precio       = '';
$stock_actual = '0';
$stock_minimo = '0';
$id_categoria = '';
$errores      = [];

// Si es edición, cargamos los datos existentes
if ($es_edicion && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = :id");
    $stmt->execute(['id' => $id_producto]);
    $producto = $stmt->fetch();

    if (!$producto) {
        header("Location: productos.php");
        exit();
    }

    $codigo       = $producto['codigo'];
    $nombre       = $producto['nombre'];
    $descripcion  = $producto['descripcion'];
    $precio       = $producto['precio'];
    $stock_actual = $producto['stock_actual'];
    $stock_minimo = $producto['stock_minimo'];
    $id_categoria = $producto['id_categoria'];
}

// Procesar el formulario cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = trim($_POST['codigo'] ?? '');
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = trim($_POST['precio'] ?? '');
    $stock_actual = trim($_POST['stock_actual'] ?? '');
    $stock_minimo = trim($_POST['stock_minimo'] ?? '');
    $id_categoria = trim($_POST['id_categoria'] ?? '');

    // Validaciones Backend (Reglas del Negocio)
    if (empty($codigo) || empty($nombre) || empty($precio) || $stock_actual === '' || $stock_minimo === '' || empty($id_categoria)) {
        $errores[] = "Todos los campos obligatorios deben ser completados.";
    }

    if (!is_numeric($precio) || $precio < 0) {
        $errores[] = "El precio debe ser un número mayor o igual a 0.";
    }

    if (!is_numeric($stock_actual) || $stock_actual < 0) {
        $errores[] = "El stock actual no puede ser negativo.";
    }

    if (!is_numeric($stock_minimo) || $stock_minimo < 0) {
        $errores[] = "El stock mínimo no puede ser negativo.";
    }

    // Validar unicidad del código
    if ($es_edicion) {
        $stmtCheck = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = :codigo AND id_producto != :id");
        $stmtCheck->execute(['codigo' => $codigo, 'id' => $id_producto]);
    } else {
        $stmtCheck = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = :codigo");
        $stmtCheck->execute(['codigo' => $codigo]);
    }

    if ($stmtCheck->fetch()) {
        $errores[] = "El código ingresado ya está asignado a otro producto.";
    }

    // Si no hay errores, guardar en BD con PDO
    if (empty($errores)) {
        if ($es_edicion) {
            $sql = "UPDATE productos 
                    SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion, 
                        precio = :precio, stock_actual = :stock_actual, stock_minimo = :stock_minimo, 
                        id_categoria = :id_categoria 
                    WHERE id_producto = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'codigo'       => $codigo,
                'nombre'       => $nombre,
                'descripcion'  => $descripcion,
                'precio'       => $precio,
                'stock_actual' => $stock_actual,
                'stock_minimo' => $stock_minimo,
                'id_categoria' => $id_categoria,
                'id'           => $id_producto
            ]);
        } else {
            $sql = "INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria, estado) 
                    VALUES (:codigo, :nombre, :descripcion, :precio, :stock_actual, :stock_minimo, :id_categoria, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'codigo'       => $codigo,
                'nombre'       => $nombre,
                'descripcion'  => $descripcion,
                'precio'       => $precio,
                'stock_actual' => $stock_actual,
                'stock_minimo' => $stock_minimo,
                'id_categoria' => $id_categoria
            ]);
        }

        header("Location: productos.php");
        exit();
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h4 class="card-title mb-0 fw-bold"><?= $es_edicion ? 'Editar Producto' : 'Registrar Nuevo Producto' ?></h4>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errores as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formProducto" novalidate>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="codigo" class="form-label">Código del Producto *</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required value="<?= htmlspecialchars($codigo) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="id_categoria" class="form-label">Categoría *</label>
                            <select class="form-select" id="id_categoria" name="id_categoria" required>
                                <option value="">Seleccione una categoría...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categoria'] ?>" <?= ($id_categoria == $cat['id_categoria']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Producto *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required value="<?= htmlspecialchars($nombre) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($descripcion) ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="precio" class="form-label">Precio ($) *</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="precio" name="precio" required value="<?= htmlspecialchars($precio) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="stock_actual" class="form-label">Stock Actual *</label>
                            <input type="number" min="0" class="form-control" id="stock_actual" name="stock_actual" required value="<?= htmlspecialchars($stock_actual) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="stock_minimo" class="form-label">Stock Mínimo *</label>
                            <input type="number" min="0" class="form-control" id="stock_minimo" name="stock_minimo" required value="<?= htmlspecialchars($stock_minimo) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="productos.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><?= $es_edicion ? 'Guardar Cambios' : 'Registrar Producto' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>