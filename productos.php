<?php
// productos.php
session_start();

// Control de acceso: solo usuarios autenticados
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/conexion.php';

// Capturar el término de búsqueda
$busqueda = trim($_GET['buscar'] ?? '');

// Consulta SQL con filtro dinámico y JOIN con categorías
$sql = "SELECT p.*, c.nombre AS categoria_nombre 
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1 = 1";

$parametros = [];

if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE :busq OR p.codigo LIKE :busq OR c.nombre LIKE :busq)";
    $parametros['busq'] = "%$busqueda%";
}

$sql .= " ORDER BY p.id_producto DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Catálogo de Productos</h2>
        <p class="text-muted mb-0">Control de existencias e inventario general</p>
    </div>
    <a href="producto_form.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nuevo Producto
    </a>
</div>

<!-- Barra de Búsqueda -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="productos.php" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por código, nombre o categoría..." value="<?= htmlspecialchars($busqueda) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-search"></i> Buscar</button>
                <?php if (!empty($busqueda)): ?>
                    <a href="productos.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Productos -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No se encontraron productos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <?php 
                                // Regla de Negocio 8: Alerta de stock bajo
                                $alertaStock = ($p['stock_actual'] < $p['stock_minimo']);
                            ?>
                            <tr class="<?= $p['estado'] == 0 ? 'table-secondary text-muted' : '' ?>">
                                <td class="fw-semibold"><?= htmlspecialchars($p['codigo']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                    <?php if (!empty($p['descripcion'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($p['descripcion']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['categoria_nombre']) ?></span></td>
                                <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="fw-bold <?= $alertaStock ? 'text-danger' : 'text-success' ?>">
                                        <?= $p['stock_actual'] ?>
                                    </span>
                                    <?php if ($alertaStock && $p['estado'] == 1): ?>
                                        <span class="badge bg-danger ms-1" title="Stock bajo el mínimo"><i class="bi bi-exclamation-triangle"></i> Bajo Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $p['stock_minimo'] ?></td>
                                <td>
                                    <?php if ($p['estado'] == 1): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="producto_form.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($p['estado'] == 1): ?>
                                        <a href="producto_desactivar.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas desactivar este producto?');" title="Desactivar">
                                            <i class="bi bi-toggle-on"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="producto_desactivar.php?id=<?= $p['id_producto'] ?>&activar=1" class="btn btn-sm btn-outline-success" title="Activar">
                                            <i class="bi bi-toggle-off"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>