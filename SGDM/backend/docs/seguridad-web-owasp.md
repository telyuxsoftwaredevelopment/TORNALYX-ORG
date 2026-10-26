# Revisión y plan de seguridad web

Evaluación documental basada en OWASP Top 10 (2021). Es una revisión del repositorio, no un pentest ni certificación. Volver a evaluar ante cambios importantes y antes de exponer una instancia pública.

| Área | Controles observados / acción | Estado |
|---|---|---|
| A01 Control de acceso | Roles y autorización del lado servidor; probar acceso horizontal a cada recurso | Implementado; validar con M-04 |
| A02 Fallos criptográficos | `password_hash`/`password_verify` y TLS a MySQL configurable; exigir HTTPS público y proteger claves | Parcial por entorno |
| A03 Inyección | PDO y consultas preparadas en modelos/controladores; revisar cualquier SQL dinámico | Implementado según revisión; regresión requerida |
| A04 Diseño inseguro | Throttle de login y flujo 2FA; documentar abuso de recuperación y límites por IP/cuenta | Parcial |
| A05 Configuración insegura | `.htaccess` agrega cabeceras y desactiva listado; confirmar cabeceras y errores en cada proxy | Parcial |
| A06 Componentes vulnerables | Inventariar imágenes base y dependencias, fijar versiones y escanear en cada release | Pendiente de proceso |
| A07 Autenticación | Bcrypt, OTP por email, limitación de intentos y sesión propia; probar expiración y rotación | Implementado; pruebas manuales |
| A08 Integridad de software/datos | Resultados pueden firmarse RSA-SHA256 y verificarse con clave pública; aún se debe restringir pipeline y validar artefactos | Parcial |
| A09 Registro/alertas | Logs Apache a stdout; evitar PII, contraseñas, OTP y tokens; definir retención/alertas | Parcial |
| A10 SSRF | Validar URLs externas admitidas en perfiles y formularios; no efectuar fetch arbitrario | Revisar cada campo URL |

## Controles de release

1. Ejecutar `php -l` en los PHP cambiados y pruebas descritas en `plan-testing.md`.
2. Verificar HTTPS, redirección HTTP, cookies `Secure`, `HttpOnly`, `SameSite`, cabeceras y límites de login desde el hostname desplegado.
3. Confirmar que secretos solo estén en el gestor de variables del hosting y que la base no acepte conexiones públicas innecesarias.
4. Revisar accesos directos a controladores, CSRF, autorización por objeto y subida de archivos.
5. Corregir hallazgos críticos/altos antes de publicar; guardar fecha, versión, evidencia redactada y responsable.

## Límites de infraestructura

Render termina TLS en su plataforma; no se instala Certbot ni un certificado dentro de este contenedor. En una VM propia, TLS, firewall, Fail2ban, antivirus y monitoreo deben habilitarse en el sistema operativo anfitrión con reglas específicas al proveedor y puertos realmente usados. La aplicación por sí sola no demuestra que esos servicios estén activos.
