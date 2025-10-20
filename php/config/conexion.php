<?php
// ===============================
// 🔗 CONEXIÓN PDO CON VARIABLES DE ENTORNO (Render + InfinityFree)
// ===============================

$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASS');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    // echo "✅ Conexión correcta.";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>
