<?php

class DB {
    // Configuración de la base de datos
    private static $host     = "localhost";
    private static $dbname   = "productosdb";
    private static $user     = "root";
    private static $password = "";
    

    // Instancia única (patrón Singleton)
    private static $instancia = null;

    // Objeto PDO que representa la conexión
    private $conexion;

    /**
     * Constructor privado: impide crear instancias desde fuera.
     * Crea la conexión PDO con charset UTF-8 y modo de errores EXCEPTION.
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8";
            $this->conexion = new PDO($dsn, self::$user, self::$password);
            // Activa excepciones en caso de error SQL
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Devuelve filas como arrays asociativos por defecto
            $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Devuelve JSON de error y detiene la ejecución
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Error de conexión: " . $e->getMessage()]);
            exit;
        }
    }

    /*
     * obtenerInstancia()
     * Retorna la única instancia de DB (Singleton).
     * Si no existe, la crea.
     */
    public static function obtenerInstancia(): DB {
        if (self::$instancia === null) {
            self::$instancia = new DB();
        }
        return self::$instancia;
    }

    /*
     * insertSeguro()
     * Ejecuta un INSERT con parámetros enlazados para prevenir inyección SQL.
     */
    public function insertSeguro(string $sql, array $datos): int {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($datos);
        return (int) $this->conexion->lastInsertId();
    }

    /*
     * updateSeguro()
     * Ejecuta un UPDATE con parámetros enlazados para prevenir inyección SQL.
     */
    public function updateSeguro(string $sql, array $datos): int {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($datos);
        return $stmt->rowCount();
    }

    /*
     * query()
     * Ejecuta una consulta SELECT con parámetros opcionales enlazados.
     */
    public function query(string $sql, array $params = []): array {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
