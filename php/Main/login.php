<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../Assets/css/main/login.css">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta http-equiv="Expires" content="0" />
    </head>
    
    <body>
        <main class="main-landing">
            <section class="card" role="dialog" aria-labelledby="titulo-login">
                <h1 id="titulo-login">Iniciar Sesión</h1>
                <form action="../auth/login_auth.php" method="post" autocomplete="on">
                    <label class="label" for="email">Correo Electrónico</label>
                    <input class="input" type="email" id="email" name="email" required autocomplete="username" placeholder="correo@ejemplo.com">

                    <label class="label" for="password">Contraseña</label>
                    <input class="input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="Tu contraseña">

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Entrar</button>
                        <a class="btn btn-secondary" href="register.php">¿No tienes cuenta? Regístrate</a>
                    </div>
                </form>
            </section>
        </main>
    </body>
</html>
