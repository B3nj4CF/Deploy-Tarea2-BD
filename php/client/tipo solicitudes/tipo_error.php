<?php
session_start();
require_once '../../config/conexion.php';

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $id_topico = (int)$_POST['topico'];
    $rut_usuario = $_SESSION['usuario']['rut'];
    $fecha_publicacion = date('Y-m-d');
    $id_estado = 1; // Estado "Abierto" por defecto
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO solicitud_error 
            (titulo_error, descripcion_error, fecha_publicacion, id_estado, id_topico, rut_usuario) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$titulo, $descripcion, $fecha_publicacion, $id_estado, $id_topico, $rut_usuario]);
        
        $mensaje = "Solicitud de error creada exitosamente.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            $mensaje = "Ya existe una solicitud con ese título.";
        } else {
            $mensaje = "Error al crear la solicitud: " . $e->getMessage();
        }
        $tipo_mensaje = "error";
    }
}

// Obtener tópicos
$topicos = $pdo->query('SELECT id_topico, nombre_topico FROM topico ORDER BY nombre_topico')->fetchAll();
?>

<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportar Error</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../Assets/css/client/tipo_error.css">
  </head>

  <body>
    <?php include '../nav-bar_usuario.php'; ?>
    <main class="main-landing">
      <section class="card" role="dialog" aria-labelledby="titulo-error">
        <h1 id="titulo-error">Solicitud de Gestión de Errores</h1>
        
        <?php if (isset($mensaje)): ?>
          <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
          </div>
        <?php endif; ?>
        
        <form class="formulario" action="" method="post">
          <label class="label" for="titulo">Título del Error</label>
          <input class="input" type="text" id="titulo" name="titulo" required maxlength="100" placeholder="Título corto del problema">

          <label class="label" for="descripcion">Descripción</label>
          <textarea class="input" id="descripcion" name="descripcion" required maxlength="200" rows="4" placeholder="Describe el error, qué hacías, qué pasó y qué esperabas"></textarea>

          <label class="label" for="topico">Tópico</label>
          <select class="input" id="topico" name="topico" required>
            <option value="">Selecciona un tópico...</option>
            <?php foreach ($topicos as $top): ?>
              <option value="<?php echo htmlspecialchars($top['id_topico']); ?>"><?php echo htmlspecialchars($top['nombre_topico']); ?></option>
            <?php endforeach; ?>
          </select>

          <button class="btn" type="submit">Enviar Solicitud</button>
        </form>
      </section>
    </main>
  </body>
</html>
<script>
  // Auto-ocultar mensajes después de 2s
  window.addEventListener('DOMContentLoaded', () => {
    const msg = document.querySelector('.mensaje');
    if (msg) {
      setTimeout(() => {
        msg.classList.add('hide');
        // Remover del DOM tras la animación
        setTimeout(() => msg.remove(), 300);
      }, 2000);
    }
  });
</script>
