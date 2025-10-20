<?php
session_start();
require_once '../config/conexion.php';

// Params: type = funcionalidad|error, id = int
$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$type || !$id || !in_array($type, ['funcionalidad','error'])) {
	http_response_code(400);
	echo 'Parámetros inválidos';
	exit;
}

// Opcional: restringir a solicitudes del usuario logueado
$rut = $_SESSION['usuario']['rut'] ?? null;
$rol = $_SESSION['usuario']['rol'] ?? 'usuario';

// Procesar acciones de reseñas (solo ingeniero)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rol === 'ingeniero') {
	$accion = $_POST['accion'] ?? '';
    
	if ($accion === 'agregar_resena') {
		$descripcion = trim($_POST['descripcion'] ?? '');
		if (!empty($descripcion)) {
			try {
				if ($type === 'funcionalidad') {
					$stmt = $pdo->prepare("INSERT INTO resena (descripcion, rut_ingeniero, id_funcionalidad) VALUES (?, ?, ?)");
					$stmt->execute([$descripcion, $rut, $id]);
				} else {
					$stmt = $pdo->prepare("INSERT INTO resena (descripcion, rut_ingeniero, id_error) VALUES (?, ?, ?)");
					$stmt->execute([$descripcion, $rut, $id]);
				}
				$mensajeResena = "Reseña agregada exitosamente.";
				$tipoMensajeResena = "success";
			} catch (PDOException $e) {
				$mensajeResena = "Error al agregar la reseña: " . $e->getMessage();
				$tipoMensajeResena = "error";
			}
		}
	} elseif ($accion === 'editar_resena') {
		$descripcion = trim($_POST['descripcion'] ?? '');
		$id_resena = (int)($_POST['id_resena'] ?? 0);
		if (!empty($descripcion) && $id_resena > 0) {
			try {
				$stmt = $pdo->prepare("UPDATE resena SET descripcion = ? WHERE id_resena = ? AND rut_ingeniero = ?");
				$stmt->execute([$descripcion, $id_resena, $rut]);
				$mensajeResena = "Reseña actualizada exitosamente.";
				$tipoMensajeResena = "success";
			} catch (PDOException $e) {
				$mensajeResena = "Error al actualizar la reseña: " . $e->getMessage();
				$tipoMensajeResena = "error";
			}
		}
	} elseif ($accion === 'eliminar_resena') {
		$id_resena = (int)($_POST['id_resena'] ?? 0);
		if ($id_resena > 0) {
			try {
				$stmt = $pdo->prepare("DELETE FROM resena WHERE id_resena = ? AND rut_ingeniero = ?");
				$stmt->execute([$id_resena, $rut]);
				$mensajeResena = "Reseña eliminada exitosamente.";
				$tipoMensajeResena = "success";
			} catch (PDOException $e) {
				$mensajeResena = "Error al eliminar la reseña: " . $e->getMessage();
				$tipoMensajeResena = "error";
			}
		}
	}
}

// Procesar cambio de estado (solo ingeniero)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado' && $rol === 'ingeniero') {
    $nuevo_estado = isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : 0;
    if ($nuevo_estado > 0) {
        try {
            if ($type === 'funcionalidad') {
                $stmt = $pdo->prepare("UPDATE solicitud_funcionalidad SET id_estado = ? WHERE id_funcionalidad = ?");
                $stmt->execute([$nuevo_estado, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE solicitud_error SET id_estado = ? WHERE id_error = ?");
                $stmt->execute([$nuevo_estado, $id]);
            }
            $mensajeEstado = "Estado actualizado correctamente.";
            $tipoMensajeEstado = "success";
        } catch (PDOException $e) {
            $mensajeEstado = "Error al cambiar estado: " . $e->getMessage();
            $tipoMensajeEstado = "error";
        }
    }
}

if ($type === 'funcionalidad') {
	$sql = "
		SELECT f.id_funcionalidad AS id, f.titulo_funcionalidad AS titulo, f.resumen_funcionalidad AS resumen,
					 f.fecha_publicacion, f.id_estado, e.nombre_estado AS estado,
					 t.nombre_topico AS topico, a.nombre_ambiente AS ambiente,
					 u.nombre_usuario AS solicitante, u.rut_usuario
		FROM solicitud_funcionalidad f
		JOIN estado e ON e.id_estado = f.id_estado
		JOIN topico t ON t.id_topico = f.id_topico
		LEFT JOIN ambiente_desarrollo a ON a.id_ambiente = f.id_ambiente
		JOIN usuario u ON u.rut_usuario = f.rut_usuario
		WHERE f.id_funcionalidad = ?
	";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$id]);
	$sol = $stmt->fetch();

	// Criterios de aceptación
	$critStmt = $pdo->prepare("SELECT descripcion_criterio FROM criterio_aceptacion WHERE id_funcionalidad = ?");
	$critStmt->execute([$id]);
	$criterios = $critStmt->fetchAll();
} else {
	$sql = "
		SELECT s.id_error AS id, s.titulo_error AS titulo, s.descripcion_error AS descripcion,
					 s.fecha_publicacion, s.id_estado, e.nombre_estado AS estado,
					 t.nombre_topico AS topico,
					 u.nombre_usuario AS solicitante, u.rut_usuario
		FROM solicitud_error s
		JOIN estado e ON e.id_estado = s.id_estado
		JOIN topico t ON t.id_topico = s.id_topico
		JOIN usuario u ON u.rut_usuario = s.rut_usuario
		WHERE s.id_error = ?
	";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$id]);
	$sol = $stmt->fetch();
	$criterios = [];
}

if (!$sol) {
	http_response_code(404);
	echo 'Solicitud no encontrada';
	exit;
}

// Cargar TODAS las reseñas de la solicitud (con datos del ingeniero)
$todasResenas = [];
if ($type === 'funcionalidad') {
	$stmtR = $pdo->prepare("SELECT r.*, i.nombre_ingeniero FROM resena r JOIN ingeniero i ON r.rut_ingeniero = i.rut_ingeniero WHERE r.id_funcionalidad = ? ORDER BY r.fecha_publicacion DESC");
	$stmtR->execute([$id]);
} else {
	$stmtR = $pdo->prepare("SELECT r.*, i.nombre_ingeniero FROM resena r JOIN ingeniero i ON r.rut_ingeniero = i.rut_ingeniero WHERE r.id_error = ? ORDER BY r.fecha_publicacion DESC");
	$stmtR->execute([$id]);
}
$todasResenas = $stmtR->fetchAll();

// Filtrar reseñas del ingeniero actual si es ingeniero
$resenasIngeniero = [];
if ($rol === 'ingeniero') {
	$resenasIngeniero = array_filter($todasResenas, fn($r) => $r['rut_ingeniero'] === $rut);
}


// Cargar todos los estados para el dropdown (si es ingeniero)
$estados = [];
if ($rol === 'ingeniero') {
    $stmtEstados = $pdo->query("SELECT id_estado, nombre_estado FROM estado ORDER BY id_estado");
    $estados = $stmtEstados->fetchAll();
}

?>
<!doctype html>
<html lang="es">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Detalle de solicitud</title>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="../../Assets/css/main/detalle_solicitud.css">
	</head>
	<body>
		<?php 
			// Navbar según rol
			$rol = $_SESSION['usuario']['rol'] ?? 'usuario';
			if ($rol === 'ingeniero') {
				include '../admin/nav-bar_ingeniero.php';
			} else {
				include '../client/nav-bar_usuario.php';
			}
		?>

		<main class="main-landing">
			<section class="card">
				<?php if (isset($mensajeResena) && !empty($mensajeResena)): ?>
					<div class="mensaje <?php echo $tipoMensajeResena; ?>" id="mensajeResena">
						<?php echo htmlspecialchars($mensajeResena); ?>
					</div>
				<?php endif; ?>

				<?php if (isset($mensajeEstado) && !empty($mensajeEstado)): ?>
					<div class="mensaje <?php echo $tipoMensajeEstado; ?>" id="mensajeEstado">
						<?php echo htmlspecialchars($mensajeEstado); ?>
					</div>
				<?php endif; ?>

				<header class="header">
					<h1><?php echo htmlspecialchars($sol['titulo']); ?></h1>
					<span class="badge estado"><?php echo htmlspecialchars($sol['estado']); ?></span>
				</header>

				<div class="meta">
					<div><strong>Tipo:</strong> <?php echo $type === 'funcionalidad' ? 'Funcionalidad' : 'Error'; ?></div>
					<div><strong>Tópico:</strong> <?php echo htmlspecialchars($sol['topico']); ?></div>
					<?php if ($type === 'funcionalidad'): ?>
						<div><strong>Ambiente:</strong> <?php echo htmlspecialchars($sol['ambiente'] ?? '—'); ?></div>
					<?php endif; ?>
					<div><strong>Estado:</strong> <?php echo htmlspecialchars($sol['estado']); ?></div>
					<div><strong>Fecha:</strong> <?php echo htmlspecialchars($sol['fecha_publicacion']); ?></div>
					<div><strong>Solicitante:</strong> <?php echo htmlspecialchars($sol['solicitante']); ?> (<?php echo htmlspecialchars($sol['rut_usuario']); ?>)</div>
				</div>

				<?php if ($type === 'funcionalidad'): ?>
					<h2>Resumen</h2>
					<p class="texto"><?php echo nl2br(htmlspecialchars($sol['resumen'])); ?></p>

					<?php if (!empty($criterios)): ?>
						<h2>Criterios de aceptación</h2>
						<ul class="criterios">
							<?php foreach ($criterios as $c): ?>
								<li><?php echo htmlspecialchars($c['descripcion_criterio']); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
			<?php else: ?>
				<h2>Descripción</h2>
				<p class="texto"><?php echo nl2br(htmlspecialchars($sol['descripcion'])); ?></p>
			<?php endif; ?>

			<?php if ($rol === 'ingeniero'): ?>
				<!-- Mis reseñas (con CRUD) -->
				<?php if (!empty($resenasIngeniero)): ?>
					<div class="resenas-section">
						<h2>Mis Reseñas</h2>
						<?php foreach ($resenasIngeniero as $resena): ?>
							<div class="resena-item" id="resena-<?php echo $resena['id_resena']; ?>">
								<p class="resena-texto"><?php echo nl2br(htmlspecialchars($resena['descripcion'])); ?></p>
								<div class="resena-meta">
									<small class="resena-fecha"><?php echo date('d/m/Y H:i', strtotime($resena['fecha_publicacion'])); ?></small>
									<div class="resena-acciones">
										<button class="btn-sm btn-editar" onclick="editarResena(<?php echo (int)$resena['id_resena']; ?>, <?php echo json_encode($resena['descripcion']); ?>)">✏️ Editar</button>
										<form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta reseña?');">
											<input type="hidden" name="accion" value="eliminar_resena">
											<input type="hidden" name="id_resena" value="<?php echo $resena['id_resena']; ?>">
											<button type="submit" class="btn-sm btn-eliminar">🗑️ Eliminar</button>
										</form>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<!-- Formulario para agregar reseña -->
				<div class="agregar-resena-section">
					<h2>Agregar Reseña</h2>
					<form method="post" class="form-agregar-resena">
						<input type="hidden" name="accion" value="agregar_resena">
						<textarea name="descripcion" placeholder="Escribe tus observaciones aquí..." required rows="4" maxlength="500"></textarea>
						<button type="submit" class="btn-agregar-resena">Agregar Reseña</button>
					</form>
				</div>

				<!-- Formulario para cambiar estado -->
				<div class="cambiar-estado-section">
					<h2>Cambiar Estado</h2>
					<form method="post" class="form-estado">
						<input type="hidden" name="accion" value="cambiar_estado">
						<label for="id_estado">Nuevo estado:</label>
						<select name="id_estado" id="id_estado" required>
							<?php foreach ($estados as $est): ?>
								<option value="<?php echo $est['id_estado']; ?>" <?php echo ($est['id_estado'] == $sol['id_estado']) ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($est['nombre_estado']); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="btn-cambiar-estado">Actualizar Estado</button>
					</form>
				</div>
			<?php else: ?>
				<!-- Usuario: Ver TODAS las reseñas (solo lectura) -->
				<?php if (!empty($todasResenas)): ?>
					<div class="resenas-section">
						<h2>Reseñas de Ingenieros</h2>
						<?php foreach ($todasResenas as $resena): ?>
							<div class="resena-item">
								<div class="resena-header">
									<strong><?php echo htmlspecialchars($resena['nombre_ingeniero']); ?></strong>
								</div>
								<p class="resena-texto"><?php echo nl2br(htmlspecialchars($resena['descripcion'])); ?></p>
								<small class="resena-fecha"><?php echo date('d/m/Y H:i', strtotime($resena['fecha_publicacion'])); ?></small>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php
					// Enlace de regreso dinámico
					$from = $_GET['from'] ?? '';
					$hrefVolver = '../client/mis_solicitudes.php';
					if ($rol === 'ingeniero') {
						if ($from === 'mis_asignaciones') {
							$hrefVolver = '../admin/mis_asignaciones.php';
						} elseif ($from === 'ver_proyectos') {
							$hrefVolver = '../admin/ver_proyectos.php';
						} else {
							// por defecto, ver proyectos para ingeniero
							$hrefVolver = '../admin/ver_proyectos.php';
						}
					}
				?>
				<div class="acciones">
					<a class="btn-regreso" href="<?php echo $hrefVolver; ?>">Volver</a>

                    <?php if ($sol['estado'] !== 'En Progreso' && isset($_SESSION['usuario']) && $sol['rut_usuario'] === $_SESSION['usuario']['rut']): ?>
                        
						<a class="btn-Actualizar" href="../client/actualizar_solicitud.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>">Actualizar solicitud</a>
                        <form method="post" action="detalle_solicitud.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>" style="display:inline;">
                            <input type="hidden" name="accion" value="eliminar">
                            <button class="btn-Eliminar" type="submit" onclick="return confirm('¿Seguro que deseas eliminar esta solicitud?');">Eliminar solicitud</button>
                        </form>

                    <?php endif; ?>
				</div>

                <?php
                // Lógica de eliminación
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
                    if ($sol['estado'] === 'En Progreso') {
                        echo '<script>alert("No puedes eliminar una solicitud en progreso."); window.location.href = "detalle_solicitud.php?type=' . $type . '&id=' . $id . '";</script>';
                        exit;
                    }
                    if ($type === 'funcionalidad') {
                        $del = $pdo->prepare("DELETE FROM solicitud_funcionalidad WHERE id_funcionalidad = ? AND rut_usuario = ?");
                        $del->execute([$id, $rut]);
                        // Eliminar criterios asociados
                        $pdo->prepare("DELETE FROM criterio_aceptacion WHERE id_funcionalidad = ?")->execute([$id]);
                    } else {
                        $del = $pdo->prepare("DELETE FROM solicitud_error WHERE id_error = ? AND rut_usuario = ?");
                        $del->execute([$id, $rut]);
                    }
                    echo '<script>alert("Solicitud eliminada correctamente."); window.location.href = "../client/mis_solicitudes.php";</script>';
                    exit;
                }
                ?>
			</section>
		</main>

		<!-- Modal para editar reseña (ingeniero) -->
		<?php if ($rol === 'ingeniero'): ?>
		<div id="modalEditar" class="modal">
			<div class="modal-content">
				<span class="close" onclick="cerrarModal()">&times;</span>
				<h3>Editar Reseña</h3>
				<form method="post" id="formEditarResena">
					<input type="hidden" name="accion" value="editar_resena">
					<input type="hidden" name="id_resena" id="idResenaEditar">
					<textarea name="descripcion" id="descripcionEditar" required rows="4" maxlength="500"></textarea>
					<div class="modal-actions">
						<button type="submit" class="btn-guardar">Guardar Cambios</button>
						<button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
					</div>
				</form>
			</div>
		</div>
		<?php endif; ?>

		<script>
			// Auto-ocultar mensajes
			const msgResena = document.getElementById('mensajeResena');
			if (msgResena) {
				setTimeout(() => {
					msgResena.classList.add('hide');
					setTimeout(() => msgResena.remove(), 300);
				}, 2200);
			}

			const msgEstado = document.getElementById('mensajeEstado');
			if (msgEstado) {
				setTimeout(() => {
					msgEstado.classList.add('hide');
					setTimeout(() => msgEstado.remove(), 300);
				}, 2200);
			}

			<?php if ($rol === 'ingeniero'): ?>
			function editarResena(id, texto) {
				document.getElementById('idResenaEditar').value = id;
				document.getElementById('descripcionEditar').value = texto;
				document.getElementById('modalEditar').style.display = 'block';
			}

			function cerrarModal() {
				document.getElementById('modalEditar').style.display = 'none';
			}

			window.onclick = function(event) {
				const modal = document.getElementById('modalEditar');
				if (event.target == modal) {
					cerrarModal();
				}
			}
			<?php endif; ?>
		</script>
	</body>
</html>
