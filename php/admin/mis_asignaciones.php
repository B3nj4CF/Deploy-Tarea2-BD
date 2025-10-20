<?php
session_start();
require_once '../config/conexion.php';

// Verificar que sea ingeniero
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'ingeniero') {
    header('Location: ../Main/login.php');
    exit;
}

$rut_ingeniero = $_SESSION['usuario']['rut'];

// Obtener funcionalidades asignadas
$sqlFunc = "
    SELECT 
        f.id_funcionalidad,
        f.titulo_funcionalidad,
        f.resumen_funcionalidad,
        f.fecha_publicacion,
        e.nombre_estado,
        t.nombre_topico,
        a.nombre_ambiente,
        u.nombre_usuario,
        af.id_asignacion_funcionalidad
    FROM asignar_funcionalidad af
    JOIN solicitud_funcionalidad f ON af.id_funcionalidad = f.id_funcionalidad
    JOIN estado e ON f.id_estado = e.id_estado
    JOIN topico t ON f.id_topico = t.id_topico
    LEFT JOIN ambiente_desarrollo a ON f.id_ambiente = a.id_ambiente
    JOIN usuario u ON f.rut_usuario = u.rut_usuario
    WHERE af.rut_ingeniero = ?
    ORDER BY f.fecha_publicacion DESC
";
$stmtFunc = $pdo->prepare($sqlFunc);
$stmtFunc->execute([$rut_ingeniero]);
$funcionalidades = $stmtFunc->fetchAll();

// Obtener errores asignados
$sqlError = "
    SELECT 
        s.id_error,
        s.titulo_error,
        s.descripcion_error,
        s.fecha_publicacion,
        e.nombre_estado,
        t.nombre_topico,
        u.nombre_usuario,
        ae.id_asignacion_error
    FROM asignar_error ae
    JOIN solicitud_error s ON ae.id_error = s.id_error
    JOIN estado e ON s.id_estado = e.id_estado
    JOIN topico t ON s.id_topico = t.id_topico
    JOIN usuario u ON s.rut_usuario = u.rut_usuario
    WHERE ae.rut_ingeniero = ?
    ORDER BY s.fecha_publicacion DESC
";
$stmtError = $pdo->prepare($sqlError);
$stmtError->execute([$rut_ingeniero]);
$errores = $stmtError->fetchAll();
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mis Asignaciones - Ingeniero</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../Assets/css/admin/mis_asignaciones.css">
    </head>
    <body>
        <?php include 'nav-bar_ingeniero.php'; ?>

        <main class="main-landing">
            <div class="contenedor">
                <h1>Mis Asignaciones</h1>

                <!-- Sección de Funcionalidades Asignadas -->
                <section class="seccion-solicitudes">
                    <h2>📋 Funcionalidades Asignadas (<?php echo count($funcionalidades); ?>)</h2>
                    
                    <?php if (empty($funcionalidades)): ?>
                        <p class="mensaje-vacio">No tienes funcionalidades asignadas.</p>
                    <?php else: ?>
                        <div class="lista-asignaciones">
                            <?php foreach ($funcionalidades as $func): ?>
                                <article class="card-asignacion">
                                    <div class="card-header">
                                        <h3><?php echo htmlspecialchars($func['titulo_funcionalidad']); ?></h3>
                                        <span class="badge estado-<?php echo strtolower(str_replace(' ', '-', $func['nombre_estado'])); ?>">
                                            <?php echo htmlspecialchars($func['nombre_estado']); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="resumen"><?php echo htmlspecialchars($func['resumen_funcionalidad']); ?></p>
                                    
                                    <div class="card-info">
                                        <div class="info-item">
                                            <span class="label">Tópico:</span>
                                            <span class="valor"><?php echo htmlspecialchars($func['nombre_topico']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Ambiente:</span>
                                            <span class="valor"><?php echo htmlspecialchars($func['nombre_ambiente'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Solicitado por:</span>
                                            <span class="valor"><?php echo htmlspecialchars($func['nombre_usuario']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Fecha:</span>
                                            <span class="valor"><?php echo date('d/m/Y', strtotime($func['fecha_publicacion'])); ?></span>
                                        </div>
                                    </div>

                                    <div class="card-actions">
                                        <a href="../Main/detalle_solicitud.php?type=funcionalidad&id=<?php echo $func['id_funcionalidad']; ?>&from=mis_asignaciones" class="btn-ver">Ver Detalles</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Sección de Errores Asignados -->
                <section class="seccion-solicitudes">
                    <h2>🐛 Errores Asignados (<?php echo count($errores); ?>)</h2>
                    
                    <?php if (empty($errores)): ?>
                        <p class="mensaje-vacio">No tienes errores asignados.</p>
                    <?php else: ?>
                        <div class="lista-asignaciones">
                            <?php foreach ($errores as $error): ?>
                                <article class="card-asignacion">
                                    <div class="card-header">
                                        <h3><?php echo htmlspecialchars($error['titulo_error']); ?></h3>
                                        <span class="badge estado-<?php echo strtolower(str_replace(' ', '-', $error['nombre_estado'])); ?>">
                                            <?php echo htmlspecialchars($error['nombre_estado']); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="resumen"><?php echo htmlspecialchars($error['descripcion_error']); ?></p>
                                    
                                    <div class="card-info">
                                        <div class="info-item">
                                            <span class="label">Tópico:</span>
                                            <span class="valor"><?php echo htmlspecialchars($error['nombre_topico']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Reportado por:</span>
                                            <span class="valor"><?php echo htmlspecialchars($error['nombre_usuario']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Fecha:</span>
                                            <span class="valor"><?php echo date('d/m/Y', strtotime($error['fecha_publicacion'])); ?></span>
                                        </div>
                                    </div>

                                    <div class="card-actions">
                                        <a href="../Main/detalle_solicitud.php?type=error&id=<?php echo $error['id_error']; ?>&from=mis_asignaciones" class="btn-ver">Ver Detalles</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </body>
</html>
