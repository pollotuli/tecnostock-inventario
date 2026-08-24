# Sistema de Inventario - TecnoStock (Caso 1)

Sistema web para la gestión centralizada de catálogo de productos, control de existencias y trazabilidad de movimientos, desarrollado sin frameworks complejos utilizando la arquitectura PHP, MySQL y Bootstrap.

---

## 1. Análisis y Definición del Sistema

### 1.1 Contexto y Problema
El emprendimiento **TecnoStock** registraba sus existencias mediante planillas manuales, generando duplicidad de códigos, inconsistencias numéricas y falta de claridad en el stock disponible. La solución centraliza la persistencia de datos y automatiza el flujo de inventario.

### 1.2 Alcance y Actor
* **Actor Principal:** Encargado de Inventario.
* **Alcance:** Autenticación de usuarios, administración de catálogo (CRUD con baja lógica), registro de entradas/salidas de inventario con actualización atómica de stock y alertas visuales de reposición.

### 1.3 Reglas de Negocio Implementadas
1. **Unicidad:** Códigos de producto no repetibles (`UNIQUE`).
2. **No Negatividad:** Precios, stock actual, stock mínimo y cantidades mayores o iguales a 0 (`CHECK` y validaciones backend).
3. **Trazabilidad:** Registro automático de fecha/hora, tipo, cantidad, producto y usuario en cada movimiento.
4. **Protección de Saldo:** Una salida no puede dejar el stock bajo cero.
5. **Baja Lógica:** Productos con historial de movimientos no se eliminan físicamente (`DELETE`), solo se desactivan (`estado = 0`).
6. **Actualización Automática:** El stock se recalcula de forma inmediata y transaccional tras cada entrada o salida válida.

---

## 2. Pila Tecnológica

* **Backend:** PHP 8 (Programación estructurada, extensión PDO con sentencias preparadas y transacciones).
* **Base de Datos:** MySQL / MariaDB (Motor InnoDB con soporte de restricciones e integridad referencial).
* **Frontend:** HTML5, CSS3, Bootstrap 5.3 y JavaScript nativo.
* **Servidor Local:** Apache (XAMPP).
* **Control de Versiones:** Git y GitHub.

---

## 3. Estructura de la Base de Datos

* **`usuarios`:** Control de acceso y sesiones (`id_usuario`, `nombre`, `correo`, `clave` hasheada, `estado`).
* **`categorias`:** Clasificación de artículos (`id_categoria`, `nombre`, `descripcion`, `estado`).
* **`productos`:** Catálogo y existencias (`id_producto`, `codigo`, `nombre`, `descripcion`, `precio`, `stock_actual`, `stock_minimo`, `estado`, `id_categoria`).
* **`movimientos`:** Bitácora de transacciones (`id_movimiento`, `fecha_hora`, `tipo`, `cantidad`, `observacion`, `id_producto`, `id_usuario`).

---

## 4. Instrucciones de Despliegue Local

1. Clonar o descargar este repositorio dentro del directorio web del servidor:
   `C:\xampp\htdocs\tecnostock`
2. Iniciar los servicios **Apache** y **MySQL** desde el Panel de Control de XAMPP.
3. Ingresar a phpMyAdmin (`http://localhost/phpmyadmin`) y crear la base de datos `tecnostock_db`.
4. Importar o ejecutar el script ubicado en:
   `db/schema.sql`
5. Abrir el navegador e ingresar a:
   `http://localhost/tecnostock/`

### Credenciales de Acceso
* **Correo:** `admin@tecnostock.cl`
* **Contraseña:** `admin123`

---

## 5. Matriz de Casos de Prueba

| Caso de Prueba | Entrada / Acción | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- |
| **CP-01: Login válido** | `admin@tecnostock.cl` / `admin123` | Acceso concedido y redirección a `productos.php`. | Superado |
| **CP-02: Código duplicado** | Registrar producto con código existente | Bloqueo por validación y mensaje de error. | Superado |
| **CP-03: Alerta de stock** | Producto con `stock_actual < stock_minimo` | Distintivo visual rojo *Bajo Stock*. | Superado |
| **CP-04: Salida sin saldo** | Intentar salida de 10 unidades teniendo 5 | Transacción rechazada y stock intacto. | Superado |
| **CP-05: Baja lógica** | Desactivar un producto registrado | El registro cambia a inactivo sin borrado físico de la BD. | Superado |