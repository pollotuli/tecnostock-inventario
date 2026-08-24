<?php
// index.php
session_start();

// Si ya inició sesión, redirigir directo al inventario
if (isset($_SESSION['usuario_id'])) {
    header("Location: productos.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/conexion.php';

    $correo = trim($_POST['correo'] ?? '');
    $clave  = trim($_POST['clave'] ?? '');

    if (empty($correo) || empty($clave)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        // Consulta segura mediante sentencia preparada
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, clave, estado FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute(['correo' => $correo]);
        $usuario = $stmt->fetch();

        // Verificación con hash nativo y validación de estado activo
        if ($usuario && password_verify($clave, $usuario['clave'])) {
            if ($usuario['estado'] == 1) {
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                header("Location: productos.php");
                exit();
            } else {
                $error = "El usuario se encuentra inactivo en el sistema.";
            }
        } else {
            $error = "Credenciales incorrectas. Verifica el correo y la contraseña.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4 fw-bold text-primary">Ingreso al Sistema</h3>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php" novalidate>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" required placeholder="admin@tecnostock.cl" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="clave" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="clave" name="clave" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mt-2">Iniciar Sesión</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>