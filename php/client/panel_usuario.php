<?php
session_start();
$nombre = $_SESSION['usuario']['nombre'] ?? 'Usuario';
?>


<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Panel de Usuario</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../Assets/css/admin/panel_usuario.css">
    </head>

    <body>
        <main class="main-landing">
            <section class="card" role="dialog" aria-labelledby="titulo-panel">
                <h1 id="titulo-panel">Panel de Usuario</h1>
                <p>Bienvenido, <strong><?php echo htmlspecialchars($nombre); ?></strong></p>
                <div class="panel-actions" role="group" aria-label="Acciones de usuario">
                    <a class="btn btn-primary" href="/Tarea 2 BD - MySQL y PHP/php/client/mis_solicitudes.php">Mis solicitudes</a>
                    <a class="btn btn-primary" href="/Tarea 2 BD - MySQL y PHP/php/client/crear_solicitud.php">Crear solicitud</a>
                    <a class="btn btn-primary" href="/Tarea 2 BD - MySQL y PHP/php/client/perfil_usuario.php">Perfil</a>
                </div>
                <div style="margin-top:22px;">
                    <a class="btn btn-secondary" href="../auth/logout.php">Cerrar sesión</a>
                </div>
            </section>
        </main>
    </body>
</html>
