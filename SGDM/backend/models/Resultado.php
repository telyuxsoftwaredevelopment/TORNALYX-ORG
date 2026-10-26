<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/../shared/FirmaResultado.php';

/**
 * Modelo de Resultado. Registra y consulta resultados de partidos.
 */
class Resultado extends Model {

    protected string $table = 'resultados';

    /**
     * Carga el resultado de un partido y lo marca como finalizado.
     *
     * Cada partido tiene un único resultado (clave única en la BD), así que
     * volver a cargarlo corrige el marcador en vez de fallar: el organizador
     * puede enmendar un error de carga. Quien recalcula la tabla de
     * posiciones es Posicion::recalcular(), que se ejecuta después.
     *
     * @param int      $partidoId
     * @param int      $golesLocal
     * @param int      $golesVisitante
     * @param int|null $ganadorId    NULL = empate
     * @param int      $cargadoPor   usuario_id
     * @return int ID del resultado.
     */
    public function cargar(
        int $partidoId,
        int $golesLocal,
        int $golesVisitante,
        ?int $ganadorId,
        int $cargadoPor
    ): int {
        $transaccionPropia = !$this->db->inTransaction();
        if ($transaccionPropia) $this->db->beginTransaction();
        try {
        $stmt = $this->db->prepare(
            'INSERT INTO resultados
                (partido_id, goles_local, goles_visitante, ganador_id, cargado_por)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                goles_local     = VALUES(goles_local),
                goles_visitante = VALUES(goles_visitante),
                ganador_id      = VALUES(ganador_id),
                cargado_por     = VALUES(cargado_por)'
        );
        $stmt->execute([$partidoId, $golesLocal, $golesVisitante, $ganadorId, $cargadoPor]);

        $this->db->prepare(
            "UPDATE partidos SET estado = 'finalizado' WHERE id = ?"
        )->execute([$partidoId]);

        $stmt = $this->db->prepare(
            'SELECT id, partido_id, goles_local, goles_visitante, ganador_id, cargado_por
               FROM resultados WHERE partido_id = ?'
        );
        $stmt->execute([$partidoId]);
        $fila = $stmt->fetch();
        if (!$fila) throw new RuntimeException('No se pudo recuperar el resultado guardado.');

        $payload = FirmaResultado::payload($fila);
        $firma = FirmaResultado::firmar($payload);
        $actualizarFirma = $this->db->prepare(
            'UPDATE resultados SET firma_payload = ?, firma_digital = ?,
                    firma_algoritmo = ?, firmado_at = ? WHERE id = ?'
        );
        $actualizarFirma->execute([
            $firma === null ? null : $payload,
            $firma,
            $firma === null ? null : 'RSA-SHA256',
            $firma === null ? null : date('Y-m-d H:i:s'),
            (int) $fila['id'],
        ]);
        if ($transaccionPropia) $this->db->commit();
        return (int) $fila['id'];
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** Devuelve valida, invalida, sin_firma o clave_no_disponible; null si no existe. */
    public function verificarFirma(int $resultadoId): ?string {
        $stmt = $this->db->prepare(
            'SELECT partido_id, goles_local, goles_visitante, ganador_id, cargado_por,
                    firma_payload, firma_digital, firma_algoritmo
               FROM resultados WHERE id = ?'
        );
        $stmt->execute([$resultadoId]);
        $fila = $stmt->fetch();
        if (!$fila) return null;
        if (!$fila['firma_payload'] || !$fila['firma_digital']
            || $fila['firma_algoritmo'] !== 'RSA-SHA256') return 'sin_firma';
        $payloadActual = FirmaResultado::payload($fila);
        if (!hash_equals($fila['firma_payload'], $payloadActual)) return 'invalida';
        $valida = FirmaResultado::verificar($fila['firma_payload'], $fila['firma_digital']);
        return $valida === null ? 'clave_no_disponible' : ($valida ? 'valida' : 'invalida');
    }

    /**
     * Obtiene resultados de un torneo con datos de partido.
     *
     * @param int $torneoId
     * @return array
     */
    public function listarPorTorneo(int $torneoId): array {
        // Resuelve los nombres de local y visitante (equipo o usuario, según el
        // tipo del partido) para mostrar resultados legibles en el detalle.
        $stmt = $this->db->prepare(
            'SELECT r.*, p.local_id, p.visitante_id, p.fecha_partido, p.tipo_contendiente,
                    ro.numero AS ronda, ro.nombre AS ronda_nombre,
                    COALESCE(el.nombre, TRIM(CONCAT(ul.nombre, \' \', COALESCE(ul.apellido, \'\')))) AS local_nombre,
                    COALESCE(ev.nombre, TRIM(CONCAT(uv.nombre, \' \', COALESCE(uv.apellido, \'\')))) AS visitante_nombre
               FROM resultados r
               INNER JOIN partidos p  ON p.id = r.partido_id
               INNER JOIN rondas   ro ON ro.id = p.ronda_id
               LEFT JOIN equipos  el ON p.tipo_contendiente = \'equipo\'  AND el.id = p.local_id
               LEFT JOIN usuarios ul ON p.tipo_contendiente = \'usuario\' AND ul.id = p.local_id
               LEFT JOIN equipos  ev ON p.tipo_contendiente = \'equipo\'  AND ev.id = p.visitante_id
               LEFT JOIN usuarios uv ON p.tipo_contendiente = \'usuario\' AND uv.id = p.visitante_id
               WHERE p.torneo_id = ?
               ORDER BY ro.numero ASC, p.id ASC'
        );
        $stmt->execute([$torneoId]);
        return $stmt->fetchAll();
    }
}
