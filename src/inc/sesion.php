<?php
/**
 * =====================================================================
 *  inc/sesion.php · Sesión y cookies del Gremio · Solución de ejemplo
 * =====================================================================
 *  PARTE 3 (UD5) · CE 4.b (sesiones) · CE 4.c (cookies) · CE 4.e.1 (autenticación)
 *
 *  Recuerda la amnesia de la UD2: PHP olvida todo al terminar la petición.
 *  La SESIÓN guarda datos en el SERVIDOR; el navegador solo guarda una
 *  cookie con el identificador (PHPSESSID). Las COOKIES propias guardan
 *  datos pequeños en el NAVEGADOR (aquí, preferencias no sensibles).
 */

declare(strict_types=1);

const COOKIE_TEMA    = 'gremio_tema';
const COOKIE_USUARIO = 'gremio_usuario';
const TEMAS          = ['claro', 'oscuro'];

/** Arranca la sesión con una cookie de sesión segura. */
function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Devuelve el aventurero guardado en la sesión, o null. */
function aventureroActual(): ?array
{
    return $_SESSION['aventurero'] ?? null;
}

/**
 * Protege una página: si no hay sesión, redirige a login.php?motivo=privado
 * y TERMINA el script. Si la hay, devuelve el aventurero.
 *
 */
function requiereLogin(): array
{
    $aventurero = aventureroActual();
    if ($aventurero === null) {
        header('Location: login.php?motivo=privado');
        exit; // imprescindible: sin exit el resto de la página se seguiría ejecutando
    }
    return $aventurero;
}

/** Guarda un mensaje para mostrarlo UNA vez en la siguiente página. */
function mensajeFlash(string $texto): void
{
    $_SESSION['flash'] = $texto;
}

/** Recupera y borra el mensaje flash. */
function leerFlash(): ?string
{
    $texto = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $texto;
}

/**
 * Tema guardado en la cookie COOKIE_TEMA. Si no existe o su valor no está
 * en TEMAS (¡alguien puede haberla manipulado!), devuelve 'claro'.
 */
function temaActual(): string
{
    $tema = $_COOKIE[COOKIE_TEMA] ?? '';
    // La cookie la controla el navegador: se valida contra la lista blanca.
    if (is_string($tema) && in_array($tema, TEMAS, true)) {
        return $tema;
    }
    return 'claro';
}

/**
 * Guarda el tema en la cookie COOKIE_TEMA durante un año.
 * setcookie con array de opciones: expires, path '/', httponly, samesite 'Lax'.
 */
function guardarTema(string $tema): void
{
    setcookie(COOKIE_TEMA, $tema, [
        'expires'  => time() + 365 * 24 * 60 * 60, // 1 año
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Guarda el NOMBRE DE USUARIO (nunca la contraseña) 30 días en COOKIE_USUARIO. */
function recordarUsuario(string $usuario): void
{
    setcookie(COOKIE_USUARIO, $usuario, [
        'expires'  => time() + 30 * 24 * 60 * 60, // 30 días
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Borra la cookie COOKIE_USUARIO (pista: caducidad en el pasado). */
function olvidarUsuario(): void
{
    // Mismos path/httponly/samesite que al crearla, con caducidad en el pasado.
    setcookie(COOKIE_USUARIO, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Comprueba las credenciales contra la tabla `aventureros`.
 * Devuelve id, usuario, nombre y clase (SIN el hash) o null si no son correctas.
 *
 *  1. Consulta preparada con un marcador :usuario. PROHIBIDO concatenar.
 *  2. Si no hay fila → null.
 *  3. password_verify($clave, $fila['password_hash']); si falla → null.
 *  4. Quita el hash del array antes de devolverlo.
 *  Pregunta para la memoria: ¿por qué el mensaje de error es el mismo si
 *  falla el usuario que si falla la contraseña?
 */
function autenticar(PDO $pdo, string $usuario, string $clave): ?array
{
    // Consulta preparada: el usuario viaja como dato, nunca como parte del SQL.
    $sentencia = $pdo->prepare(
        'SELECT id, usuario, nombre, clase, password_hash
           FROM aventureros
          WHERE usuario = :usuario'
    );
    $sentencia->execute([':usuario' => $usuario]);
    $fila = $sentencia->fetch();

    if ($fila === false) {
        return null; // el usuario no existe
    }
    if (!password_verify($clave, $fila['password_hash'])) {
        return null; // contraseña incorrecta
    }

    unset($fila['password_hash']); // el hash nunca sale de esta función
    return $fila;
}

/**
 * Cierra la sesión POR COMPLETO: vacía $_SESSION, caduca la cookie de sesión
 * (session_name(), session_get_cookie_params()) y llama a session_destroy().
 */
function cerrarSesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }

    session_destroy();
}
