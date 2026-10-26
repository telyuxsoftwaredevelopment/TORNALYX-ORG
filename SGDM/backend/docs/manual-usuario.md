# Manual de usuario — Tornalyx

## Público

Abrí la página de torneos, buscá por nombre o disciplina y seleccioná un torneo para consultar descripción, formato, fechas, partidos, resultados y posiciones. Solo los torneos marcados como públicos aparecen sin iniciar sesión.

## Participante

1. Creá una cuenta con nombre, apellido, correo y fecha de nacimiento.
2. Iniciá sesión con correo y contraseña; ingresá el código temporal enviado por correo si el segundo factor está habilitado.
3. Abrí un torneo con inscripción disponible y enviá la solicitud. El organizador debe aprobarla.
4. Consultá el estado de inscripción, fixture, horario y resultados en el detalle del torneo.
5. Editá perfil desde la sección de cuenta. No compartas códigos OTP ni enlaces de aprobación.

## Organizador

1. Iniciá sesión y creá un torneo desde el panel de organizador.
2. Completá nombre, disciplina, formato, cupo y reglas; publicalo cuando esté listo.
3. Revisá inscripciones y aprobá o rechazá cada solicitud.
4. Cuando haya al menos dos contendientes aprobados, generá el fixture. En liga se genera el calendario; en eliminación y suizo se genera ronda a ronda.
5. Programá hora/lugar y cargá el marcador al finalizar el partido. La tabla de posiciones se recalcula automáticamente.
6. Si el servidor tiene claves de firma instaladas, la respuesta del resultado incluye su firma RSA y se puede comprobar con `GET /api/resultado/{id}/verificar`; sin claves, el resultado queda sin firma.

## Problemas frecuentes

- No puedo iniciar sesión: verificá correo/contraseña y revisá si llegó el código OTP; solicitá uno nuevo si expiró.
- No puedo generar fixture: revisá que existan dos o más contendientes aprobados y que la ronda anterior esté completa.
- No veo un torneo: puede ser privado, estar en borrador o no estar publicado.
- No puedo gestionar un torneo: solo su organizador o un administrador tienen permiso.
