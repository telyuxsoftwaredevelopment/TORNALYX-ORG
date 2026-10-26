# Plan de pruebas — Tornalyx

**Versión:** 1.0 · **Fecha:** 25/10/2026 · **Alcance:** lógica de fixtures, flujos web y persistencia MySQL.

## Objetivo y criterios

Comprobar que los formatos de torneo generan cruces válidos, los resultados mantienen posiciones coherentes y los flujos protegidos exigen identidad, autorización y token CSRF. Una ejecución se considera aprobada si todas las pruebas automatizadas terminan con código 0, no hay errores PHP y las pruebas manuales críticas cumplen el resultado esperado.

## Pruebas automatizadas

| ID | Tipo | Escenario | Resultado esperado |
|---|---|---|---|
| UT-FIX-01 | Unidad | Round robin, seis contendientes | 5 rondas, 15 cruces únicos, nadie se enfrenta consigo mismo |
| UT-FIX-02 | Unidad | Round robin, cinco contendientes | 10 cruces; un descanso por ronda |
| UT-FIX-03 | Unidad | Eliminación directa, cantidad impar | Empareja consecutivamente y deja un bye |
| UT-FIX-04 | Unidad | Suizo con rival ya enfrentado | Elige otro rival disponible |
| UT-CRYPTO-01 | Unidad | Firmar payload canónico RSA-SHA256 | Firma válida verifica y payload alterado falla |
| IT-MOTOR-01 | Integración | `probar_motor.php` con base de desarrollo | Liga y eliminación generan rondas, resultados y posiciones coherentes |
| IT-MOTOR-02 | Integración | Suizo en prueba de modelo | No repite rival mientras exista alternativa y deja bye si impar |

Ejecutar la suite unitaria desde `SGDM/backend/` con `composer install` y `composer test`. La prueba de integración requiere la base configurada y se ejecuta con `php database/probar_motor.php`; solo admite `APP_ENV` distinto de `production` y modifica datos de demostración.

## Casos manuales

| ID | Flujo | Pasos y resultado esperado |
|---|---|---|
| M-01 | Registro e inicio de sesión | Registrar datos válidos; verificar el desafío 2FA por correo; iniciar sesión y cerrar sesión |
| M-02 | Validación | Enviar campos requeridos vacíos o fuera de límites; el servidor rechaza la operación sin guardar cambios |
| M-03 | CSRF | Enviar POST sin token y con token incorrecto; recibir rechazo y sin cambios persistidos |
| M-04 | Autorización | Participante intenta administrar torneo ajeno; operación denegada |
| M-05 | Inscripción | Participante solicita lugar; organizador aprueba; participante aparece una sola vez |
| M-06 | Liga | Organizador genera fixture; consultar calendario y tabla tras cargar resultados |
| M-07 | Eliminación | Cargar ganadores sucesivamente; avanzar hasta una final y definir campeón |
| M-08 | Suizo | Jugar rondas; comprobar emparejamiento por clasificación y evitar revancha disponible |
| M-09 | Persistencia | Reiniciar contenedores sin borrar volúmenes; usuarios y torneos permanecen |
| M-10 | Documentos | Solicitar acceso, aprobar y verificar OTP/vencimiento; usuario sin aprobación no accede |

## Datos, ambiente y evidencia

Usar únicamente datos sintéticos en una instancia local separada de producción. Registrar versión del commit, fecha, entorno, ID del caso, pasos, resultado esperado/obtenido y captura o log sin contraseñas, OTP ni tokens. Al informar un defecto, incluir severidad, pasos para reproducir y evidencia redactada.

## Límites

La prueba del motor no cubre controladores HTTP, permisos, CSRF, correo, concurrencia ni configuración del proveedor. Esos riesgos requieren las pruebas manuales listadas y revisión del despliegue.
