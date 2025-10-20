-- =====================================
-- CONFIGURACIÓN INICIAL Y BORRADO DE TABLAS
-- =====================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS resena;
DROP TABLE IF EXISTS asignar_funcionalidad;
DROP TABLE IF EXISTS asignar_error;
DROP TABLE IF EXISTS criterio_aceptacion;
DROP TABLE IF EXISTS solicitud_funcionalidad;
DROP TABLE IF EXISTS solicitud_error;
DROP TABLE IF EXISTS ingeniero_especialidad;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS ingeniero;
DROP TABLE IF EXISTS topico;
DROP TABLE IF EXISTS estado;
DROP TABLE IF EXISTS especialidad;
DROP TABLE IF EXISTS ambiente_desarrollo;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================
-- TABLAS PRINCIPALES DEL PROYECTO
-- =====================================

CREATE TABLE IF NOT EXISTS usuario(
    rut_usuario VARCHAR(10) NOT NULL PRIMARY KEY,
    nombre_usuario VARCHAR(100) NOT NULL,
    email_usuario VARCHAR(100) NOT NULL UNIQUE,
    password_usuario VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS ingeniero(
    rut_ingeniero VARCHAR(10) NOT NULL PRIMARY KEY,
    nombre_ingeniero VARCHAR(100) NOT NULL,
    email_ingeniero VARCHAR(100) NOT NULL UNIQUE,
    password_ingeniero VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS topico(
    id_topico INT PRIMARY KEY AUTO_INCREMENT,
    nombre_topico VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS estado(
    id_estado INT PRIMARY KEY AUTO_INCREMENT,
    nombre_estado VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS ambiente_desarrollo(
    id_ambiente INT PRIMARY KEY AUTO_INCREMENT,
    nombre_ambiente VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS especialidad(
    id_especialidad INT PRIMARY KEY AUTO_INCREMENT,
    nombre_especialidad VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS ingeniero_especialidad (
    id_especialidad INT NOT NULL,
    rut_ingeniero VARCHAR(10) NOT NULL,
    PRIMARY KEY (id_especialidad, rut_ingeniero),
    FOREIGN KEY (id_especialidad) REFERENCES especialidad(id_especialidad),
    FOREIGN KEY (rut_ingeniero) REFERENCES ingeniero(rut_ingeniero)
);

CREATE TABLE IF NOT EXISTS solicitud_funcionalidad (
    id_funcionalidad INT PRIMARY KEY AUTO_INCREMENT,
    titulo_funcionalidad VARCHAR(100) NOT NULL UNIQUE,
    id_estado INT NOT NULL,
    resumen_funcionalidad VARCHAR(150) NOT NULL,
    id_topico INT NOT NULL,
    rut_usuario VARCHAR(10) NOT NULL,
    id_ambiente INT,
    fecha_publicacion DATE NOT NULL,
    FOREIGN KEY (id_estado) REFERENCES estado(id_estado),
    FOREIGN KEY (id_topico) REFERENCES topico(id_topico),
    FOREIGN KEY (rut_usuario) REFERENCES usuario(rut_usuario),
    FOREIGN KEY (id_ambiente) REFERENCES ambiente_desarrollo(id_ambiente)
);

CREATE TABLE IF NOT EXISTS solicitud_error (
    id_error INT PRIMARY KEY AUTO_INCREMENT,
    titulo_error VARCHAR(100) NOT NULL UNIQUE,
    descripcion_error VARCHAR(200) NOT NULL,
    fecha_publicacion DATE NOT NULL,
    id_estado INT NOT NULL,
    id_topico INT NOT NULL,
    rut_usuario VARCHAR(10) NOT NULL,
    FOREIGN KEY (id_estado) REFERENCES estado(id_estado),
    FOREIGN KEY (id_topico) REFERENCES topico(id_topico),
    FOREIGN KEY (rut_usuario) REFERENCES usuario(rut_usuario)
);

CREATE TABLE IF NOT EXISTS criterio_aceptacion(
    id_criterio INT PRIMARY KEY AUTO_INCREMENT,
    descripcion_criterio VARCHAR(200) NOT NULL,
    id_funcionalidad INT NOT NULL,
    FOREIGN KEY (id_funcionalidad) REFERENCES solicitud_funcionalidad(id_funcionalidad)
);

CREATE TABLE IF NOT EXISTS asignar_funcionalidad (
    id_asignacion_funcionalidad INT PRIMARY KEY AUTO_INCREMENT,
    rut_ingeniero VARCHAR(10) NOT NULL,
    id_funcionalidad INT NOT NULL,
    FOREIGN KEY (rut_ingeniero) REFERENCES ingeniero(rut_ingeniero),
    FOREIGN KEY (id_funcionalidad) REFERENCES solicitud_funcionalidad(id_funcionalidad)
);

CREATE TABLE IF NOT EXISTS asignar_error (
    id_asignacion_error INT PRIMARY KEY AUTO_INCREMENT,
    rut_ingeniero VARCHAR(10) NOT NULL,
    id_error INT NOT NULL,
    FOREIGN KEY (rut_ingeniero) REFERENCES ingeniero(rut_ingeniero),
    FOREIGN KEY (id_error) REFERENCES solicitud_error(id_error)
);

CREATE TABLE IF NOT EXISTS resena (
    id_resena INT PRIMARY KEY AUTO_INCREMENT,
    descripcion VARCHAR(500) NOT NULL,
    fecha_publicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rut_ingeniero VARCHAR(10) NOT NULL,
    id_funcionalidad INT NULL,
    id_error INT NULL,
    FOREIGN KEY (rut_ingeniero) REFERENCES ingeniero(rut_ingeniero),
    FOREIGN KEY (id_funcionalidad) REFERENCES solicitud_funcionalidad(id_funcionalidad),
    FOREIGN KEY (id_error) REFERENCES solicitud_error(id_error),
    CHECK (
        (id_funcionalidad IS NOT NULL AND id_error IS NULL)
        OR (id_funcionalidad IS NULL AND id_error IS NOT NULL)
    )
);

-- =====================================
-- TRIGGERS Y VALIDACIONES (SINTAXIS MYSQL)
-- =====================================

DELIMITER $$

-- MAX 2 ESPECIALIDADES POR INGENIERO 
CREATE TRIGGER trigger_validar_asignar_ingenieros
BEFORE INSERT ON ingeniero_especialidad
FOR EACH ROW
BEGIN
    DECLARE total_especialidades INT;
    SELECT COUNT(*) INTO total_especialidades
    FROM ingeniero_especialidad
    WHERE rut_ingeniero = NEW.rut_ingeniero;

    IF total_especialidades >= 2 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El ingeniero ya tiene asignadas 2 especialidades. No se puede agregar otra.';
    END IF;
END$$

-- MAX 20 ASIGNACIONES TOTALES POR INGENIERO 
CREATE TRIGGER trigger_validar_20_asignaciones_total_funcionalidad
BEFORE INSERT ON asignar_funcionalidad
FOR EACH ROW
BEGIN
    DECLARE total_asignaciones INT;
    SELECT 
        (SELECT COUNT(*) FROM asignar_funcionalidad WHERE rut_ingeniero = NEW.rut_ingeniero) +
        (SELECT COUNT(*) FROM asignar_error WHERE rut_ingeniero = NEW.rut_ingeniero)
    INTO total_asignaciones;

    IF total_asignaciones >= 20 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El ingeniero ya tiene asignadas 20 solicitudes en total. No se puede agregar otra.';
    END IF;
END$$

CREATE TRIGGER trigger_validar_20_asignaciones_total_error
BEFORE INSERT ON asignar_error
FOR EACH ROW
BEGIN
    DECLARE total_asignaciones INT;
    SELECT 
        (SELECT COUNT(*) FROM asignar_funcionalidad WHERE rut_ingeniero = NEW.rut_ingeniero) +
        (SELECT COUNT(*) FROM asignar_error WHERE rut_ingeniero = NEW.rut_ingeniero)
    INTO total_asignaciones;

    IF total_asignaciones >= 20 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El ingeniero ya tiene asignadas 20 solicitudes en total. No se puede agregar otra.';
    END IF;
END$$


-- MAX 3 INGENIEROS POR FUNCIONALIDAD 
CREATE TRIGGER trigger_validar_max_ingenieros_funcionalidad
BEFORE INSERT ON asignar_funcionalidad
FOR EACH ROW
BEGIN
    DECLARE total_ingenieros INT;
    SELECT COUNT(*) INTO total_ingenieros
    FROM asignar_funcionalidad
    WHERE id_funcionalidad = NEW.id_funcionalidad;

    IF total_ingenieros >= 3 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La funcionalidad ya tiene asignados 3 ingenieros. No se puede agregar otro.';
    END IF;
END$$

-- MAX 3 INGENIEROS POR ERROR 
CREATE TRIGGER trigger_validar_max_ingenieros_error
BEFORE INSERT ON asignar_error
FOR EACH ROW
BEGIN
    DECLARE total_ingenieros INT;
    SELECT COUNT(*) INTO total_ingenieros
    FROM asignar_error
    WHERE id_error = NEW.id_error;

    IF total_ingenieros >= 3 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El error ya tiene asignados 3 ingenieros. No se puede agregar otro.';
    END IF;
END$$

-- MAX 25 SOLICITUDES DE FUNCIONALIDAD POR USUARIO/DÍA 
CREATE TRIGGER trigger_max_funcionalidades_por_usuario_por_dia
BEFORE INSERT ON solicitud_funcionalidad
FOR EACH ROW
BEGIN
    DECLARE total_funcionalidades INT;
    SELECT COUNT(*) INTO total_funcionalidades
    FROM solicitud_funcionalidad
    WHERE rut_usuario = NEW.rut_usuario
      AND fecha_publicacion = NEW.fecha_publicacion;

    IF total_funcionalidades >= 25 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El usuario ya ha realizado 25 solicitudes de funcionalidad hoy.';
    END IF;
END$$

-- MAX 25 SOLICITUDES DE ERROR POR USUARIO/DÍA 
CREATE TRIGGER trigger_max_errores_por_usuario_por_dia
BEFORE INSERT ON solicitud_error
FOR EACH ROW
BEGIN
    DECLARE total_errores INT;
    SELECT COUNT(*) INTO total_errores
    FROM solicitud_error
    WHERE rut_usuario = NEW.rut_usuario
      AND fecha_publicacion = NEW.fecha_publicacion;

    IF total_errores >= 25 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El usuario ya ha realizado 25 solicitudes de error hoy.';
    END IF;
END$$

-- Validar criterios antes de cerrar o resolver funcionalidad
CREATE TRIGGER trigger_validar_cambio_estado_funcionalidad
BEFORE UPDATE ON solicitud_funcionalidad
FOR EACH ROW
BEGIN
    DECLARE numero_criterios INT;
    DECLARE nombre_estado_nuevo TEXT;
    
    SELECT nombre_estado INTO nombre_estado_nuevo
    FROM estado
    WHERE id_estado = NEW.id_estado;

    IF nombre_estado_nuevo IN ('Resuelto', 'Cerrado') THEN
        SELECT COUNT(*) INTO numero_criterios
        FROM criterio_aceptacion
        WHERE id_funcionalidad = NEW.id_funcionalidad;

        IF numero_criterios < 3 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La funcionalidad no puede cambiar de estado. Requiere al menos 3 criterios de aceptación.';
        END IF;
    END IF;
END$$

DELIMITER ;