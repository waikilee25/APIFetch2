<?php
header("Content-Type: application/json");

// Incluir la clase Producto y Conexion.php
require_once __DIR__ . "/Modelo/Productos.php";

$accion = $_POST["Accion"] ?? "";

switch ($accion) {

    // CASE: Guardar → INSERT nuevo producto
    case "Guardar":
        // Sanitizar y leer los campos del formulario
        $codigo   = htmlspecialchars(trim($_POST["Codigo"]   ?? ""));
        $producto = htmlspecialchars(trim($_POST["Producto"] ?? ""));
        $precio   = (float) ($_POST["Precio"]   ?? 0);
        $cantidad = (int)   ($_POST["Cantidad"] ?? 0);

        // Crear instancia de Producto con los datos recibidos
        $p = new Producto($codigo, $producto, $precio, $cantidad);

        // Llamar al método guardar() que valida e inserta
        $respuesta = $p->guardar();

        // Si fue exitoso, incluir la tabla actualizada en la respuesta
        if ($respuesta["success"]) {
            $respuesta["tabla"] = Producto::listar();
        }

        echo json_encode($respuesta);
        break;

    // Modificar: UPDATE producto existente
    case "Modificar":
        // Leer el ID junto con los demás campos
        $id       = (int)   ($_POST["id"]       ?? 0);
        $codigo   = htmlspecialchars(trim($_POST["Codigo"]   ?? ""));
        $producto = htmlspecialchars(trim($_POST["Producto"] ?? ""));
        $precio   = (float) ($_POST["Precio"]   ?? 0);
        $cantidad = (int)   ($_POST["Cantidad"] ?? 0);

        // Crear instancia con el ID para edición
        $p = new Producto($codigo, $producto, $precio, $cantidad, $id);

        // Llamar al método editar() que valida y actualiza
        $respuesta = $p->editar();

        // Si fue exitoso, incluir la tabla actualizada
        if ($respuesta["success"]) {
            $respuesta["tabla"] = Producto::listar();
        }

        echo json_encode($respuesta);
        break;

    // Buscar: SELECT con LIKE por código/nombre
    case "Buscar":
        $termino = trim($_POST["Termino"] ?? "");

        // Buscar es método estático, no necesita instancia
        $respuesta = Producto::buscar($termino);

        echo json_encode($respuesta);
        break;

    // Listar: SELECT todos los productos
    case "Listar":
        $productos = Producto::listar();

        echo json_encode([
            "success" => true,
            "message" => "Lista obtenida.",
            "accion"  => "Listar",
            "tabla"   => $productos
        ]);
        break;

    // Eliminar: DELETE producto por ID
    case "Eliminar":
        $id = (int) ($_POST["id"] ?? 0);

        $respuesta = Producto::eliminar($id);

        if ($respuesta["success"]) {
            $respuesta["tabla"] = Producto::listar();
        }

        echo json_encode($respuesta);
        break;

    default:
        echo json_encode([
            "success" => false,
            "message" => "Acción no reconocida: '$accion'",
            "accion"  => $accion,
            "errors"  => ["El campo 'Accion' es requerido y debe ser válido."]
        ]);
        break;
}