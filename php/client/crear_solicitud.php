<?php session_start(); ?>

<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Crear Solicitud</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../Assets/css/client/crear_solicitud.css">
    </head>
    
    <body>
        <?php include 'nav-bar_usuario.php'; ?>

        <main class="main-landing">
            <section class="card" role="dialog" aria-labelledby="titulo-crear">
                <h1 id="titulo-crear">¿Qué tipo de solicitud deseas crear?</h1>
                <div class="menu-actions" role="group" aria-label="Opciones de solicitud">
                    <a class="btn btn-primary" href="tipo Solicitudes/tipo_funcionalidad.php">Crear Funcionalidad</a>
                    <a class="btn btn-primary" href="tipo Solicitudes/tipo_error.php">Reportar Error</a>
                </div>
            </section>
        </main>
    </body>
</html>
