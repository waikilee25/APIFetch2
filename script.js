// URL del controlador PHP
const URL_REGISTRAR = "registrar.php";

document.addEventListener("DOMContentLoaded", () => {
    ListarProductos();
});

async function enviarFormulario(accion) {

    // Leer valores del formulario
    const id       = document.getElementById("id").value.trim();
    const codigo   = document.getElementById("Codigo").value.trim();
    const producto = document.getElementById("Producto").value.trim();
    const precio   = document.getElementById("Precio").value.trim();
    const cantidad = document.getElementById("Cantidad").value.trim();

    // Validaciones comunes a ambas acciones
    let errorCliente = "";

    if (codigo === "") {
        errorCliente = "El código es obligatorio.";
    } else if (producto === "") {
        errorCliente = "El nombre del producto es obligatorio.";
    } else if (precio === "" || parseFloat(precio) <= 0) {
        errorCliente = "El precio debe ser mayor a 0.";
    } else if (cantidad === "" || parseInt(cantidad) < 0) {
        errorCliente = "La cantidad no puede ser negativa.";
    }

    // Switch en JS para reglas específicas por acción
    if (errorCliente === "") {
        switch (accion) {
            case "Guardar":
                // Regla de negocio: nuevo producto debe tener al menos 1 unidad
                if (parseInt(cantidad) < 1) {
                    errorCliente = "Al registrar un producto nuevo debe tener al menos 1 unidad en stock.";
                }
                break;

            case "Modificar":
                // Se requiere ID válido para modificar
                if (id === "" || parseInt(id) <= 0) {
                    errorCliente = "Seleccione un producto de la tabla para modificarlo.";
                }
                break;

            default:
                errorCliente = "Acción no reconocida.";
        }
    }

    // Si hay error, mostrar alerta y detener
    if (errorCliente !== "") {
        Swal.fire({ icon: "warning", title: "LLene los campos", text: errorCliente });
        return;
    }

    const formData = new FormData();
    formData.append("Accion",   accion);
    formData.append("Codigo",   codigo);
    formData.append("Producto", producto);
    formData.append("Precio",   precio);
    formData.append("Cantidad", cantidad);

    // El ID solo se agrega cuando se modifica
    if (accion === "Modificar") {
        formData.append("id", id);
    }

    // Indicador de carga
    Swal.fire({
        title: "Procesando...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        // Petición fetch al backend 
        const response = await fetch(URL_REGISTRAR, {
            method: "POST",
            body:   formData
        });

        if (!response.ok) {
            throw new Error("Error del servidor: " + response.status);
        }

        // Parsear JSON devuelto por registrar.php
        const data = await response.json();

        // Switch en JS para manejar respuesta según acción
        switch (data.accion) {

            case "Guardar":
                if (data.success) {
                    // Éxito: alerta verde, limpiar formulario, recargar tabla
                    Swal.fire({
                        icon: "success",
                        title: "¡Guardado!",
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    limpiarFormulario();
                    renderizarTabla(data.tabla);
                } else {
                    mostrarErrores(data.message, data.errors);
                }
                break;

            case "Modificar":
                if (data.success) {
                    // Éxito: alerta verde, limpiar formulario, recargar tabla
                    Swal.fire({
                        icon: "success",
                        title: "¡Actualizado!",
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    limpiarFormulario();
                    renderizarTabla(data.tabla);
                } else {
                    mostrarErrores(data.message, data.errors);
                }
                break;

            default:
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message || "Respuesta no reconocida del servidor."
                });
        }

    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Error de conexión",
            text: "No se pudo conectar al servidor. Detalle: " + error.message
        });
    }
}

async function buscarProducto() {
    const termino = document.getElementById("campoBuscar").value.trim();

    if (termino === "") {
        Swal.fire({ icon: "warning", title: "Buscar", text: "Ingrese un término para buscar." });
        return;
    }

    const formData = new FormData();
    formData.append("Accion",  "Buscar");
    formData.append("Termino", termino);

    try {
        const response = await fetch(URL_REGISTRAR, { method: "POST", body: formData });
        if (!response.ok) throw new Error("HTTP " + response.status);

        const data = await response.json();

        if (data.success && data.datos.length > 0) {
            renderizarTabla(data.datos);
            Swal.fire({
                icon: "info",
                title: "Búsqueda",
                text: data.message,
                timer: 1800,
                showConfirmButton: false
            });
        } else {
            renderizarTabla([]);
            Swal.fire({ icon: "warning", title: "Sin resultados", text: "No se encontraron productos con ese término." });
        }

    } catch (error) {
        Swal.fire({ icon: "error", title: "Error", text: "No se pudo realizar la búsqueda: " + error.message });
    }
}

async function ListarProductos() {
    const formData = new FormData();
    formData.append("Accion", "Listar");

    try {
        const response = await fetch(URL_REGISTRAR, { method: "POST", body: formData });
        if (!response.ok) throw new Error("HTTP " + response.status);

        const data = await response.json();
        renderizarTabla(data.success ? data.tabla : []);

    } catch (error) {
        console.error("Error al listar productos:", error);
        
        Swal.fire({
            icon: "error",
            title: "Error al cargar productos",
            text: "No se pudo conectar con el servidor."
        });
    }
}

function renderizarTabla(productos) {
    const tbody = document.getElementById("cuerpoTabla");

    if (!productos || productos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-3">
                    No hay productos registrados.
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = productos.map(p => `
        <tr>
            <td>${p.id}</td>
            <td>${p.codigo}</td>
            <td>${p.producto}</td>
            <td>$${parseFloat(p.precio).toFixed(2)}</td>
            <td>${p.cantidad}</td>
            <td>
                <button class="btn btn-sm btn-warning text-white"
                        onclick="cargarParaEditar(${p.id}, '${escapar(p.codigo)}', '${escapar(p.producto)}', ${p.precio}, ${p.cantidad})">
                    Editar
                </button>
                <button class="btn btn-sm btn-danger"
                        onclick="eliminarProducto(${p.id})">
                    Eliminar
                </button>
            </td>
        </tr>
    `).join("");
}

function cargarParaEditar(id, codigo, producto, precio, cantidad) {

    if (!id || id <= 0) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "ID de producto inválido."
        });
        return;
    }

    // Rellenar los campos del formulario con los datos del producto
    document.getElementById("id").value       = id;
    document.getElementById("Codigo").value   = codigo;
    document.getElementById("Producto").value = producto;
    document.getElementById("Precio").value   = precio;
    document.getElementById("Cantidad").value = cantidad;

    // Cambiar botones: ocultar Registrar, mostrar Actualizar
    document.getElementById("btnRegistrar").style.display  = "none";
    document.getElementById("btnActualizar").style.display = "inline-block";

    // Cambiar color del encabezado de la tarjeta para indicar modo edición
    const header = document.getElementById("encabezadoFormulario");
    header.className = "card-header bg-warning text-dark fw-semibold";
    header.textContent = "Editando Producto ID #" + id;

    // Scroll suave al formulario DESPUÉS de que SweetAlert cierre
    window.scrollTo({ top: 0, behavior: "smooth" });
}

function limpiarFormulario() {
    document.getElementById("id").value       = "";
    document.getElementById("Codigo").value   = "";
    document.getElementById("Producto").value = "";
    document.getElementById("Precio").value   = "";
    document.getElementById("Cantidad").value = "";

    // Restaurar botones
    document.getElementById("btnRegistrar").style.display  = "inline-block";
    document.getElementById("btnActualizar").style.display = "none";

    // Restaurar encabezado a estado original
    const header = document.getElementById("encabezadoFormulario");
    header.className = "card-header bg-primary text-white fw-semibold";
    header.textContent = "Registro de Producto";
}

function limpiarBusqueda() {
    document.getElementById("campoBuscar").value = "";
    ListarProductos();
}

async function eliminarProducto(id) {

    // Confirmación antes de eliminar
    const confirmacion = await Swal.fire({
        icon:              "warning",
        title:             "¿Eliminar producto?",
        text:              `Esta acción no se puede deshacer. ID #${id}`,
        showCancelButton:  true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText:  "Cancelar",
        confirmButtonColor: "#f44747",
        cancelButtonColor:  "#3c3c3c"
    });

    // Si el usuario canceló, no hacer nada
    if (!confirmacion.isConfirmed) return;

    const formData = new FormData();
    formData.append("Accion", "Eliminar");
    formData.append("id", id);

    Swal.fire({
        title: "Eliminando...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch(URL_REGISTRAR, {
            method: "POST",
            body:   formData
        });

        if (!response.ok) throw new Error("HTTP " + response.status);

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon:              "success",
                title:             "Eliminado",
                text:              data.message,
                timer:             2000,
                showConfirmButton: false
            });
            limpiarFormulario();
            renderizarTabla(data.tabla);
        } else {
            Swal.fire({
                icon:  "error",
                title: "Error",
                text:  data.message
            });
        }

    } catch (error) {
        Swal.fire({
            icon:  "error",
            title: "Error de conexión",
            text:  error.message
        });
    }
}

function mostrarErrores(mensaje, errores = []) {
    const listaHTML = errores.length > 0
        ? "<ul class='text-start'>" + errores.map(e => `<li>${e}</li>`).join("") + "</ul>"
        : "";

    Swal.fire({
        icon:  "error",
        title: mensaje,
        html:  listaHTML || "Ocurrió un error inesperado."
    });
}

function escapar(texto) {
    return String(texto).replace(/\\/g, "\\\\").replace(/'/g, "\\'");
}