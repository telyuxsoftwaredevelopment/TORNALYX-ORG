<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FirmaResultadoTest extends TestCase {
    private string $clavePrivada;
    private string $clavePublica;

    protected function setUp(): void {
        $par = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($par);
        openssl_pkey_export($par, $this->clavePrivada);
        $detalle = openssl_pkey_get_details($par);
        $this->clavePublica = $detalle['key'];
        putenv('RESULTADO_SIGNING_PRIVATE_KEY=' . $this->clavePrivada);
        putenv('RESULTADO_SIGNING_PUBLIC_KEY=' . $this->clavePublica);
    }

    protected function tearDown(): void {
        putenv('RESULTADO_SIGNING_PRIVATE_KEY');
        putenv('RESULTADO_SIGNING_PUBLIC_KEY');
    }

    public function testSignatureVerifiesAndDetectsPayloadChanges(): void {
        $datos = ['partido_id' => 41, 'goles_local' => 3, 'goles_visitante' => 1, 'ganador_id' => 12, 'cargado_por' => 7];
        $payload = FirmaResultado::payload($datos);
        $firma = FirmaResultado::firmar($payload);
        self::assertNotNull($firma);
        self::assertTrue(FirmaResultado::verificar($payload, $firma));
        self::assertFalse(FirmaResultado::verificar($payload . ' ', $firma));
    }
}
