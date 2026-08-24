<?php
// movimientos.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/conexion.php';

$mensaje_exito = '';
$errores = [];

// Obtener productos activos para el selector
$stmtProd = $pdo->query("SELECT id_producto, codigo, nombre, stock_actual FROM productos WHERE estado = 1 ORDER BY nombre ASC");
$productos_activos = $stmtProd->fetchAll();

// Procesar registro de movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = $_POST['id_producto'] ?? '';
    $tipo        = $_POST['tipo'] ?? '';
    $cantidad    = intval($_POST['cantidad'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');
    $id_usuario  = $_SESSION['usuario_id'];

    if (empty($id_producto) || empty($tipo) || $cantidad <= 0) {
        $errores[] = "Todos los campos son obligatorios y la cantidad debe ser mayor a 0.";
    }

    if (!in_array($tipo, ['ENTRADA', 'SALIDA'])) {
        $errores[] = "Tipo de movimiento no válido.";
    }

    if (empty($errores)) {
        // Consultar el producto actual
        $stmtP = $pdo->prepare("SELECT stock_actual, nombre FROM productos WHERE id_producto = :id FOR UPDATE");
        
        try {
            // Usamos una transacción para asegurar consistencia atómica
            $pdo->beginTransaction();

            $stmtP->execute(['id' => $id_producto]);
            $prod = $stmtP->fetch();

            if (!$prod) {
                throw new Exception("El producto seleccionado no existe.");
            }

            // Regla de Negocio 4: Una salida no puede dejar el stock bajo cero
            if ($tipo === 'SALIDA' && $prod['stock_actual'] < $cantidad) {
                throw new Exception("Stock insuficiente. Hay {$prod['stock_actual']} unidad(es) disponible(s) de {$prod['nombre']}.");
            }

            // Calcular nuevo stock
            $nuevo_stock = ($tipo === 'ENTRADA') 
                ? $prod['stock_actual'] + $cantidad 
                : $prod['stock_actual'] - $cantidad;

            // 1. Insertar el movimiento (Trazabilidad)
            $sqlMov = "INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario) 
                       VALUES (:tipo, :cantidad, :observacion, :id_producto, :id_usuario)";
            $stmtMov = $pdo->prepare($sqlMov);
            $stmtMov->execute([
                'tipo'        => $tipo,
                'cantidad'    => $cantidad,
                'observacion' => $observacion,
                'id_producto' => $id_producto,
                'id_usuario'  => $id_usuario
            ]);

            // 2. Actualizar el stock del producto
            $sqlUpd = "UPDATE productos SET stock_actual = :stock WHERE id_producto = :id";
            $stmtUpd = $pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                'stock' => $nuevo_stock,
                'id'    => $id_producto
            ]);

            // Confirmar transacción
            $pdo->commit();
            $mensaje_exito = "Movimiento de $tipo registrado exitosamente. Nuevo stock: $nuevo_stock.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = $e->getMessage();
        }
    }
}

// Consultar el historial de movimientos con JOINs para mostrar nombres
$sqlHistorial = "SELECT m.*, p.codigo, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
                 FROM movimientos m
                 INNER JOIN productos p ON m.id_producto = p.id_producto
                 INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
                 ORDER BY m.id_movimiento DESC";
$movimientos = $pdo->query($sqlHistorial)->fetchAll();

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <!-- Formulario de Registro de Movimiento -->
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="bi bi-arrow-left-right text-primary"></i> Registrar Movimiento</h5>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($mensaje_exito)): ?>
                    <div class="alert alert-success py-2" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje_exito) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger py-2" role="alert">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errores as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="movimientos.php">
                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto *</label>
                        <select class="form-select" id="id_producto" name="id_producto" required>
                            <option value="">Seleccione un producto...</option>
                            <?php foreach ($productos_activos as $pa): ?>
                                <option value="<?= $pa['id_producto'] ?>">
                                    [<?= htmlspecialchars($pa['codigo']) ?>] <?= htmlspecialchars($pa['nombre']) ?> (Disp: <?= $pa['stock_actual'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="ENTRADA">Entrada (+)</option>
                                <option value="SALIDA">Salida (-)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="cantidad" class="form-label">Cantidad *</label>
                            <input type="number" min="1" class="form-control" id="cantidad" name="cantidad" required placeholder="Ej: 5">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observacion" class="form-label">Observación / Motivo</label>
                        <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Ej: Compra proveedor, Venta cliente, Ajuste"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Confirmar Movimiento</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Historial de Movimientos -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="bi bi-clock-history"></i> Historial Reciente</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha / Hora</th>
                                <th>Tipo</th>
                                <th>Producto</th>
                                <th>Cant.</th>
                                <th>Responsable</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movimientos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Aún no se registran movimientos.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($movimientos as $m): ?>
                                    <tr>
                                        <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($m['fecha_hora'])) ?></small></td>
                                        <td>
                                            <?php if ($m['tipo'] === 'ENTRADA'): ?>
                                                <span class="badge bg-success"><i class="bi bi-arrow-down-left"></i> Entrada</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bi bi-arrow-up-right"></i> Salida</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($m['producto_nombre']) ?></strong>
                                            <?php if (!empty($m['observacion'])): ?>
                                                <br><small class="text-muted fst-italic"><?= htmlspecialchars($m['observacion']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold"><?= $m['cantidad'] ?></td>
                                        <td><small><i class="bi bi-person"></i> <?= htmlspecialchars($m['usuario_nombre']) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>