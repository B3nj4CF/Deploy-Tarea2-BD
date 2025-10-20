<?php
session_start();

// Proteger ruta: requiere sesión
if (!isset($_SESSION['usuario'])) {
    header('Location: ../Main/login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre'] ?? '');
$rut    = htmlspecialchars($usuario['rut'] ?? '');
$email  = htmlspecialchars($usuario['email'] ?? '');
$rol    = htmlspecialchars($usuario['rol'] ?? 'usuario');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mi Perfil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../Assets/css/client/perfil_usuario.css">
</head>
<body>
<?php
// Navbar según rol
if ($rol === 'ingeniero') {
    include '../admin/nav-bar_ingeniero.php';
} else {
    include './nav-bar_usuario.php';
}
?>

<main class="perfil-wrapper">
    <section class="perfil-card">
        <header class="perfil-header">
            <h1 style="margin:0;">Mi Perfil</h1>
            <span class="badge"><?php echo $rol === 'ingeniero' ? 'Ingeniero' : 'Usuario'; ?></span>
        </header>

        <div class="perfil-grid">
            <div><strong>Nombre:</strong> <?php echo $nombre; ?></div>
            <div><strong>RUT:</strong> <?php echo $rut; ?></div>
            <div><strong>Email:</strong> <?php echo $email; ?></div>
            <div><strong>Rol:</strong> <?php echo $rol; ?></div>
        </div>

        <div class="perfil-actions">
            <?php if ($rol === 'ingeniero'): ?>
                <a class="btn" href="../admin/panel_ingeniero.php">Ir a Panel</a>
            <?php else: ?>
                <a class="btn" href="./panel_usuario.php">Ir a Panel</a>
            <?php endif; ?>
            <a class="btn" href="../auth/logout.php">Cerrar Sesión</a>
        </div>
    </section>
</main>
</body>
</html>
