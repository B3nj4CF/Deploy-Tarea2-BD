<?php
require_once '../config/conexion.php';
// Cargar especialidades disponibles
try {
    $especialidades = $pdo->query('SELECT id_especialidad, nombre_especialidad FROM especialidad ORDER BY nombre_especialidad')->fetchAll();
} catch (Throwable $e) {
    $especialidades = [];
}
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Registro</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../Assets/css/main/register.css">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta http-equiv="Expires" content="0" />
    </head>

    <body>
        <main class="main-landing">
            <section class="card" role="dialog" aria-labelledby="titulo-registro">
                <h1 id="titulo-registro">Registro</h1>
                <form action="../auth/register_auth.php" method="post" autocomplete="on">
                    <label class="label" for="rut">RUT</label>
                    <input class="input" type="text" id="rut" name="rut" required maxlength="12" placeholder="Ej: 12.345.678-9">

                    <label class="label" for="nombre">Nombre Completo</label>
                    <input class="input" type="text" id="nombre" name="nombre" required maxlength="80" placeholder="Tu nombre completo">

                    <label class="label" for="email">Correo Electrónico</label>
                    <input class="input" type="email" id="email" name="email" required autocomplete="username" placeholder="correo@ejemplo.com">

                    <label class="label" for="password">Contraseña</label>
                    <input class="input" type="password" id="password" name="password" required autocomplete="new-password" minlength="6" placeholder="Crea una contraseña">

                    <label class="label">Tipo de Usuario</label>
                    <div style="display:flex; gap:18px; margin-bottom:8px; justify-content:center;">
                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                            <input type="radio" name="rol" value="usuario" required>
                            Usuario
                        </label>
                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                            <input type="radio" name="rol" value="ingeniero" required>
                            Ingeniero
                        </label>
                    </div>

                    <!-- Selección de especialidades (solo ingeniero) -->
                    <div id="especialidades-section" style="display:none; margin: 10px 0 16px;">
                        <label class="label" for="especialidades">Especialidades (máx. 2)</label>
                        <?php if (!empty($especialidades)): ?>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:10px; background: rgba(0,0,0,0.2); padding:12px; border-radius:10px;">
                                <?php foreach ($especialidades as $esp): ?>
                                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                        <input type="checkbox" name="especialidades[]" value="<?php echo (int)$esp['id_especialidad']; ?>" class="chk-especialidad">
                                        <span><?php echo htmlspecialchars($esp['nombre_especialidad']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small style="color:#9ca3af; display:block; margin-top:6px;">Puedes seleccionar hasta dos especialidades.</small>
                        <?php else: ?>
                            <div style="color:#9ca3af;">No hay especialidades registradas aún.</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Registrarme</button>
                        <a class="btn btn-secondary" href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
                    </div>
                </form>
            </section>
        </main>
        
        <script>
            // Mostrar/ocultar especialidades según rol
            const radiosRol = document.querySelectorAll('input[name="rol"]');
            const seccionEsp = document.getElementById('especialidades-section');
            const chkEsp = () => Array.from(document.querySelectorAll('.chk-especialidad'));

            function actualizarVisibilidadEspecialidades() {
                const seleccionado = document.querySelector('input[name="rol"]:checked');
                if (seleccionado && seleccionado.value === 'ingeniero') {
                    seccionEsp.style.display = 'block';
                } else {
                    seccionEsp.style.display = 'none';
                    // Desmarcar si cambia a usuario
                    chkEsp().forEach(c => c.checked = false);
                }
            }
            radiosRol.forEach(r => r.addEventListener('change', actualizarVisibilidadEspecialidades));
            actualizarVisibilidadEspecialidades();

            // Limitar a máximo 2 especialidades seleccionadas en frontend
            function limitarMaximoEspecialidades() {
                const seleccionados = chkEsp().filter(c => c.checked);
                if (seleccionados.length > 2) {
                    // desmarcar el último marcado
                    const ultimo = seleccionados.pop();
                    ultimo.checked = false;
                    alert('Solo puedes seleccionar hasta 2 especialidades.');
                }
            }
            chkEsp().forEach(c => c.addEventListener('change', limitarMaximoEspecialidades));
        </script>
    </body>
</html>
