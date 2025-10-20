<?php
require_once 'config/conexion.php';

try {
    $stmt = $pdo->query("SHOW TABLES");
    echo "<h3>✅ Conexión establecida correctamente a InfinityFree</h3>";
    echo "<pre>";
    print_r($stmt->fetchAll());
    echo "</pre>";
} catch (PDOException $e) {
    echo "❌ Error al listar tablas: " . $e->getMessage();
}
?>
