-- Datos sintéticos para desarrollo local. Se ejecuta solo al inicializar
-- un volumen MySQL vacío; no contiene cuentas utilizables ni datos reales.
USE tornalyx_db;

-- 100 participantes con contraseñas aleatorias no utilizables como acceso.
INSERT INTO usuarios (nombre, apellido, email, password, fecha_nac, rol, estado)
WITH RECURSIVE n AS (
  SELECT 1 AS i UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
SELECT CONCAT('Participante', i), 'Demo', CONCAT('participante', i, '@tornalyx.test'),
       CONCAT('$2y$10$', REPEAT('x', 53)), '2000-01-01', 'participante', 'activo'
FROM n;

-- 50 torneos de liga y 50 partidos con resultado para poblar pantallas.
INSERT INTO torneos (organizador_id, nombre, descripcion, disciplina, formato,
                     requiere_equipos, estado, max_participantes, publico, fecha_inicio)
WITH RECURSIVE n AS (
  SELECT 1 AS i UNION ALL SELECT i + 1 FROM n WHERE i < 50
)
SELECT (SELECT id FROM usuarios WHERE email = 'participante1@tornalyx.test'),
       CONCAT('Torneo Demo ', LPAD(i, 2, '0')), 'Fixture sintético de desarrollo.',
       'Futbol 5', 'liga', 1, 'en_curso', 2, 1, CURDATE()
FROM n;

INSERT INTO equipos (capitan_id, torneo_id, nombre, estado)
SELECT u.id, t.id, CONCAT(t.nombre, ' — Equipo A'), 'aprobado'
FROM torneos t
JOIN usuarios u ON u.email = CONCAT('participante', (t.id - (SELECT MIN(id) FROM torneos)) * 2 + 1, '@tornalyx.test')
WHERE t.nombre LIKE 'Torneo Demo %';
INSERT INTO equipos (capitan_id, torneo_id, nombre, estado)
SELECT u.id, t.id, CONCAT(t.nombre, ' — Equipo B'), 'aprobado'
FROM torneos t
JOIN usuarios u ON u.email = CONCAT('participante', (t.id - (SELECT MIN(id) FROM torneos)) * 2 + 2, '@tornalyx.test')
WHERE t.nombre LIKE 'Torneo Demo %';

INSERT INTO inscripciones (usuario_id, torneo_id, equipo_id, estado)
SELECT u.id, t.id, e.id, 'aprobada'
FROM torneos t
JOIN usuarios u ON u.email = CONCAT('participante', (t.id - (SELECT MIN(id) FROM torneos)) * 2 + 1, '@tornalyx.test')
JOIN equipos e ON e.torneo_id = t.id AND e.nombre = CONCAT(t.nombre, ' — Equipo A')
WHERE t.nombre LIKE 'Torneo Demo %';
INSERT INTO inscripciones (usuario_id, torneo_id, equipo_id, estado)
SELECT u.id, t.id, e.id, 'aprobada'
FROM torneos t
JOIN usuarios u ON u.email = CONCAT('participante', (t.id - (SELECT MIN(id) FROM torneos)) * 2 + 2, '@tornalyx.test')
JOIN equipos e ON e.torneo_id = t.id AND e.nombre = CONCAT(t.nombre, ' — Equipo B')
WHERE t.nombre LIKE 'Torneo Demo %';

INSERT INTO rondas (torneo_id, numero, nombre, estado)
SELECT id, 1, 'Ronda 1', 'finalizada' FROM torneos WHERE nombre LIKE 'Torneo Demo %';

INSERT INTO partidos (torneo_id, ronda_id, local_id, visitante_id, tipo_contendiente, estado, fecha_partido)
SELECT t.id, r.id,
       (SELECT id FROM equipos WHERE torneo_id = t.id AND nombre = CONCAT(t.nombre, ' — Equipo A')),
       (SELECT id FROM equipos WHERE torneo_id = t.id AND nombre = CONCAT(t.nombre, ' — Equipo B')),
       'equipo', 'finalizado', NOW()
FROM torneos t JOIN rondas r ON r.torneo_id = t.id AND r.numero = 1
WHERE t.nombre LIKE 'Torneo Demo %';

INSERT INTO resultados (partido_id, goles_local, goles_visitante, ganador_id, cargado_por)
SELECT p.id, MOD(p.id, 4), MOD(p.id + 1, 4),
       CASE WHEN MOD(p.id, 4) > MOD(p.id + 1, 4) THEN p.local_id
            WHEN MOD(p.id, 4) < MOD(p.id + 1, 4) THEN p.visitante_id ELSE NULL END,
       t.organizador_id
FROM partidos p JOIN torneos t ON t.id = p.torneo_id
WHERE t.nombre LIKE 'Torneo Demo %';

INSERT INTO posiciones (torneo_id, contendiente_id, tipo, pj, pg, pe, pp, gf, gc, dg, pts)
SELECT p.torneo_id, p.local_id, 'equipo', 1,
       IF(r.goles_local > r.goles_visitante, 1, 0), IF(r.goles_local = r.goles_visitante, 1, 0),
       IF(r.goles_local < r.goles_visitante, 1, 0), r.goles_local, r.goles_visitante,
       r.goles_local - r.goles_visitante,
       IF(r.goles_local > r.goles_visitante, 3, IF(r.goles_local = r.goles_visitante, 1, 0))
FROM partidos p JOIN resultados r ON r.partido_id = p.id
JOIN torneos t ON t.id = p.torneo_id WHERE t.nombre LIKE 'Torneo Demo %';
INSERT INTO posiciones (torneo_id, contendiente_id, tipo, pj, pg, pe, pp, gf, gc, dg, pts)
SELECT p.torneo_id, p.visitante_id, 'equipo', 1,
       IF(r.goles_visitante > r.goles_local, 1, 0), IF(r.goles_local = r.goles_visitante, 1, 0),
       IF(r.goles_visitante < r.goles_local, 1, 0), r.goles_visitante, r.goles_local,
       r.goles_visitante - r.goles_local,
       IF(r.goles_visitante > r.goles_local, 3, IF(r.goles_local = r.goles_visitante, 1, 0))
FROM partidos p JOIN resultados r ON r.partido_id = p.id
JOIN torneos t ON t.id = p.torneo_id WHERE t.nombre LIKE 'Torneo Demo %';

INSERT INTO avisos (torneo_id, autor_id, tipo, titulo, cuerpo)
SELECT id, organizador_id, 'resultado', CONCAT('Resultado cargado — ', nombre),
       'Aviso sintético de desarrollo.'
FROM torneos WHERE nombre LIKE 'Torneo Demo %';
