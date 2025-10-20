<?php
session_start();

// Proteger ruta: requiere sesión de ingeniero
if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'ingeniero') {
    header('Location: ../Main/login.php');
    exit;
}

$especialidades = [];
try {
    require_once '../config/conexion.php';
} catch (Throwable $e) {
    // Si falla la conexión, continuamos mostrando el perfil básico
}

$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre'] ?? '');
$rut    = htmlspecialchars($usuario['rut'] ?? '');
$email  = htmlspecialchars($usuario['email'] ?? '');
$rol    = htmlspecialchars($usuario['rol'] ?? 'ingeniero');

// Cargar especialidades del ingeniero
if (isset($pdo) && !empty($usuario['rut'])) {
    try {
        $stmt = $pdo->prepare('SELECT e.nombre_especialidad 
                               FROM ingeniero_especialidad ie 
                               JOIN especialidad e ON e.id_especialidad = ie.id_especialidad 
                               WHERE ie.rut_ingeniero = ? 
                               ORDER BY e.nombre_especialidad');
        $stmt->execute([$usuario['rut']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $especialidades = array_map(function($r){ return $r['nombre_especialidad']; }, $rows);
    } catch (Throwable $e) {
        $especialidades = [];
    }
}

$especialidadesStr = '—';
if (!empty($especialidades)) {
    $especialidadesStr = implode(', ', array_map(function($n){ return htmlspecialchars($n, ENT_QUOTES, 'UTF-8'); }, $especialidades));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perfil Ingeniero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../Assets/css/client/perfil_usuario.css">
</head>
<body>
<?php
// Navbar de ingeniero
include './nav-bar_ingeniero.php';
?>

<main class="perfil-wrapper">
    <section class="perfil-card">
        <header class="perfil-header">
            <h1 style="margin:0;">Mi Perfil</h1>
            <span class="badge">Ingeniero</span>
        </header>

        <div class="perfil-grid">
            <div><strong>Nombre:</strong> <?php echo $nombre; ?></div>
            <div><strong>RUT:</strong> <?php echo $rut; ?></div>
            <div><strong>Email:</strong> <?php echo $email; ?></div>
            <div><strong>Rol:</strong> <?php echo $rol; ?></div>
            <div><strong>Especialidades:</strong> <?php echo $especialidadesStr; ?></div>
        </div>

        <div class="perfil-actions">
            <a class="btn" href="./panel_ingeniero.php">Ir a Panel</a>
            <a class="btn" href="../auth/logout.php">Cerrar Sesión</a>
        </div>
    </section>
</main>
</body>
</html>
