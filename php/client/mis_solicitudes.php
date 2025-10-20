<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario'])) {
	header('Location: ../Main/login.php');
	exit;
}

$rut = $_SESSION['usuario']['rut'];

// Funcionalidades del usuario (titulo, resumen, topico nombre, ambiente nombre, estado nombre, fecha)
$sqlFunc = "
	SELECT f.id_funcionalidad, f.titulo_funcionalidad, f.resumen_funcionalidad, f.fecha_publicacion,
				 t.nombre_topico AS topico, a.nombre_ambiente AS ambiente, e.nombre_estado AS estado
	FROM solicitud_funcionalidad f
	JOIN topico t ON t.id_topico = f.id_topico
	LEFT JOIN ambiente_desarrollo a ON a.id_ambiente = f.id_ambiente
	JOIN estado e ON e.id_estado = f.id_estado
	WHERE f.rut_usuario = ?
	ORDER BY f.fecha_publicacion DESC, f.id_funcionalidad DESC
";
$stmtF = $pdo->prepare($sqlFunc);
$stmtF->execute([$rut]);
$funcs = $stmtF->fetchAll();

// Errores del usuario (titulo, descripcion, topico nombre, fecha, estado)
$sqlErr = "
	SELECT s.id_error, s.titulo_error, s.descripcion_error, s.fecha_publicacion,
				 t.nombre_topico AS topico, e.nombre_estado AS estado
	FROM solicitud_error s
	JOIN topico t ON t.id_topico = s.id_topico
	JOIN estado e ON e.id_estado = s.id_estado
	WHERE s.rut_usuario = ?
	ORDER BY s.fecha_publicacion DESC, s.id_error DESC
";
$stmtE = $pdo->prepare($sqlErr);
$stmtE->execute([$rut]);
$errs = $stmtE->fetchAll();
?>

<!doctype html>
<html lang="es">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Mis solicitudes</title>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="../../Assets/css/client/mis_solicitudes.css">
	</head>
	<body>
		<?php include 'nav-bar_usuario.php'; ?>

		<main class="main-landing">
			<section class="content">
				<h1>Mis solicitudes</h1>

				<div class="section">
					<h2>Funcionalidades</h2>
					<?php if (empty($funcs)): ?>
						<div class="empty">Aún no has creado funcionalidades.</div>
					<?php else: ?>
						<div class="cards">
						<?php foreach ($funcs as $f): ?>
                                <article class="card">

								<header>
									<h3><?php echo htmlspecialchars($f['titulo_funcionalidad']); ?></h3>
									<span class="badge estado"><?php echo htmlspecialchars($f['estado']); ?></span>
								</header>
								<p class="resumen"><?php echo htmlspecialchars($f['resumen_funcionalidad']); ?></p>
								<div class="meta">
									<span><strong>Tópico:</strong> <?php echo htmlspecialchars($f['topico']); ?></span>
									<span><strong>Ambiente:</strong> <?php echo htmlspecialchars($f['ambiente'] ?? '—'); ?></span>
									<span><strong>Fecha:</strong> <?php echo htmlspecialchars($f['fecha_publicacion']); ?></span>
								</div>

                                <div>
                                    <a class="btn-detalle" style="margin-top:8px; width:100%; text-align:center;" href="/Tarea 2 BD - MySQL y PHP/php/Main/detalle_solicitud.php?type=funcionalidad&id=<?php echo (int)$f['id_funcionalidad']; ?>">Ver detalle</a>
                                </div>
							</article>
						<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="section">
					<h2>Errores</h2>
					<?php if (empty($errs)): ?>
						<div class="empty">Aún no has reportado errores.</div>
					<?php else: ?>
						<div class="cards">
						<?php foreach ($errs as $e): ?>
								<article class="card">

								<header>
									<h3><?php echo htmlspecialchars($e['titulo_error']); ?></h3>
									<span class="badge estado"><?php echo htmlspecialchars($e['estado']); ?></span>
								</header>

								<p class="resumen"><?php echo htmlspecialchars($e['descripcion_error']); ?></p>
								<div class="meta">
									<span><strong>Tópico:</strong> <?php echo htmlspecialchars($e['topico']); ?></span>
									<span><strong>Fecha:</strong> <?php echo htmlspecialchars($e['fecha_publicacion']); ?></span>
								</div>

                                <div>
                                    <a class="btn-detalle" href="/Tarea 2 BD - MySQL y PHP/php/Main/detalle_solicitud.php?type=error&id=<?php echo (int)$e['id_error']; ?>">Ver detalle</a>
                                </div>
							</article>
						<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

			</section>
		</main>
	</body>
</html>
