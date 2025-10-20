<?php
session_start();
require_once '../config/conexion.php';

// Verificar que sea ingeniero
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'ingeniero') {
    header('Location: ../Main/login.php');
    exit;
}

$rut_ingeniero = $_SESSION['usuario']['rut'];
$mensaje = '';
$tipo_mensaje = '';

// Helper para detectar estados cerrados/finalizados por nombre
function esEstadoCerrado($nombreEstado) {
    if (!$nombreEstado) return false;
    $n = mb_strtolower($nombreEstado, 'UTF-8');
    // coincide con "final", "finalizado", "cerrado"
    return (strpos($n, 'final') !== false) || (strpos($n, 'cerr') !== false);
}

// Obtener especialidades del ingeniero (por nombre en minúsculas)
$especialidadesIngeniero = [];
$stmtEsp = $pdo->prepare("SELECT LOWER(TRIM(e.nombre_especialidad)) AS nombre FROM ingeniero_especialidad ie JOIN especialidad e ON ie.id_especialidad = e.id_especialidad WHERE ie.rut_ingeniero = ?");
$stmtEsp->execute([$rut_ingeniero]);
foreach ($stmtEsp->fetchAll() as $row) {
    $especialidadesIngeniero[$row['nombre']] = true;
}

// Procesar asignaciones (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'asignar_func') {
            $id_funcionalidad = isset($_POST['id_funcionalidad']) ? (int)$_POST['id_funcionalidad'] : 0;
            if ($id_funcionalidad > 0) {
                // Estado, tópico y datos de la funcionalidad
                $stmt = $pdo->prepare("SELECT f.id_funcionalidad, e.nombre_estado, t.nombre_topico FROM solicitud_funcionalidad f JOIN estado e ON f.id_estado = e.id_estado JOIN topico t ON f.id_topico = t.id_topico WHERE f.id_funcionalidad = ?");
                $stmt->execute([$id_funcionalidad]);
                $func = $stmt->fetch();
                if (!$func) {
                    throw new Exception('Funcionalidad no encontrada.');
                }
                // Validar compatibilidad de especialidad con el tópico
                $topicoLower = mb_strtolower(trim($func['nombre_topico']), 'UTF-8');
                $enEspecialidad = isset($especialidadesIngeniero[$topicoLower]);

                // Ya asignado a mí
                $stmt = $pdo->prepare("SELECT 1 FROM asignar_funcionalidad WHERE id_funcionalidad = ? AND rut_ingeniero = ? LIMIT 1");
                $stmt->execute([$id_funcionalidad, $rut_ingeniero]);
                $yaAsignado = (bool)$stmt->fetchColumn();

                // Cupo actual (máximo 3)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM asignar_funcionalidad WHERE id_funcionalidad = ?");
                $stmt->execute([$id_funcionalidad]);
                $cupo = (int)$stmt->fetchColumn();

                // Total de asignaciones del ingeniero (límite 20 entre ambos tipos)
                $stmt = $pdo->prepare("SELECT (
                        (SELECT COUNT(*) FROM asignar_funcionalidad WHERE rut_ingeniero = ?) +
                        (SELECT COUNT(*) FROM asignar_error WHERE rut_ingeniero = ?)
                    ) AS total");
                $stmt->execute([$rut_ingeniero, $rut_ingeniero]);
                $totalAsignaciones = (int)$stmt->fetchColumn();

                if (!$enEspecialidad) {
                    $mensaje = 'No puedes asignarte: tu especialidad no coincide con el tópico ('.htmlspecialchars($func['nombre_topico']).').';
                    $tipo_mensaje = 'error';
                } elseif ($yaAsignado) {
                    $mensaje = 'Ya estás asignado a esta funcionalidad.';
                    $tipo_mensaje = 'info';
                } elseif (esEstadoCerrado($func['nombre_estado'])) {
                    $mensaje = 'Esta funcionalidad está cerrada/finalizada.';
                    $tipo_mensaje = 'error';
                } elseif ($cupo >= 3) {
                    $mensaje = 'El cupo de esta funcionalidad ya está completo.';
                    $tipo_mensaje = 'error';
                } elseif ($totalAsignaciones >= 20) {
                    $mensaje = 'Has alcanzado el máximo de 20 asignaciones.';
                    $tipo_mensaje = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO asignar_funcionalidad (id_funcionalidad, rut_ingeniero) VALUES (?, ?)");
                    $stmt->execute([$id_funcionalidad, $rut_ingeniero]);
                    $mensaje = 'Te asignaste correctamente a la funcionalidad.';
                    $tipo_mensaje = 'success';
                }
            }
        } elseif ($accion === 'asignar_error') {
            $id_error = isset($_POST['id_error']) ? (int)$_POST['id_error'] : 0;
            if ($id_error > 0) {
                // Estado, tópico y datos del error
                $stmt = $pdo->prepare("SELECT s.id_error, e.nombre_estado, t.nombre_topico FROM solicitud_error s JOIN estado e ON s.id_estado = e.id_estado JOIN topico t ON s.id_topico = t.id_topico WHERE s.id_error = ?");
                $stmt->execute([$id_error]);
                $err = $stmt->fetch();
                if (!$err) {
                    throw new Exception('Solicitud de error no encontrada.');
                }
                // Validar compatibilidad de especialidad con el tópico
                $topicoLower = mb_strtolower(trim($err['nombre_topico']), 'UTF-8');
                $enEspecialidad = isset($especialidadesIngeniero[$topicoLower]);

                // Ya asignado a mí
                $stmt = $pdo->prepare("SELECT 1 FROM asignar_error WHERE id_error = ? AND rut_ingeniero = ? LIMIT 1");
                $stmt->execute([$id_error, $rut_ingeniero]);
                $yaAsignado = (bool)$stmt->fetchColumn();

                // Cupo actual (máximo 3)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM asignar_error WHERE id_error = ?");
                $stmt->execute([$id_error]);
                $cupo = (int)$stmt->fetchColumn();

                // Total de asignaciones del ingeniero
                $stmt = $pdo->prepare("SELECT (
                        (SELECT COUNT(*) FROM asignar_funcionalidad WHERE rut_ingeniero = ?) +
                        (SELECT COUNT(*) FROM asignar_error WHERE rut_ingeniero = ?)
                    ) AS total");
                $stmt->execute([$rut_ingeniero, $rut_ingeniero]);
                $totalAsignaciones = (int)$stmt->fetchColumn();

                if (!$enEspecialidad) {
                    $mensaje = 'No puedes asignarte: tu especialidad no coincide con el tópico ('.htmlspecialchars($err['nombre_topico']).').';
                    $tipo_mensaje = 'error';
                } elseif ($yaAsignado) {
                    $mensaje = 'Ya estás asignado a este error.';
                    $tipo_mensaje = 'info';
                } elseif (esEstadoCerrado($err['nombre_estado'])) {
                    $mensaje = 'Este error está cerrado/finalizado.';
                    $tipo_mensaje = 'error';
                } elseif ($cupo >= 3) {
                    $mensaje = 'El cupo de esta solicitud de error ya está completo.';
                    $tipo_mensaje = 'error';
                } elseif ($totalAsignaciones >= 20) {
                    $mensaje = 'Has alcanzado el máximo de 20 asignaciones.';
                    $tipo_mensaje = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO asignar_error (id_error, rut_ingeniero) VALUES (?, ?)");
                    $stmt->execute([$id_error, $rut_ingeniero]);
                    $mensaje = 'Te asignaste correctamente al error.';
                    $tipo_mensaje = 'success';
                }
            }
        }
    } catch (PDOException $e) {
        $mensaje = 'Error al asignar: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener todas las solicitudes de funcionalidad
$sqlFunc = "
    SELECT 
        f.id_funcionalidad,
        f.titulo_funcionalidad,
        f.resumen_funcionalidad,
        f.fecha_publicacion,
        e.nombre_estado,
        t.nombre_topico,
        a.nombre_ambiente,
        u.nombre_usuario
    FROM solicitud_funcionalidad f
    JOIN estado e ON f.id_estado = e.id_estado
    JOIN topico t ON f.id_topico = t.id_topico
    JOIN ambiente_desarrollo a ON f.id_ambiente = a.id_ambiente
    JOIN usuario u ON f.rut_usuario = u.rut_usuario
    ORDER BY f.fecha_publicacion DESC
";
$stmtFunc = $pdo->query($sqlFunc);
$funcionalidades = $stmtFunc->fetchAll();

// Prefetch: conteo de cupos y si ya estoy asignado (funcionalidades)
$asignacionesFuncCount = [];
$stmt = $pdo->query("SELECT id_funcionalidad, COUNT(*) cnt FROM asignar_funcionalidad GROUP BY id_funcionalidad");
foreach ($stmt->fetchAll() as $row) {
    $asignacionesFuncCount[(int)$row['id_funcionalidad']] = (int)$row['cnt'];
}
$asignadasPorMiFunc = [];
$stmt = $pdo->prepare("SELECT id_funcionalidad FROM asignar_funcionalidad WHERE rut_ingeniero = ?");
$stmt->execute([$rut_ingeniero]);
foreach ($stmt->fetchAll() as $row) {
    $asignadasPorMiFunc[(int)$row['id_funcionalidad']] = true;
}

// Obtener todas las solicitudes de error
$sqlError = "
    SELECT 
        s.id_error,
        s.titulo_error,
        s.descripcion_error,
        s.fecha_publicacion,
        e.nombre_estado,
        t.nombre_topico,
        u.nombre_usuario
    FROM solicitud_error s
    JOIN estado e ON s.id_estado = e.id_estado
    JOIN topico t ON s.id_topico = t.id_topico
    JOIN usuario u ON s.rut_usuario = u.rut_usuario
    ORDER BY s.fecha_publicacion DESC
";
$stmtError = $pdo->query($sqlError);
$errores = $stmtError->fetchAll();

// Prefetch: conteo de cupos y si ya estoy asignado (errores)
$asignacionesErrorCount = [];
$stmt = $pdo->query("SELECT id_error, COUNT(*) cnt FROM asignar_error GROUP BY id_error");
foreach ($stmt->fetchAll() as $row) {
    $asignacionesErrorCount[(int)$row['id_error']] = (int)$row['cnt'];
}
$asignadasPorMiError = [];
$stmt = $pdo->prepare("SELECT id_error FROM asignar_error WHERE rut_ingeniero = ?");
$stmt->execute([$rut_ingeniero]);
foreach ($stmt->fetchAll() as $row) {
    $asignadasPorMiError[(int)$row['id_error']] = true;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ver Proyectos - Ingeniero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../Assets/css/admin/ver_proyectos.css">
</head>
<body>
    <?php include 'nav-bar_ingeniero.php'; ?>

    <main class="main-landing">
        <div class="contenedor">
            <h1>Todas las Solicitudes del Sistema</h1>

            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo htmlspecialchars($tipo_mensaje); ?>" id="mensaje">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Sección de Funcionalidades -->
            <section class="seccion-solicitudes">
                <h2>📋 Solicitudes de Funcionalidad (<?php echo count($funcionalidades); ?>)</h2>
                
                <?php if (empty($funcionalidades)): ?>
                    <p class="mensaje-vacio">No hay solicitudes de funcionalidad registradas.</p>
                <?php else: ?>
                    <div class="grid-solicitudes">
                        <?php foreach ($funcionalidades as $func): ?>
                            <article class="card-solicitud">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($func['titulo_funcionalidad']); ?></h3>
                                    <span class="badge estado-<?php echo strtolower(str_replace(' ', '-', $func['nombre_estado'])); ?>">
                                        <?php echo htmlspecialchars($func['nombre_estado']); ?>
                                    </span>
                                </div>
                                
                                <p class="resumen"><?php echo htmlspecialchars($func['resumen_funcionalidad']); ?></p>
                                
                                <div class="card-info">
                                    <div class="info-item">
                                        <span class="label">Tópico:</span>
                                        <span class="valor"><?php echo htmlspecialchars($func['nombre_topico']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Ambiente:</span>
                                        <span class="valor"><?php echo htmlspecialchars($func['nombre_ambiente']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Solicitado por:</span>
                                        <span class="valor"><?php echo htmlspecialchars($func['nombre_usuario']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Fecha:</span>
                                        <span class="valor"><?php echo date('d/m/Y', strtotime($func['fecha_publicacion'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="card-actions">
                                    <a href="../Main/detalle_solicitud.php?type=funcionalidad&id=<?php echo $func['id_funcionalidad']; ?>&from=ver_proyectos" class="btn-ver">Ver Detalles</a>
                                    <?php
                                        $idF = (int)$func['id_funcionalidad'];
                                        $cupo = $asignacionesFuncCount[$idF] ?? 0;
                                        $ya = isset($asignadasPorMiFunc[$idF]);
                                        $cerrado = esEstadoCerrado($func['nombre_estado']);
                                        $matchEspecialidad = isset($especialidadesIngeniero[mb_strtolower(trim($func['nombre_topico']), 'UTF-8')]);
                                        if ($ya) {
                                            echo '<button class="btn-asignar" disabled>Asignado a mí</button>';
                                        } elseif (!$matchEspecialidad) {
                                            echo '<button class="btn-asignar" disabled>Fuera de especialidad</button>';
                                        } elseif ($cerrado) {
                                            echo '<button class="btn-asignar" disabled>Cerrado</button>';
                                        } elseif ($cupo >= 3) {
                                            echo '<button class="btn-asignar" disabled>Cupo lleno</button>';
                                        } else {
                                            echo '<form method="post" style="margin:0;">
                                                    <input type="hidden" name="accion" value="asignar_func">
                                                    <input type="hidden" name="id_funcionalidad" value="'.$idF.'">
                                                    <button type="submit" class="btn-asignar">Asignarme</button>
                                                  </form>';
                                        }
                                    ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Sección de Errores -->
            <section class="seccion-solicitudes">
                <h2>🐛 Solicitudes de Gestión de Errores (<?php echo count($errores); ?>)</h2>
                
                <?php if (empty($errores)): ?>
                    <p class="mensaje-vacio">No hay solicitudes de error registradas.</p>
                <?php else: ?>
                    <div class="grid-solicitudes">
                        <?php foreach ($errores as $error): ?>
                            <article class="card-solicitud">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($error['titulo_error']); ?></h3>
                                    <span class="badge estado-<?php echo strtolower(str_replace(' ', '-', $error['nombre_estado'])); ?>">
                                        <?php echo htmlspecialchars($error['nombre_estado']); ?>
                                    </span>
                                </div>
                                
                                <p class="resumen"><?php echo htmlspecialchars($error['descripcion_error']); ?></p>
                                
                                <div class="card-info">
                                    <div class="info-item">
                                        <span class="label">Tópico:</span>
                                        <span class="valor"><?php echo htmlspecialchars($error['nombre_topico']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Reportado por:</span>
                                        <span class="valor"><?php echo htmlspecialchars($error['nombre_usuario']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Fecha:</span>
                                        <span class="valor"><?php echo date('d/m/Y', strtotime($error['fecha_publicacion'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="card-actions">
                                    <a href="../Main/detalle_solicitud.php?type=error&id=<?php echo $error['id_error']; ?>&from=ver_proyectos" class="btn-ver">Ver Detalles</a>
                                    <?php
                                        $idE = (int)$error['id_error'];
                                        $cupoE = $asignacionesErrorCount[$idE] ?? 0;
                                        $yaE = isset($asignadasPorMiError[$idE]);
                                        $cerradoE = esEstadoCerrado($error['nombre_estado']);
                                        $matchEspecialidadE = isset($especialidadesIngeniero[mb_strtolower(trim($error['nombre_topico']), 'UTF-8')]);
                                        if ($yaE) {
                                            echo '<button class="btn-asignar" disabled>Asignado a mí</button>';
                                        } elseif (!$matchEspecialidadE) {
                                            echo '<button class="btn-asignar" disabled>Fuera de especialidad</button>';
                                        } elseif ($cerradoE) {
                                            echo '<button class="btn-asignar" disabled>Cerrado</button>';
                                        } elseif ($cupoE >= 3) {
                                            echo '<button class="btn-asignar" disabled>Cupo lleno</button>';
                                        } else {
                                            echo '<form method="post" style="margin:0;">
                                                    <input type="hidden" name="accion" value="asignar_error">
                                                    <input type="hidden" name="id_error" value="'.$idE.'">
                                                    <button type="submit" class="btn-asignar">Asignarme</button>
                                                  </form>';
                                        }
                                    ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <script>
        // Auto-ocultar mensajes de feedback
        const msg = document.getElementById('mensaje');
        if (msg) {
            setTimeout(() => {
                msg.classList.add('hide');
                setTimeout(() => msg.remove(), 300);
            }, 2200);
        }
    </script>
</body>
</html>
