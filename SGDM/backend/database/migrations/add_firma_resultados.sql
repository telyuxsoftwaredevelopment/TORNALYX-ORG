-- Firma criptográfica opcional de los resultados con RSA/SHA-256.
ALTER TABLE resultados
    ADD COLUMN firma_payload TEXT NULL DEFAULT NULL,
    ADD COLUMN firma_digital TEXT NULL DEFAULT NULL,
    ADD COLUMN firma_algoritmo VARCHAR(32) NULL DEFAULT NULL,
    ADD COLUMN firmado_at TIMESTAMP NULL DEFAULT NULL;
