<?php
session_start();
require_once '../config/conexion.php';

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rut = $_SESSION['usuario']['rut'] ?? null;

if (!$type || !$id || !in_array($type, ['funcionalidad','error'])) {
  http_response_code(400);
  echo 'Parámetros inválidos';
  exit;
}

// Obtener datos actuales
if ($type === 'funcionalidad') {
  $sql = "
    SELECT f.*, e.nombre_estado AS estado
    FROM solicitud_funcionalidad f
    JOIN estado e ON e.id_estado = f.id_estado
    WHERE f.id_funcionalidad = ? AND f.rut_usuario = ?
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$id, $rut]);
  $sol = $stmt->fetch();
  // Criterios
  $critStmt = $pdo->prepare("SELECT id_criterio, descripcion_criterio FROM criterio_aceptacion WHERE id_funcionalidad = ?");
  $critStmt->execute([$id]);
  $criterios = $critStmt->fetchAll();
  // Ambientes y tópicos
  $ambientes = $pdo->query('SELECT id_ambiente, nombre_ambiente FROM ambiente_desarrollo ORDER BY nombre_ambiente')->fetchAll();
  $topicos = $pdo->query('SELECT id_topico, nombre_topico FROM topico ORDER BY nombre_topico')->fetchAll();
} else {
  $sql = "
    SELECT s.*, e.nombre_estado AS estado
    FROM solicitud_error s
    JOIN estado e ON e.id_estado = s.id_estado
    WHERE s.id_error = ? AND s.rut_usuario = ?
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$id, $rut]);
  $sol = $stmt->fetch();
  $criterios = [];
  $topicos = $pdo->query('SELECT id_topico, nombre_topico FROM topico ORDER BY nombre_topico')->fetchAll();
}

if (!$sol) {
  http_response_code(404);
  echo 'Solicitud no encontrada o no tienes permiso.';
  exit;
}
if ($sol['estado'] === 'En Progreso') {
  echo '<script>alert("No puedes modificar una solicitud en progreso."); window.location.href = "../Main/detalle_solicitud.php?type=' . $type . '&id=' . $id . '";</script>';
  exit;
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($type === 'funcionalidad') {
    $titulo = trim($_POST['titulo']);
    $resumen = trim($_POST['resumen']);
    $id_ambiente = (int)$_POST['ambiente'];
    $id_topico = (int)$_POST['topico'];
    // Criterios dinámicos
    $criteriosNuevos = [];
    foreach ($_POST as $key => $value) {
      if (strpos($key, 'criterio') === 0 && !empty(trim($value))) {
        $criteriosNuevos[] = trim($value);
      }
    }
    if (count($criteriosNuevos) < 3) {
      $mensaje = "Debes ingresar al menos 3 criterios.";
      $tipo_mensaje = "error";
    } else {
      try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE solicitud_funcionalidad SET titulo_funcionalidad=?, resumen_funcionalidad=?, id_ambiente=?, id_topico=? WHERE id_funcionalidad=? AND rut_usuario=?");
        $stmt->execute([$titulo, $resumen, $id_ambiente, $id_topico, $id, $rut]);
        // Eliminar criterios viejos y agregar nuevos
        $pdo->prepare("DELETE FROM criterio_aceptacion WHERE id_funcionalidad = ?")->execute([$id]);
        $stmtC = $pdo->prepare("INSERT INTO criterio_aceptacion (descripcion_criterio, id_funcionalidad) VALUES (?, ?)");
        foreach ($criteriosNuevos as $c) {
          $stmtC->execute([$c, $id]);
        }
        $pdo->commit();
        echo '<script>alert("Solicitud actualizada correctamente."); window.location.href = "../Main/detalle_solicitud.php?type=funcionalidad&id=' . $id . '";</script>';
        exit;
      } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = "Error al actualizar: " . $e->getMessage();
        $tipo_mensaje = "error";
      }
    }
  } else {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $id_topico = (int)$_POST['topico'];
    try {
      $stmt = $pdo->prepare("UPDATE solicitud_error SET titulo_error=?, descripcion_error=?, id_topico=? WHERE id_error=? AND rut_usuario=?");
      $stmt->execute([$titulo, $descripcion, $id_topico, $id, $rut]);
      echo '<script>alert("Solicitud actualizada correctamente."); window.location.href = "../Main/detalle_solicitud.php?type=error&id=' . $id . '";</script>';
      exit;
    } catch (PDOException $e) {
      $mensaje = "Error al actualizar: " . $e->getMessage();
      $tipo_mensaje = "error";
    }
  }
}
?>
<!doctype html>
<html lang="es">
    <head>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Actualizar solicitud</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../Assets/css/client/actualizar_solicitud.css">
   
    </head>
    
    <body>
        <?php include 'nav-bar_usuario.php'; ?>

        <main class="main-landing">
            <section class="card">
                <h1>Actualizar solicitud</h1>
                <?php if (isset($mensaje)): ?>
                    <div class="mensaje <?php echo $tipo_mensaje; ?>"> <?php echo htmlspecialchars($mensaje); ?> </div>
                <?php endif; ?>

                <form class="formulario" method="post">
                    <?php if ($type === 'funcionalidad'): ?>
                        <label for="titulo">Título</label>
                        <input class="input" type="text" name="titulo" required maxlength="100" value="<?php echo htmlspecialchars($sol['titulo_funcionalidad']); ?>">

                        <label for="resumen">Resumen</label>
                        <textarea class="input" name="resumen" required maxlength="150" rows="3"><?php echo htmlspecialchars($sol['resumen_funcionalidad']); ?></textarea>

                        <label for="ambiente">Ambiente</label>
                        <select class="input" name="ambiente" required>
                            <option value="">Selecciona un ambiente...</option>
                            <?php foreach ($ambientes as $amb): ?>
                            <option value="<?php echo $amb['id_ambiente']; ?>" <?php if ($sol['id_ambiente'] == $amb['id_ambiente']) echo 'selected'; ?>><?php echo htmlspecialchars($amb['nombre_ambiente']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="topico">Tópico</label>
                        <select class="input" name="topico" required>
                            <option value="">Selecciona un tópico...</option>
                            <?php foreach ($topicos as $top): ?>
                            <option value="<?php echo $top['id_topico']; ?>" <?php if ($sol['id_topico'] == $top['id_topico']) echo 'selected'; ?>><?php echo htmlspecialchars($top['nombre_topico']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label>Criterios de aceptación</label>
                        <div id="criterios-container">
                            <?php $i = 1; foreach ($criterios as $c): ?>
                            <div class="criterio-item">
                                <input class="input" type="text" name="criterio<?php echo $i; ?>" required value="<?php echo htmlspecialchars($c['descripcion_criterio']); ?>" placeholder="Criterio <?php echo $i; ?>">
                                <?php if ($i > 3): ?><button type="button" class="btn-remove-criterio" onclick="this.parentElement.remove()">-</button><?php endif; ?>
                            </div>
                            <?php $i++; endforeach; ?>
                        </div>

                        <button type="button" class="btn-add-criterio" id="btn-agregar">+</button>
                        <?php else: ?>

                        <label for="titulo">Título</label>
                        <input class="input" type="text" name="titulo" required maxlength="100" value="<?php echo htmlspecialchars($sol['titulo_error']); ?>">

                        <label for="descripcion">Descripción</label>
                        <textarea class="input" name="descripcion" required maxlength="200" rows="4"><?php echo htmlspecialchars($sol['descripcion_error']); ?></textarea>

                        <label for="topico">Tópico</label>
                        <select class="input" name="topico" required>
                            <option value="">Selecciona un tópico...</option>
                            <?php foreach ($topicos as $top): ?>
                            <option value="<?php echo $top['id_topico']; ?>" <?php if ($sol['id_topico'] == $top['id_topico']) echo 'selected'; ?>><?php echo htmlspecialchars($top['nombre_topico']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <button class="btn" type="submit">Guardar cambios</button>
                    <a class="btn" href="../Main/detalle_solicitud.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>">Cancelar</a>
                </form>
            </section>
        </main>


        <script>
            let criterioCount = <?php echo count($criterios); ?>;
            document.getElementById('btn-agregar')?.addEventListener('click', function() {
            criterioCount++;
            const container = document.getElementById('criterios-container');
            const criterioDiv = document.createElement('div');
            criterioDiv.className = 'criterio-item';
            criterioDiv.innerHTML = `
                <input class="input" type="text" name="criterio${criterioCount}" placeholder="Criterio ${criterioCount}">
                <button type="button" class="btn-remove-criterio" onclick="this.parentElement.remove()">-</button>
            `;
            container.appendChild(criterioDiv);
            });
        </script>
    </body>
</html>
