<?php
require_once '../config/conexion.php';
session_start();
// Recoger datos del formulario
$rut = trim($_POST['rut'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'] ?? '';
// Validaciones básicas
if (!$rut || !$nombre || !$email || !$password || !$rol) {
	$_SESSION['error'] = 'Todos los campos son obligatorios.';
	header('Location: ../Main/register.php');
	exit;
}
// Validar email único en ambas tablas
$existe = false;
if ($rol === 'usuario') {
	$stmt = $pdo->prepare('SELECT 1 FROM usuario WHERE email_usuario = ? OR rut_usuario = ?');
	$stmt->execute([$email, $rut]);
	$existe = $stmt->fetch();
} else if ($rol === 'ingeniero') {
	$stmt = $pdo->prepare('SELECT 1 FROM ingeniero WHERE email_ingeniero = ? OR rut_ingeniero = ?');
	$stmt->execute([$email, $rut]);
	$existe = $stmt->fetch();
}
if ($existe) {
	$_SESSION['error'] = 'El correo o RUT ya está registrado.';
	header('Location: ../Main/register.php');
	exit;
}

// Encriptar contraseña
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
	if ($rol === 'usuario') {
		$stmt = $pdo->prepare('INSERT INTO usuario (rut_usuario, nombre_usuario, email_usuario, password_usuario) VALUES (?, ?, ?, ?)');
		$stmt->execute([$rut, $nombre, $email, $passwordHash]);
	} else if ($rol === 'ingeniero') {
		// Transacción para crear ingeniero y asociar especialidades
		$pdo->beginTransaction();
		$stmt = $pdo->prepare('INSERT INTO ingeniero (rut_ingeniero, nombre_ingeniero, email_ingeniero, password_ingeniero) VALUES (?, ?, ?, ?)');
		$stmt->execute([$rut, $nombre, $email, $passwordHash]);

		// Especialidades (opcional, máximo 2)
		$especialidades = $_POST['especialidades'] ?? [];
		if (!is_array($especialidades)) { $especialidades = []; }
		// Quitar duplicados y limpiar
		$especialidades = array_unique(array_map('intval', $especialidades));
		if (count($especialidades) > 2) {
			throw new Exception('Solo puedes seleccionar hasta 2 especialidades.');
		}
		if (count($especialidades) > 0) {
			// Validar que existan en la tabla especialidad
			$in = implode(',', array_fill(0, count($especialidades), '?'));
			$valida = $pdo->prepare("SELECT id_especialidad FROM especialidad WHERE id_especialidad IN ($in)");
			$valida->execute($especialidades);
			$validas = $valida->fetchAll(PDO::FETCH_COLUMN, 0);
			if (count($validas) !== count($especialidades)) {
				throw new Exception('Alguna especialidad no es válida.');
			}
			// Insertar asociaciones
			$ins = $pdo->prepare('INSERT INTO ingeniero_especialidad (id_especialidad, rut_ingeniero) VALUES (?, ?)');
			foreach ($especialidades as $idEsp) {
				$ins->execute([$idEsp, $rut]);
			}
		}
		$pdo->commit();
	} else {
		$_SESSION['error'] = 'Rol inválido.';
		header('Location: ../Main/register.php');
		exit;
	}
	// Iniciar sesión automáticamente
	if ($rol === 'usuario') {
		$_SESSION['usuario'] = [
			'rut' => $rut,
			'nombre' => $nombre,
			'email' => $email,
			'rol' => 'usuario'
		];
		header('Location: ../client/panel_usuario.php');
	} else {
		$_SESSION['usuario'] = [
			'rut' => $rut,
			'nombre' => $nombre,
			'email' => $email,
			'rol' => 'ingeniero'
		];
		header('Location: ../admin/panel_ingeniero.php');
	}
	exit;
} catch (Exception $e) {
	if ($rol === 'ingeniero' && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	$_SESSION['error'] = 'Error al registrar: ' . $e->getMessage();
	header('Location: ../Main/register.php');
	exit;
}
?>
