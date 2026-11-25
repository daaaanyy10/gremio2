# Memoria · Gremio de Aventureros

**Código de alumno/a:** EJEMPLO-001 (código ficticio)

## 1. Mi aventurero
- Usuario que he añadido a la tabla: `alumno` (contraseña `Gremio2026`; cámbiala si quieres usar la tuya).
- Comando o pasos que he usado para generar el hash:
  `docker compose exec www php -r "echo password_hash('Gremio2026', PASSWORD_DEFAULT), PHP_EOL;"`
  Copié el resultado (`$2y$10$...`) en `sql/mi_aventurero.sql` y lo importé desde phpMyAdmin.
  La contraseña nunca se guarda en claro: solo su hash bcrypt.

## 2. Parte 1 · match
Sí, he puesto `default` en `etiquetaRango()` y devuelve `'Desconocido'`. Si llega el rango `'Z'`,
`match` no tiene ningún brazo que coincida y, sin `default`, lanzaría `UnhandledMatchError`
(la página se rompería). Lo prefiero así porque un dato inesperado no tumba la web y el usuario
ve un texto razonable. En `claseRango()` el `default` devuelve cadena vacía (sin clase de color).

## 3. Parte 2 · Validación de la URL
Con `tablon.php?rango=Z&oro=hola` se muestran las 10 misiones, sin errores.
- `leerRangoFiltro()`: `in_array($rango, RANGOS, true)` falla con `Z` y devuelve `null` (= todos los rangos).
- `leerOroMinimo()`: `filter_var(..., FILTER_VALIDATE_INT, [min_range 0, max_range 10000])` devuelve
  `false` con `hola` y la función devuelve `0`.
Además compruebo `is_string()` por si llega `?rango[]=B` (un array).

## 4. Parte 3 · Sesión frente a cookie
- **`$_SESSION` (servidor):** el aventurero autenticado, el zurrón, el contador de visitas, la hora de
  entrada y el mensaje flash. Son datos que el usuario no debe poder modificar y que dependen de
  haber iniciado sesión. El navegador solo guarda el `PHPSESSID`.
- **Cookies (navegador):** el tema (`gremio_tema`) y el usuario recordado (`gremio_usuario`).
  Son preferencias no sensibles y pequeñas, y deben durar más que la sesión.
- **Por qué no al revés:** una cookie la controla el usuario (F12 → Cookies): si guardase en ella el
  zurrón o quién está autenticado, cualquiera podría falsificarlo. Y si el tema viviera en la sesión,
  se perdería al cerrarla, justo cuando queremos que se recuerde.
- **Qué sobrevive al cerrar sesión:** `cerrarSesion()` vacía `$_SESSION` y destruye la sesión (se
  pierde el zurrón), pero las cookies de preferencias no se tocan porque son del navegador, no de la
  sesión, y tienen su propia caducidad (1 año y 30 días).

## 5. Parte 3 · Seguridad del login
- **`' OR '1'='1` en el usuario:** no entra y sale «Usuario o contraseña incorrectos.». La consulta
  es preparada (`WHERE usuario = :usuario`): el texto viaja como dato, no como SQL, así que se busca
  un usuario que se llame literalmente `' OR '1'='1` y no existe. Además, aunque existiera, haría falta
  que `password_verify` diera `true`.
- **`session_regenerate_id(true)`:** genera un identificador de sesión nuevo al iniciar sesión y borra
  el antiguo. Evita la *session fixation* (que alguien fije un ID antes del login y lo reutilice después).
- **Mismo mensaje si falla usuario o contraseña:** si dijera «ese usuario no existe», un atacante
  podría averiguar qué usuarios son reales (enumeración de usuarios). Con un mensaje único no
  sabe cuál de los dos datos ha fallado.

## 6. Pruebas que he hecho
| Prueba | Resultado esperado | Resultado obtenido |
|---|---|---|
| Entrar en tablon.php sin login | Redirige al login con «solo para miembros» | Redirige a `login.php?motivo=privado` y muestra el aviso |
| Login con contraseña mala | «Usuario o contraseña incorrectos.» | Se muestra ese mensaje y no se entra |
| Usuario `' OR '1'='1` | Mismo mensaje de error | Mismo mensaje; no entra |
| Login con «Recordar» y salir | El login aparece con el usuario relleno | El campo usuario viene relleno y la casilla marcada |
| Aceptar la misma misión dos veces / F5 | No se duplica; F5 no reenvía | Aviso «Ya llevas…»; el zurrón no duplica; F5 no reenvía (PRG) |
| Recargar el tablón | El contador de visitas sube | Sube en cada recarga |
| Cambiar el tema y cerrar sesión | El tema se mantiene; el zurrón se vacía | El tema se mantiene; el zurrón queda vacío |
| Editar a mano `gremio_tema` | Tema claro y texto escapado | Usa el tema claro; el texto se pinta escapado con `e()` |
| `tablon.php?rango=Z&oro=hola` | Muestra las 10 misiones | Muestra las 10 misiones sin errores |
| Entrar con mi propio aventurero | Funciona | Entra con `alumno` / `Gremio2026` |
| Rango B y oro mínimo 500 | 2 misiones | 2 misiones |
| Aceptar «Bandidos del desfiladero» y «El dragón de Obsidiana» | Total 5.650 mo | Total 5.650 mo |

## 7. Dificultades y cómo las he resuelto
- **Post/Redirect/Get:** al aceptar o abandonar una misión hago `header('Location: ...')` + `exit`
  tras el POST. Así F5 recarga una petición GET y no repite la acción.
- **Contador de visitas:** lo incremento solo en el GET del tablón (después del bloque POST) para que
  cada recarga sume una sola vez.
- **`$_COOKIE` no se actualiza en la misma petición del `setcookie`:** por eso tras guardar el tema
  redirijo a `preferencias.php`, donde ya se lee el valor nuevo.
- **Cookies manipulables:** valido el tema contra `TEMAS` y trato `gremio_usuario` como texto no
  fiable (`is_string`, recorte a 30 caracteres y `e()` al pintarlo).
- **Cookie de sesión al cerrar sesión:** además de `session_destroy()`, la caduco con los mismos
  parámetros con los que se creó para que el navegador la elimine.
