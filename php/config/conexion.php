<?php
// ============================================
// 🔗 CONEXIÓN PDO A INFINITYFREE (Render + PHP)
// ============================================

// Credenciales del hosting InfinityFree
$host = 'sql202.infinityfree.com';
$dbname = 'if0_40211976_tarea2_bd';
$user = 'if0_40211976';
$password = 'I7s63xBSAYy';
$charset = 'utf8mb4';

// Crear cadena DSN para PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Configuración PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Errores con excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Datos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Sentencias preparadas reales
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    // echo "✅ Conexión establecida correctamente a InfinityFree";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>
