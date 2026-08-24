-- 1. Tabla Usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    estado TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla Categorías
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    estado TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla Productos
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
    stock_actual INT NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo INT NOT NULL DEFAULT 0 CHECK (stock_minimo >= 0),
    estado TINYINT(1) DEFAULT 1,
    id_categoria INT NOT NULL,
    CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria) 
        REFERENCES categorias(id_categoria) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla Movimientos
CREATE TABLE movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('ENTRADA', 'SALIDA') NOT NULL,
    cantidad INT NOT NULL CHECK (cantidad > 0),
    observacion TEXT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    CONSTRAINT fk_movimientos_producto FOREIGN KEY (id_producto) 
        REFERENCES productos(id_producto) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimientos_usuario FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- DATOS DE PRUEBA INICIALES
-- ==========================================

-- Usuario de prueba (Clave: admin123)
-- Usamos hash nativo de PHP: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO usuarios (nombre, correo, clave, estado) VALUES
('Administrador TecnoStock', 'admin@tecnostock.cl', '$2y$10$eE0m7h2V8b6wOaW9cT6rDeuUvU/rZ3j5T3fQ.kYv6M5mKj7YyH2q2', 1);

-- Categorías iniciales
INSERT INTO categorias (nombre, descripcion, estado) VALUES
('Periféricos', 'Teclados, mouse, audífonos y accesorios de entrada/salida', 1),
('Almacenamiento', 'Discos duros, SSD, pendrives y memorias SD', 1),
('Cables y Adaptadores', 'Cables HDMI, USB-C, adaptadores multipuerto', 1);

-- Productos iniciales
INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria) VALUES
('PROD-001', 'Mouse Inalámbrico Ergonómico', 'Mouse óptico 2.4GHz con receptor USB', 14990.00, 15, 5, 1, 1),
('PROD-002', 'Teclado Mecánico RGB', 'Teclado switch azul formato 80%', 39990.00, 3, 5, 1, 1), -- Alerta: stock_actual < stock_minimo
('PROD-003', 'SSD NVMe 1TB', 'Unidad de estado sólido M.2 PCIe 4.0', 65990.00, 8, 2, 1, 2); 