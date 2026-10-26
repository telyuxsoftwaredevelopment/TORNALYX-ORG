<?php
/** Firma y verifica la representación canónica de un resultado con RSA/SHA-256. */
final class FirmaResultado {
    private static function cargarClave(string $variable, bool $privada) {
        $valor = getenv($variable) ?: '';
        if ($valor === '') return false;
        if (str_starts_with($valor, '-----BEGIN ')) return $privada
            ? openssl_pkey_get_private($valor)
            : openssl_pkey_get_public($valor);
        if (!is_readable($valor)) return false;
        return $privada
            ? openssl_pkey_get_private('file://' . $valor)
            : openssl_pkey_get_public('file://' . $valor);
    }

    public static function payload(array $datos): string {
        $canonico = [
            'cargado_por' => (int) $datos['cargado_por'],
            'goles_local' => (int) $datos['goles_local'],
            'goles_visitante' => (int) $datos['goles_visitante'],
            'ganador_id' => $datos['ganador_id'] === null ? null : (int) $datos['ganador_id'],
            'partido_id' => (int) $datos['partido_id'],
        ];
        return json_encode($canonico, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function firmar(string $payload): ?string {
        if (!(getenv('RESULTADO_SIGNING_PRIVATE_KEY') ?: '')) return null;
        $clave = self::cargarClave('RESULTADO_SIGNING_PRIVATE_KEY', true);
        if ($clave === false) throw new RuntimeException('No se pudo cargar la clave privada de firma.');
        if (!openssl_sign($payload, $firma, $clave, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el resultado.');
        }
        return base64_encode($firma);
    }

    public static function verificar(string $payload, string $firma): ?bool {
        if (!(getenv('RESULTADO_SIGNING_PUBLIC_KEY') ?: '')) return null;
        $clave = self::cargarClave('RESULTADO_SIGNING_PUBLIC_KEY', false);
        if ($clave === false) throw new RuntimeException('No se pudo cargar la clave pública de firma.');
        $binaria = base64_decode($firma, true);
        return $binaria !== false && openssl_verify($payload, $binaria, $clave, OPENSSL_ALGO_SHA256) === 1;
    }
}
