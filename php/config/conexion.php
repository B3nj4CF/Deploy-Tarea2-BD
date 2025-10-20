<?php

// Credenciales para para el servidor local de XAMPP.
$host = 'localhost';         // Dirección del servidor de la base de datos.
$dbname = 'tarea2_bd';       // Nombre de la base de datos.
$user = 'root';              // Usuario por defecto de MySQL en XAMPP.
$password = '';              // La contraseña por defecto en XAMPP es vacía.
$charset = 'utf8mb4';        // Juego de caracteres para soportar emojis y caracteres especiales.

// El DSN es una cadena de texto que le dice a PDO a qué driver conectarse y los detalles de la conexión.
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// 3. Opciones de configuración para PDO
$options = [
    // Reporte de errores: Lanza excepciones en caso de un error en la BD.
    // Esto es mucho mejor que los warnings por defecto porque puedes capturar los errores.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    // Modo de obtención de datos: Devuelve los resultados como un array asociativo.
    // Esto significa que puedes acceder a las columnas por su nombre (ej: $fila['nombre_usuario']).
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // Desactiva la emulación de sentencias preparadas.
    // Esto asegura que estás usando sentencias preparadas nativas de MySQL, lo cual es más seguro.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. Crear la instancia de PDO (la conexión)
try {
    // Intentamos crear el objeto de conexión a la base de datos.
    // Esta variable '$pdo' será la que usarás en todos tus otros scripts para hacer consultas.
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    // Si algo sale mal durante la conexión (ej: contraseña incorrecta, la BD no existe),
    // se captura la excepción y se muestra un mensaje de error genérico.
    // En un entorno de producción, registrarías este error en un archivo en lugar de mostrarlo en pantalla.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>