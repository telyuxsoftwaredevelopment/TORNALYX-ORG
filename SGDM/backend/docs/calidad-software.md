# Calidad del software — Tornalyx

Marco de referencia: familia ISO/IEC 25000 (SQuaRE). Esta matriz define objetivos verificables para la versión académica; no afirma una certificación ni sustituye mediciones en producción.

| Característica | Objetivo del proyecto | Evidencia / medición |
|---|---|---|
| Adecuación funcional | Crear torneos, inscribir competidores, producir fixtures y registrar resultados | Casos M-05 a M-08 y pruebas del motor |
| Eficiencia de desempeño | Consultas acotadas por torneo y uso de índices en claves de búsqueda | Revisar `EXPLAIN`; medir respuesta con conjunto de prueba documentado |
| Compatibilidad | Despliegue reproducible en Docker con PHP/Apache y MySQL | Arranque limpio y prueba de persistencia |
| Usabilidad | Navegación y formularios legibles en móvil y escritorio | Encuesta posterior a tareas: facilidad 1–5, finalización y comentarios |
| Fiabilidad | Restricciones relacionales y resultados consistentes | Pruebas de integración; copia y restauración documentadas |
| Seguridad | Sesiones protegidas, contraseñas bcrypt, validación, CSRF, RBAC y TLS según entorno | Revisión OWASP, M-01 a M-04 y configuración de despliegue |
| Mantenibilidad | MVC, separación de reglas de fixture y documentación de operación | Pruebas unitarias, revisión de cambios y registro de defectos |
| Portabilidad | Compose para desarrollo y contenedor para hosting compatible | Build desde checkout limpio y matriz de variables de entorno |

## Encuesta de percepción de calidad

Aplicar tras completar una tarea real. Escala: 1 = muy difícil / muy en desacuerdo; 5 = muy fácil / muy de acuerdo. Preguntar: (1) ¿Encontraste fácilmente cómo consultar un torneo? (2) ¿Fue claro el proceso de inscripción? (3) ¿La información de partidos y posiciones fue comprensible? (4) ¿La interfaz se pudo usar desde tu dispositivo? (5) ¿Qué mejorarías? Registrar cantidad de respuestas, distribución por pregunta y comentarios anonimizados; no presentar resultados hasta contar con respuestas reales.

## Métricas de seguimiento

Por versión registrar: casos aprobados / ejecutados, defectos abiertos por severidad, tiempo de respuesta mediano y p95 bajo carga descrita, errores HTTP, resultado de restauración de backup y hallazgos de seguridad pendientes. Los valores se miden; no se inventan como resultados.
