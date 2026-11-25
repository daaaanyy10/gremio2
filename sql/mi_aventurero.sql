-- mi_aventurero.sql · Mi aventurero para el Gremio (UD5)
-- Usuario: alumno · Contraseña: Gremio2026
-- Hash bcrypt generado por mí (PASSWORD_DEFAULT / $2y$10$); la contraseña NO está en claro.
-- Para usar otra clave: docker compose exec www php -r "echo password_hash('TuClave', PASSWORD_DEFAULT), PHP_EOL;"
-- y pega aquí el resultado en lugar del hash.

INSERT INTO aventureros (usuario, nombre, clase, password_hash) VALUES
('alumno', 'Alumno <Aprendiz de DWES>', 'Explorador', '$2y$10$6bZFA0i3UpafHciDNsS1D.ZrTUucBiV27j12lX6qb91Fj2Ex/W5oC');
