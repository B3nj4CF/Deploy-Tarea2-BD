-- Este archivo selecciona todo el contenido de las tablas para la revisión
-- Imprime cada tabla con un encabezado indicando el nombre de la tabla

SELECT '===== Tabla: usuario =====' AS info;
SELECT * FROM usuario;

SELECT '===== Tabla: ingeniero =====' AS info;
SELECT * FROM ingeniero;

SELECT '===== Tabla: topico =====' AS info;
SELECT * FROM topico;

SELECT '===== Tabla: estado =====' AS info;
SELECT * FROM estado;

SELECT '===== Tabla: especialidad =====' AS info;
SELECT * FROM especialidad;

SELECT '===== Tabla: ambiente_desarrollo =====' AS info;
SELECT * FROM ambiente_desarrollo;

SELECT '===== Tabla: ingeniero_especialidad =====' AS info;
SELECT * FROM ingeniero_especialidad;

SELECT '===== Tabla: solicitud_funcionalidad =====' AS info;
SELECT * FROM solicitud_funcionalidad;

SELECT '===== Tabla: solicitud_error =====' AS info;
SELECT * FROM solicitud_error;

SELECT '===== Tabla: criterio_aceptacion =====' AS info;
SELECT * FROM criterio_aceptacion;

SELECT '===== Tabla: asignar_funcionalidad =====' AS info;
SELECT * FROM asignar_funcionalidad;

SELECT '===== Tabla: asignar_error =====' AS info;
SELECT * FROM asignar_error;