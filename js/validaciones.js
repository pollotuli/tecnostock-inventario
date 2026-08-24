// js/validaciones.js
document.addEventListener('DOMContentLoaded', () => {
    const formProducto = document.getElementById('formProducto');

    if (formProducto) {
        formProducto.addEventListener('submit', (e) => {
            const precio = parseFloat(document.getElementById('precio').value);
            const stockActual = parseInt(document.getElementById('stock_actual').value, 10);
            const stockMinimo = parseInt(document.getElementById('stock_minimo').value, 10);

            let errores = [];

            if (isNaN(precio) || precio < 0) {
                errores.push('El precio debe ser un número igual o superior a 0.');
            }

            if (isNaN(stockActual) || stockActual < 0) {
                errores.push('El stock actual no puede ser menor a 0.');
            }

            if (isNaN(stockMinimo) || stockMinimo < 0) {
                errores.push('El stock mínimo no puede ser menor a 0.');
            }

            if (errores.length > 0) {
                e.preventDefault();
                alert(errores.join('\n'));
            }
        });
    }
});