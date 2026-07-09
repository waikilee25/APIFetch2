<?php

require_once __DIR__ . "/conexion.php";

class Producto {
    // Propiedades del producto (corresponden a columnas de la tabla)
    private int    $id;
    private string $codigo;
    private string $producto;
    private float  $precio;
    private int    $cantidad;

    // Instancia de la clase DB para ejecutar consultas
    private DB $db;

    /**
     * Constructor: recibe los datos del producto y obtiene la instancia de DB.
     * @param string $codigo
     * @param string $producto
     * @param float  $precio
     * @param int    $cantidad
     * @param int    $id       
     */
    public function __construct(
        string $codigo   = "",
        string $producto = "",
        float  $precio   = 0.0,
        int    $cantidad = 0,
        int    $id       = 0
    ) {
        $this->codigo   = trim($codigo);
        $this->producto = trim($producto);
        $this->precio   = $precio;
        $this->cantidad = $cantidad;
        $this->id       = $id;
        $this->db       = DB::obtenerInstancia();
    }

    /**
     * validar()
     * Valida que todos los campos obligatorios estén completos y con valores correctos.
     */
    public function validar(): array {
        $errores = [];

        if (empty($this->codigo)) {
            $errores[] = "El código es obligatorio.";
        }

        if (empty($this->producto)) {
            $errores[] = "El nombre del producto es obligatorio.";
        }

        if ($this->precio <= 0) {
            $errores[] = "El precio debe ser mayor a 0.";
        }

        if ($this->cantidad < 0) {
            $errores[] = "La cantidad no puede ser negativa.";
        }

        return $errores;
    }

    /*
     * guardar()
     * Inserta un nuevo producto en la base de datos.
     */
    public function guardar(): array {
        // Validación del servidor antes de insertar
        $errores = $this->validar();

        // Regla de negocio: al crear, mínimo 1 unidad
        if ($this->cantidad < 1) {
            $errores[] = "Al registrar un producto nuevo debe tener al menos 1 unidad en stock.";
        }

        if (!empty($errores)) {
            return [
                "success" => false,
                "message" => "No se pudo guardar el producto.",
                "accion"  => "Guardar",
                "errors"  => $errores
            ];
        }

        $sql = "INSERT INTO productos (codigo, producto, precio, cantidad)
                VALUES (:codigo, :producto, :precio, :cantidad)";

        $id = $this->db->insertSeguro($sql, [
            ":codigo"   => $this->codigo,
            ":producto" => $this->producto,
            ":precio"   => $this->precio,
            ":cantidad" => $this->cantidad
        ]);

        return [
            "success" => true,
            "message" => "Producto guardado correctamente con ID #$id.",
            "accion"  => "Guardar",
            "errors"  => []
        ];
    }

    /*
     * editar()
     * Actualiza un producto existente en la base de datos por su ID.
     */
    public function editar(): array {
        // Validar que se proporcionó un ID
        if ($this->id <= 0) {
            return [
                "success" => false,
                "message" => "ID inválido para editar.",
                "accion"  => "Modificar",
                "errors"  => ["Se requiere un ID válido para modificar el producto."]
            ];
        }

        // Validación de los campos del servidor
        $errores = $this->validar();

        if (!empty($errores)) {
            return [
                "success" => false,
                "message" => "No se pudo actualizar el producto.",
                "accion"  => "Modificar",
                "errors"  => $errores
            ];
        }

        $sql = "UPDATE productos
                SET codigo = :codigo, producto = :producto, precio = :precio, cantidad = :cantidad
                WHERE id = :id";

        $filas = $this->db->updateSeguro($sql, [
            ":codigo"   => $this->codigo,
            ":producto" => $this->producto,
            ":precio"   => $this->precio,
            ":cantidad" => $this->cantidad,
            ":id"       => $this->id
        ]);

        if ($filas === 0) {
            return [
                "success" => false,
                "message" => "No se encontró el producto con ID #{$this->id} o no hubo cambios.",
                "accion"  => "Modificar",
                "errors"  => []
            ];
        }

        return [
            "success" => true,
            "message" => "Producto ID #{$this->id} actualizado correctamente.",
            "accion"  => "Modificar",
            "errors"  => []
        ];
    }

    /*
     * eliminar()
     * Elimina un producto de la base de datos por su ID.
     */
    public static function eliminar(int $id): array {
        if ($id <= 0) {
            return [
                "success" => false,
                "message" => "ID inválido para eliminar.",
                "accion"  => "Eliminar",
                "errors"  => ["Se requiere un ID válido para eliminar el producto."]
            ];
        }

        $db  = DB::obtenerInstancia();
        $sql = "DELETE FROM productos WHERE id = :id";

        $filas = $db->updateSeguro($sql, [":id" => $id]);

        if ($filas === 0) {
            return [
                "success" => false,
                "message" => "No se encontró el producto con ID #$id.",
                "accion"  => "Eliminar",
                "errors"  => []
            ];
        }

        return [
            "success" => true,
            "message" => "Producto ID #$id eliminado correctamente.",
            "accion"  => "Eliminar",
            "errors"  => []
        ];
    }

    /*
     * buscar()
     * Busca productos cuyo código o nombre coincidan con el término dado.
     */
    public static function buscar(string $termino): array {
        $db = DB::obtenerInstancia();

        $termino = trim($termino);

        if (empty($termino)) {
            return [
                "success" => false,
                "message" => "Ingrese un término para buscar.",
                "accion"  => "Buscar",
                "datos"   => []
            ];
        }

        $like = "%" . $termino . "%";

        $sql = "SELECT * FROM productos
                WHERE codigo LIKE :termino OR producto LIKE :termino2
                ORDER BY id ASC";

        $resultados = $db->query($sql, [
            ":termino"  => $like,
            ":termino2" => $like
        ]);

        return [
            "success" => true,
            "message" => count($resultados) . " producto(s) encontrado(s).",
            "accion"  => "Buscar",
            "datos"   => $resultados
        ];
    }

    /**
     * listar()
     * Retorna todos los productos ordenados por ID.
     */
    public static function listar(): array {
        $db  = DB::obtenerInstancia();
        $sql = "SELECT * FROM productos ORDER BY id ASC";
        return $db->query($sql);
    }
}
