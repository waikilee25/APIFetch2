<?php
require_once __DIR__ . "/Modelo/conexion.php";

try {
    $db = DB::obtenerInstancia();
    echo "✅ Conexión exitosa a la base de datos.\n";

    $resultado = $db->query("SELECT * FROM productos");
    echo "Productos encontrados: " . count($resultado) . "\n";
    print_r($resultado);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}