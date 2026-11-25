<?php
/**
 * =====================================================================
 *  inc/funciones.php · Funciones del tablón · Solución de ejemplo
 * =====================================================================
 *  PARTE 1 (UD3): decisión con match, recorridos con foreach, arrays.
 *  PARTE 2 (UD4): funciones propias con tipos, parámetros y retorno.
 *
 *   *  NO cambies los nombres ni las firmas: el resto de páginas las usan.
 */

declare(strict_types=1);

/** Rangos válidos, de menor a mayor dificultad. */
const RANGOS = ['E', 'D', 'C', 'B', 'A', 'S'];

/** Escapa texto para pintarlo en HTML. Úsala siempre al pintar datos. */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

/**
 * PARTE 1 · Nombre del rango.
 * E Novato · D Aprendiz · C Veterano · B Experto · A Élite · S Leyenda
 * Con default: un rango desconocido devuelve 'Desconocido' en vez de romper.
 */
function etiquetaRango(string $rango): string
{
    // default: un rango desconocido (p. ej. 'Z') no rompe la página.
    return match ($rango) {
        'E'     => 'Novato',
        'D'     => 'Aprendiz',
        'C'     => 'Veterano',
        'B'     => 'Experto',
        'A'     => 'Élite',
        'S'     => 'Leyenda',
        default => 'Desconocido',
    };
}

/**
 * PARTE 1 · Clase CSS según dificultad.
 * E y D → 'facil' · C y B → 'media' · A y S → 'dificil'
 * match con varios valores por brazo.
 */
function claseRango(string $rango): string
{
    return match ($rango) {
        'E', 'D' => 'facil',
        'C', 'B' => 'media',
        'A', 'S' => 'dificil',
        default  => '',
    };
}

/**
 * PARTE 1 · Formatea monedas de oro: 5000 → "5.000 mo".
 * Usa number_format con punto de millares.
 */
function formatearOro(int $oro): string
{
    return number_format($oro, 0, ',', '.') . ' mo';
}

/**
 * PARTE 2 · Filtra misiones por rango (null = todos) y recompensa mínima.
 * Recorre con foreach y construye un array nuevo.
 */
function filtrarMisiones(array $misiones, ?string $rango, int $oroMinimo): array
{
    $resultado = [];
    foreach ($misiones as $mision) {
        if ($rango !== null && $mision['rango'] !== $rango) {
            continue;
        }
        if ($mision['recompensa'] < $oroMinimo) {
            continue;
        }
        $resultado[] = $mision;
    }
    return $resultado;
}

/**
 * PARTE 2 · Busca una misión por su id ('M05'). Devuelve null si no existe.
 */
function buscarMision(array $misiones, string $id): ?array
{
    foreach ($misiones as $mision) {
        if ($mision['id'] === $id) {
            return $mision;
        }
    }
    return null;
}

/**
 * PARTE 2 · Suma la recompensa de las misiones cuyos ids se indican.
 * Reutiliza buscarMision(). Un id inexistente no suma nada.
 */
function totalRecompensa(array $misiones, array $ids): int
{
    $total = 0;
    foreach ($ids as $id) {
        $mision = buscarMision($misiones, (string) $id);
        if ($mision !== null) {
            $total += $mision['recompensa'];
        }
    }
    return $total;
}

/**
 * PARTE 2 · Lee el rango de $_GET['rango'] y lo valida.
 * Devuelve el rango en MAYÚSCULAS si está en RANGOS; si no viene o no es
 * válido, null. Nunca te fíes de la URL: el usuario puede escribir lo que quiera.
 */
function leerRangoFiltro(array $get): ?string
{
    $rango = $get['rango'] ?? null;
    // ?rango[]=B llega como array: no es un texto, se descarta.
    if (!is_string($rango)) {
        return null;
    }
    $rango = strtoupper(trim($rango));
    return in_array($rango, RANGOS, true) ? $rango : null;
}

/**
 * PARTE 2 · Lee $_GET['oro'] y lo valida como entero entre 0 y 10000.
 * Si no viene o no es válido, 0.
 * filter_var con FILTER_VALIDATE_INT y min_range / max_range.
 */
function leerOroMinimo(array $get): int
{
    $oro = $get['oro'] ?? null;
    if (!is_string($oro)) {
        return 0;
    }
    $valido = filter_var($oro, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'max_range' => 10000],
    ]);
    // filter_var devuelve false si no es un entero válido en el rango.
    return $valido === false ? 0 : $valido;
}
