<?php
require_once '../config/conexion.php';
session_start();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
	$_SESSION['error'] = 'Debes ingresar correo y contraseña.';
	header('Location: ../Main/login.php');
	exit;
}

// Buscar en usuario
$stmt = $pdo->prepare('SELECT rut_usuario AS rut, nombre_usuario AS nombre, email_usuario AS email, password_usuario AS password, "usuario" AS rol FROM usuario WHERE email_usuario = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

// Si no está en usuario, buscar en ingeniero
if (!$user) {
	$stmt = $pdo->prepare('SELECT rut_ingeniero AS rut, nombre_ingeniero AS nombre, email_ingeniero AS email, password_ingeniero AS password, "ingeniero" AS rol FROM ingeniero WHERE email_ingeniero = ?');
	$stmt->execute([$email]);
	$user = $stmt->fetch();
}

if (!$user || !password_verify($password, $user['password'])) {
	$_SESSION['error'] = 'Correo o contraseña incorrectos.';
	header('Location: ../Main/login.php');
	exit;
}

// Guardar datos en sesión
$_SESSION['usuario'] = [
	'rut' => $user['rut'],
	'nombre' => $user['nombre'],
	'email' => $user['email'],
	'rol' => $user['rol']
];

// Redirigir según rol
if ($user['rol'] === 'usuario') {
	header('Location: ../client/panel_usuario.php');
} else {
	header('Location: ../admin/panel_ingeniero.php');
}
exit;
?>
