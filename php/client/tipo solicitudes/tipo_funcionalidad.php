<?php
session_start();
require_once '../../config/conexion.php';

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $resumen = trim($_POST['resumen']);
    $id_ambiente = (int)$_POST['ambiente'];
    $id_topico = (int)$_POST['topico'];
    $rut_usuario = $_SESSION['usuario']['rut'];
    $fecha_publicacion = date('Y-m-d');
    $id_estado = 1; // Estado "Abierto" por defecto
    
    // Recoger criterios dinámicos
    $criterios = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'criterio') === 0 && !empty(trim($value))) {
            $criterios[] = trim($value);
        }
    }
    
    // Validar que haya al menos 3 criterios
    if (count($criterios) < 3) {
        $mensaje = "Debes ingresar al menos 3 criterios de aceptación.";
        $tipo_mensaje = "error";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insertar solicitud de funcionalidad
            $stmt = $pdo->prepare("
                INSERT INTO solicitud_funcionalidad 
                (titulo_funcionalidad, resumen_funcionalidad, id_estado, id_topico, rut_usuario, id_ambiente, fecha_publicacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$titulo, $resumen, $id_estado, $id_topico, $rut_usuario, $id_ambiente, $fecha_publicacion]);
            
            $id_funcionalidad = $pdo->lastInsertId();
            
            // Insertar criterios de aceptación
            $stmt_criterio = $pdo->prepare("
                INSERT INTO criterio_aceptacion (descripcion_criterio, id_funcionalidad) 
                VALUES (?, ?)
            ");
            
            foreach ($criterios as $criterio) {
                $stmt_criterio->execute([$criterio, $id_funcionalidad]);
            }
            
            $pdo->commit();
            $mensaje = "Solicitud de funcionalidad creada exitosamente.";
            $tipo_mensaje = "success";
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $mensaje = "Ya existe una solicitud con ese título.";
            } else {
                $mensaje = "Error al crear la solicitud: " . $e->getMessage();
            }
            $tipo_mensaje = "error";
        }
    }
}

// Obtener ambientes
$ambientes = $pdo->query('SELECT id_ambiente, nombre_ambiente FROM ambiente_desarrollo ORDER BY nombre_ambiente')->fetchAll();
// Obtener tópicos
$topicos = $pdo->query('SELECT id_topico, nombre_topico FROM topico ORDER BY nombre_topico')->fetchAll();
?>

<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Solicitud de Funcionalidad</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../Assets/css/client/tipo_funcionalidad.css">
  </head>

  <body>
    <?php include '../nav-bar_usuario.php'; ?>

    <main class="main-landing">
      <section class="card" role="dialog" aria-labelledby="titulo-funcionalidad">
        <h1 id="titulo-funcionalidad">Solicitud de Funcionalidad</h1>

        <?php if (isset($mensaje)): ?>
          <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
          </div>
        <?php endif; ?>

        <form class="formulario" action="" method="post">
          <label class="label" for="titulo">Título de la Funcionalidad</label>
          <input class="input" type="text" id="titulo" name="titulo" required maxlength="100" placeholder="Nombre de la nueva función">

          <!-- Resumen -->
          <label class="label" for="resumen">Resumen</label>
          <textarea class="input" id="resumen" name="resumen" required maxlength="150" rows="3" placeholder="Explica brevemente de qué se trata"></textarea>

          <!-- Ambiente -->
          <label class="label" for="ambiente">Ambiente</label>
          <select class="input" id="ambiente" name="ambiente" required>
            <option value="">Selecciona un ambiente...</option>
            <?php foreach ($ambientes as $amb): ?>
              <option value="<?php echo htmlspecialchars($amb['id_ambiente']); ?>"><?php echo htmlspecialchars($amb['nombre_ambiente']); ?></option>
            <?php endforeach; ?>
          </select>

          <!-- Tópico -->
          <label class="label" for="topico">Tópico</label>
          <select class="input" id="topico" name="topico" required>
            <option value="">Selecciona un tópico...</option>
            <?php foreach ($topicos as $top): ?>
              <option value="<?php echo htmlspecialchars($top['id_topico']); ?>"><?php echo htmlspecialchars($top['nombre_topico']); ?></option>
            <?php endforeach; ?>
          </select>

          <!-- Criterios de Aceptación -->
          <label class="label">Criterios de Aceptación (mínimo 3)</label>
          <div id="criterios-container">
            <div class="criterio-item">
              <input class="input" type="text" name="criterio1" required placeholder="Criterio 1">
            </div>
            <div class="criterio-item">
              <input class="input" type="text" name="criterio2" required placeholder="Criterio 2">
            </div>
            <div class="criterio-item">
              <input class="input" type="text" name="criterio3" required placeholder="Criterio 3">
            </div>
          </div>
          <button type="button" class="btn-add-criterio" id="btn-agregar">+</button>

          <button class="btn" type="submit">Enviar Solicitud</button>
        </form>
      </section>
    </main>

    <!-- Scripts de los criterios extras -->
    <script>
      let criterioCount = 3;

      document.getElementById('btn-agregar').addEventListener('click', function() {
        criterioCount++;
        const container = document.getElementById('criterios-container');
        
        const criterioDiv = document.createElement('div');
        criterioDiv.className = 'criterio-item';
        criterioDiv.innerHTML = `
          <input class="input" type="text" name="criterio${criterioCount}" placeholder="Criterio ${criterioCount}">
          <button type="button" class="btn-remove-criterio" onclick="this.parentElement.remove()">-</button>`;
        container.appendChild(criterioDiv);
      });
    </script>
    <script>
      // Auto-ocultar mensajes después de 2s
      window.addEventListener('DOMContentLoaded', () => {
        const msg = document.querySelector('.mensaje');
        if (msg) {
          setTimeout(() => {
            msg.classList.add('hide');
            setTimeout(() => msg.remove(), 300);
          }, 2000);
        }
      });
    </script>
  </body>
</html>
