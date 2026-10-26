# Revisión del repositorio Tornalyx contra la consigna

Oct 20, 2026 · @Telyux Software Development

El repositorio cubre bien el código, la base de datos y los scripts del servidor, pero casi toda la documentación de la 2.ª y 3.ª entrega no está en el repo, y faltan piezas técnicas clave: datos de prueba, tests automáticos, SSL, firewall, fail2ban, monitoreo con Zabbix o Grafana, y firma digital.

Se revisó [telyuxsoftwaredevelopment/Tornalyx](https://github.com/telyuxsoftwaredevelopment/Tornalyx) en su último commit ("agregados los scripts de respaldo…"). El repo tiene el código, los scripts y 4 páginas de documentación en `SGDM/vista/docs/`. Lo que va en la carpeta física (actas, Gantt, FODA, manuales) puede existir fuera del repo; esos puntos figuran como "no está en el repo".

Estados usados en las tablas: **Cumple**, **Parcial** (existe pero tiene algo para arreglar) y **Falta**.

## Ingeniería de Software

La 1.ª entrega está casi completa en `ingenieria-software.html`; de la 2.ª y la 3.ª no hay nada en el repo, y tampoco hay pruebas automáticas.

| Entrega | Requisito | Estado | Observación |
| --- | --- | --- | --- |
| 1.ª | Organización Mantei-Constantine justificada | Cumple | Programador Jefe con matices democráticos |
| 1.ª | Reglas del grupo (mínimo 7) | Cumple | 7 reglas más el régimen de 3 strikes |
| 1.ª | Ciclo de vida con fundamentación | Cumple | Iterativo-incremental con Scrum |
| 1.ª | Roles dentro del equipo | Cumple | Tabla con los 4 integrantes |
| 1.ª | Gantt hasta la 1.ª entrega | Parcial | Se cita como Anexo A (y PERT como Anexo B), no está en el repo |
| 1.ª | Justificación de técnicas de relevamiento | Cumple | Google Forms y entrevistas presenciales |
| 1.ª | Modelos de formularios | Parcial | Solo una descripción de cada uno, no el formulario |
| 1.ª | Resultados crudos | Falta | Se cita como Anexo C, no está |
| 1.ª | ESRE IEEE 29148: propósito, alcance, definiciones, limitaciones | Falta | Se cita como Anexo D, no está |
| 1.ª | Roles y perfiles de usuario con nivel de acceso (tabla) | Parcial | No está en el doc de ISW; lo más parecido es la matriz RBAC de Ciberseguridad |
| 2.ª | ESRE con requisitos funcionales y no funcionales | Falta | No está en el repo |
| 2.ª | Casos de uso (planilla y diagrama) | Falta | No está en el repo |
| 2.ª | Diagrama de clases | Falta | No está en el repo |
| 2.ª | Estudio de factibilidad | Falta | No está en el repo |
| 2.ª | Plan de contingencias | Falta | No está en el repo |
| 2.ª | Gantt hasta la 2.ª entrega | Falta | No está en el repo |
| 3.ª | Calidad de software (familia ISO 25000) | Falta | No está en el repo |
| 3.ª | Plan de testing con pruebas manuales y unitarias | Falta | No hay PHPUnit ni Cypress, ni `composer.json`; solo el script casero `probar_motor.php` |
| 3.ª | Encuesta de percepción de calidad | Falta | No está en el repo |
| 3.ª | Plan de seguridad web basado en OWASP | Falta | No está en el repo |
| 3.ª | Métricas por punto de función | Falta | No está en el repo |
| 3.ª | Gantt del proyecto completo | Falta | No está en el repo |
| 3.ª | Manual de usuario operacional | Falta | No está en el repo |
| 3.ª | Manual de administrador | Falta | No está en el repo |

En el documento, la numeración salta de la sección 5 a la 7.

## Administración de Sistemas Operativos

Los scripts están completos y los 13 pasan la verificación de sintaxis de Bash; lo que falta es el monitoreo con una herramienta real y toda la seguridad del servidor.

| Entrega | Requisito | Estado | Observación |
| --- | --- | --- | --- |
| 1.ª | Estudio de SO cliente y servidor | Cumple | Windows 11 y AlmaLinux 8.10 |
| 1.ª | Análisis de usuarios del sistema | Cumple | Mínimo privilegio, UID 1001 a 1004 |
| 1.ª | Script de gestión de usuarios | Cumple | `usuarios.sh`, `crear_roles.sh` y el resto de la suite. En el doc, el subtítulo 3.1 quedó vacío |
| 2.ª | Política de respaldos: tipos | Parcial | El script hace solo respaldos completos; no hay incremental ni diferencial, ni un documento que explique los tipos |
| 2.ª | Política de respaldos: cronograma | Cumple | Diario a las 03:15 en `/etc/cron.d/tornalyx`, con rotación |
| 2.ª | Script de respaldo con cron | Cumple | `respaldo.sh` y `cron.sh` |
| 2.ª | Sistema de monitoreo (Zabbix, Grafana, etc.) | Falta | `monitoreo_bd.sh` es un script propio, no un sistema de monitoreo |
| 3.ª | Políticas de seguridad | Falta | No hay documento |
| 3.ª | Firewall, iptables, antivirus | Falta | `servicios.sh` solo arranca y detiene `firewalld`; no hay reglas, iptables ni ClamAV |
| 3.ª | Script de consultas y monitoreo de la base | Cumple | `monitoreo_bd.sh` |
| 3.ª | Script general de gestión del servidor | Cumple | `gestion.sh` con menú y modo `doctor` |
| 3.ª | Docker de la aplicación | Cumple | `Dockerfile` y `docker-compose.yml` con MySQL |

## Programación Fullstack

La 2.ª entrega se cumple completa; lo que falta para la 3.ª son los 50 registros de prueba por componente.

| Entrega | Requisito | Estado | Observación |
| --- | --- | --- | --- |
| 1.ª | Maquetado Mobile First con Flexbox o Grid | Parcial | Flexbox y Grid se usan mucho, pero hay unas 13 media queries con `max-width` (enfoque desktop-first) mezcladas con las de `min-width` |
| 2.ª | Modelo relacional normalizado | Cumple | 16 tablas y 23 claves foráneas |
| 2.ª | DCL implementado | Cumple | `dcl.sql` |
| 2.ª | Usuarios de base con restricciones | Cumple | `ddl`, `dml`, `monitor`, `backup`, `dev` y `dcl`, cada uno con lo mínimo |
| 2.ª | Modelos alineados al relacional | Cumple | `SGDM/modelo/` |
| 2.ª | Integración PHP con POO y gestión de usuarios | Cumple | MVC propio; todo el PHP pasa `php -l` sin errores |
| 2.ª | Implementación con Apache | Cumple | vhost y `.htaccess` con rewrite y headers |
| 3.ª | Sistema completo y portable con Docker | Cumple | Liga, eliminación directa y sistema suizo implementados |
| 3.ª | Datos de prueba: mínimo 50 registros por componente | Falta | El compose carga solo `schema.sql`, vacío. `probar_motor.php` crea pocos registros (8 en la copa) y los borra al volver a correrlo |

El sistema suizo existe en `Fixture::suizo`, pero `probar_motor.php` solo prueba liga y eliminación directa.

## Tutoría UTULAB

La 1.ª entrega está en `tutoria.html`; de la 2.ª y la 3.ª solo está el repositorio.

| Entrega | Requisito | Estado | Observación |
| --- | --- | --- | --- |
| 1.ª | Nombre y logo del grupo con justificación | Cumple | Telyux |
| 1.ª | Logo de la aplicación | Cumple | Tornalyx, con paleta Crimson Fire |
| 1.ª | Fundamentos SCAMPER | Cumple |  |
| 1.ª | Formulario de acta de reunión | Parcial | No está como plantilla separada |
| 1.ª | Actas internas (mínimo 6) | Cumple | Actas 01 a 07 |
| 1.ª | Fundamentación del diseño | Cumple |  |
| 2.ª | Análisis FODA | Falta | No está en el repo |
| 2.ª | Repositorio git accesible | Cumple | Este repositorio |
| 2.ª | Actas de la 2.ª entrega (mínimo 6) | Falta | No está en el repo |
| 2.ª | Análisis costo-beneficio | Falta | No está en el repo |
| 2.ª | Seguimiento de la planificación | Falta | No está en el repo |
| 3.ª | Actas de la 3.ª entrega (mínimo 6) | Falta | No está en el repo |
| 3.ª | Modelo 3D para marketing | Falta | No está en el repo |
| 3.ª | Manual de usuario de todos los roles | Falta | No está en el repo |
| 3.ª | Seguimiento final de la planificación | Falta | No está en el repo |

## Ciberseguridad

La seguridad de la aplicación está bien resuelta (bcrypt, CSRF, CSP, 2FA); lo que falta es la seguridad del servidor, el SSL, la firma digital y los documentos de la 3.ª entrega.

| Entrega | Requisito | Estado | Observación |
| --- | --- | --- | --- |
| 1.ª | Amenazas específicas del sistema | Cumple | TX-01 a TX-29 |
| 1.ª | Vulnerabilidades de infraestructura | Parcial | Quedaron dentro de la 2.6; la numeración salta de la sección 2 a la 4 |
| 1.ª | Controles de acceso y roles | Cumple | Matriz RBAC por módulo |
| 1.ª | Política de contraseñas | Cumple | Con 2FA, según NIST SP 800-63B |
| 1.ª | Políticas y medidas de seguridad | Cumple | Sección de desarrollo seguro |
| 2.ª | Validaciones en frontend y backend | Cumple | `validations.js` y todos los controladores |
| 2.ª | Encriptación de contraseñas | Cumple | bcrypt con costo 12; los códigos OTP también van hasheados |
| 2.ª | Esquema de red con seguridad | Parcial | Está `esquemadered.drawio`, sin documento que lo explique |
| 2.ª | Registro de IDS, IPS, firewall, fail2ban | Falta | No hay ninguna configuración |
| 3.ª | Hashing, cifrado y firma digital, con documento | Parcial | Hashing sí; cifrado solo TLS hacia la base; firma digital no hay en el código; falta el documento |
| 3.ª | Certificado SSL en el servidor | Falta | No hay `mod_ssl`, certbot ni vhost en el puerto 443 |
| 3.ª | Políticas de seguridad de usuario | Falta | No hay documento |
| 3.ª | Informe OWASP del sitio | Falta | No hay informe. Sí están CSRF por cookie y header, CSP y cabeceras de seguridad en `.htaccess`, y límite de intentos de login |

## Otras cosas para arreglar

- Las 4 páginas de documentación todavía dicen "Entrega: 27/07/2026" y listan a los 4 integrantes originales, con Roberto como líder. Hay que actualizar encabezado y equipo para la 3.ª entrega.
- `dcl.sql` trae contraseñas de ejemplo (`CAMBIAR_PASSWORD_*`) y el compose usa `root` y `tornalyx_dev`. Sirve para desarrollo, pero en el servidor real hay que cambiarlas.
- `publico/assets/galeria/lamine-yamal.jpg` es la foto de una persona real. Si no tienen derechos sobre la imagen, conviene reemplazarla.
- A favor: no hay secretos reales en el historial de git y el `.env` está bien ignorado.

## Prioridades para la 3.ª entrega

La entrega es el 23/10/2026; esto es lo que más pesa, en orden.

- [ ] Seed con 50 o más registros por tabla, cargado desde Docker (Fullstack)
- [ ] Pruebas con PHPUnit o Cypress y el plan de testing (ISW)
- [ ] SSL en el servidor, reglas de firewall, fail2ban y antivirus (SO y Ciberseguridad)
- [ ] Zabbix o Grafana en el servidor, pendiente desde la 2.ª entrega (SO)
- [ ] Firma digital en algún punto del sistema, por ejemplo con `openssl_sign`, y su documento (Ciberseguridad)
- [ ] Documentación de la 2.ª y 3.ª entrega, si no está fuera del repo (todas las materias)
