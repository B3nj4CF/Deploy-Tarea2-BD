<?php
// ============================================
// 🔗 CONEXIÓN PDO A INFINITYFREE (MySQL remoto)
// ============================================

$host = 'sql202.infinityfree.com';          // Host del servidor MySQL
$dbname = 'if0_40211976_tarea2_bd';         // Nombre de tu base de datos
$user = 'if0_40211976';                     // Usuario de MySQL
$password = 'I7s63xBSAYy';                  // Contraseña
$charset = 'utf8mb4';

// Crear el DSN (cadena de conexión)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Configuración de PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    // echo "✅ Conexión exitosa a InfinityFree";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>
