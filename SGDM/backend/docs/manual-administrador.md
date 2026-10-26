# Manual de administrador — Tornalyx

## Acceso y responsabilidades

Usar una cuenta administrativa individual, proteger el segundo factor y cerrar sesión al terminar. Conceder acceso mínimo necesario y no compartir cuentas. Los cambios de infraestructura se realizan según [la guía de instalación en servidor](operacion-y-seguridad-servidor.md).

## Gestión desde el panel

- Buscar usuarios y corregir sus datos según solicitudes verificadas.
- Suspender una cuenta ante abuso; revisar impacto antes de reactivarla.
- Asignar rol administrativo solo con autorización institucional.
- Resolver solicitudes de acceso a documentación; la aprobación se completa mediante enlace/OTP del flujo correspondiente.
- Consultar estado de migraciones y atender pendientes antes de publicar cambios.

## Operación y recuperación

No editar tablas de producción manualmente para corregir un fixture. Usar el flujo de aplicación o un procedimiento de recuperación respaldado. Antes de restaurar, verificar copia y restaurar primero en un entorno aislado. Las claves RSA de firma se mantienen fuera del repositorio y con acceso restringido; una rotación requiere conservar/publicar la clave pública anterior para validar firmas históricas.

## Incidentes

Registrar hora, versión, impacto, acciones y responsable. Para exposición de cuenta, suspender acceso y revocar sesiones; para exposición de clave privada, detener nuevas firmas y seguir el plan de contingencia. No registrar contraseñas, OTP ni tokens en el informe.
