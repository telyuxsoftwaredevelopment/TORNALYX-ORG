# Guía de instalación y hardening del servidor Tornalyx

**Objetivo:** instalar el despliegue Docker de Tornalyx en una VM nueva con AlmaLinux 8, habilitar controles del host y dejar evidencia de verificación. Requiere administrador con `sudo`, dominio apuntado a la IP del servidor y acceso SSH abierto desde una sesión administrativa. La guía no declara controles activos hasta completar las comprobaciones.

> Para Render, no ejecutar los pasos de TLS, firewall ni servicios del host: Render termina HTTPS y administra la red de su plataforma. Esta guía corresponde a una VM propia.

## 1. Preparar el sistema

Actualizar paquetes, fijar hostname y reiniciar si hubo actualización de kernel:

```bash
sudo dnf update -y
sudo hostnamectl set-hostname tornalyx.example.org
sudo reboot
```

Crear una cuenta nominal de operación, añadirla al grupo administrativo y probar una segunda sesión SSH antes de restringir root o cambiar configuración de SSH. Mantener SELinux en `Enforcing` (`getenforce`). Usar claves SSH; no publicar contraseñas ni claves privadas.

## 2. Activar el firewall

Primero confirma puerto SSH actual (`sudo sshd -T | grep '^port '`) y la zona activa. Abre SSH y HTTP/HTTPS antes de aplicar la política; no cierres la sesión administrativa hasta comprobar una segunda conexión:

```bash
sudo dnf install -y firewalld
sudo systemctl enable --now firewalld
sudo firewall-cmd --get-active-zones
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
sudo firewall-cmd --list-all
```

Si SSH usa un puerto distinto al servicio `ssh`, crea y habilita un servicio para ese puerto antes de quitar la regla anterior. No abras MySQL (3306), Zabbix Agent (10050), Prometheus (9090), Node Exporter (9100) ni Grafana (3000) a Internet. Restringe esos puertos a la red de administración/servidor de monitoreo. AlmaLinux documenta `firewalld` como firewall integrado y su instalación/activación mediante DNF y systemd ([guía oficial](https://wiki.almalinux.org/documentation/after-installation-guide.html)).

## 3. Instalar el motor de contenedores y publicar la app

El repositorio incluye `Dockerfile` y `docker-compose.yml` para desarrollo. En servidor usa la base de datos externa administrada; no publiques el MySQL local del Compose. Docker publica instrucciones de RHEL; AlmaLinux es compatible a nivel de familia, pero valida el procedimiento primero en una VM de staging ([documentación de Docker](https://docs.docker.com/engine/install/rhel/)).

```bash
sudo dnf install -y dnf-plugins-core
sudo dnf config-manager --add-repo https://download.docker.com/linux/rhel/docker-ce.repo
sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker version
sudo docker compose version
```

Despliegue:

```bash
sudo install -d -m 0750 /opt/tornalyx
cd /opt/tornalyx
sudo git clone <URL_DEL_REPOSITORIO> .
```

Crear `.env` con permisos `0600` (`sudo install -m 0600 /dev/null .env`, luego `sudoedit .env`) y configurar DB externa (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL=1`, `DB_SSL_VERIFY=1`, `DB_SSL_CA` si corresponde) y correo. No reutilizar las credenciales de desarrollo de Compose. Para producción crea `compose.server.yml` con el siguiente servicio de aplicación (sin base local, sin puerto público y con clave privada montada como secreto):

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    restart: unless-stopped
    env_file: .env
    environment:
      APP_ENV: production
      PORT: "80"
      RESULTADO_SIGNING_PRIVATE_KEY: /run/secrets/resultado-private.pem
      RESULTADO_SIGNING_PUBLIC_KEY: /run/secrets/resultado-public.pem
    ports:
      - "127.0.0.1:8080:80"
    volumes:
      - /etc/tornalyx/keys/resultado-private.pem:/run/secrets/resultado-private.pem:ro
      - /etc/tornalyx/keys/resultado-public.pem:/run/secrets/resultado-public.pem:ro
```

Si no se usará la firma, quita las dos variables y los dos montajes. Instala los archivos de clave con propietario root y permisos restrictivos (privada `0600`, pública `0644`). La cuenta DML de runtime no altera el esquema: el DBA aplica la migración con la cuenta DDL antes de publicar código que la necesita. En una base existente, prepara `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_DDL_USER` y `DB_USER` (cuenta DML) en la sesión del DBA; las contraseñas las solicita el cliente MySQL de forma interactiva. No las cargues en argumentos ni subas variables al repo. Para `add_firma_resultados.sql`, desde el checkout:

```bash
mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_DDL_USER" -p "$DB_NAME" \
  < SGDM/backend/database/migrations/add_firma_resultados.sql
```

El comando solicita la contraseña interactivamente. Luego registra la migración con la cuenta DML de aplicación:

```bash
mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" -p "$DB_NAME" \
  -e "INSERT INTO schema_migrations (filename,error) VALUES ('add_firma_resultados.sql',NULL) ON DUPLICATE KEY UPDATE error=NULL"
```

El usuario `tornalyx_ddl` de `dcl.sql` está limitado a `localhost`; si la BD vive en otro host, el DBA debe limitar su origen a la IP de la aplicación o ejecutar la migración desde el servidor de base. No otorgues `ALTER` al usuario runtime.

En una instalación nueva, `schema.sql` ya trae esas columnas y marca la migración aplicada; no repitas el `ALTER`.

Construir y revisar:

```bash
sudo docker compose -f compose.server.yml config
sudo docker compose -f compose.server.yml up -d --build
sudo docker compose -f compose.server.yml ps
sudo docker compose -f compose.server.yml logs --tail=100 app
```

`compose.server.yml` es el archivo de despliegue a crear en el host (no se incluye en el repositorio porque requiere dominio, DB y secretos de cada instalación). Configura restart policy y límites de recursos según capacidad del servidor. Las imágenes oficiales de Docker tienen instalador y guía de postinstalación ([instalación](https://docs.docker.com/engine/install/), [servicio al arranque](https://docs.docker.com/engine/install/linux-postinstall/)).

## 4. Apache inverso y certificado TLS

Instala Apache del host como proxy hacia el puerto loopback de la app (por ejemplo `127.0.0.1:8080`). Habilita módulos `proxy`, `proxy_http`, `headers`, `rewrite` y `ssl`. Crea `/etc/httpd/conf.d/tornalyx.conf`:

```apache
<VirtualHost *:80>
    ServerName tornalyx.example.org
    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8080/
    ProxyPassReverse / http://127.0.0.1:8080/
    RequestHeader set X-Forwarded-Proto "http"
</VirtualHost>
```

El VirtualHost TLS que Certbot configure debe incluir los mismos `ProxyPass` y `ProxyPassReverse`, pero con `RequestHeader set X-Forwarded-Proto "https"`. El proxy debe reemplazar (no anexar) cualquier valor recibido del cliente para ese header; esto mantiene las cookies `Secure` y los enlaces de correo en HTTPS. Inicia Apache y valida sintaxis con `sudo apachectl configtest` antes de recargar `httpd`.

Para Certbot con Apache en AlmaLinux 8, instalar en virtualenv siguiendo el procedimiento RPM de Certbot; sus instrucciones oficiales listan Python, `augeas-devel`, `certbot` y `certbot-apache`, luego `certbot --apache` y renovación programada ([instrucciones oficiales](https://certbot.eff.org/instructions?os=pip&ws=apache)). Confirmar DNS y que el puerto 80 llegue al host antes de solicitar el certificado:

```bash
sudo dnf install -y python3 python3-devel augeas-devel gcc httpd mod_ssl
sudo python3 -m venv /opt/certbot
sudo /opt/certbot/bin/pip install --upgrade pip
sudo /opt/certbot/bin/pip install certbot certbot-apache
sudo ln -s /opt/certbot/bin/certbot /usr/local/bin/certbot
sudo systemctl enable --now httpd
sudo certbot --apache -d tornalyx.example.org
sudo certbot renew --dry-run
sudo tee /etc/cron.d/certbot-renewal >/dev/null <<'EOF'
0 0,12 * * * root /opt/certbot/bin/certbot renew -q
EOF
sudo systemctl enable --now crond
```

Verifica desde fuera `https://tornalyx.example.org`, cadena y fecha del certificado, redirección HTTP→HTTPS, renovación y cookies `Secure`. HSTS se habilita solo tras probar HTTPS en todos los subdominios. Si el proveedor ya termina TLS, no dupliques este proxy ni administres certificados dentro del contenedor.

## 5. Fail2ban para SSH

Habilita EPEL e instala Fail2ban. Usa un archivo local, no modifiques `jail.conf`. En AlmaLinux, confirma primero si los intentos SSH llegan a journald o a archivo y configura backend/logpath correspondiente; Fail2ban admite backend `systemd` cuando el journal está disponible ([proyecto y configuración](https://github.com/fail2ban/fail2ban), [referencia de backend](https://github.com/fail2ban/fail2ban/blob/master/man/jail.conf.5)).

```bash
sudo dnf install -y epel-release
sudo dnf install -y fail2ban
```

Crear `/etc/fail2ban/jail.d/sshd.local` (usa `backend = systemd` si el paquete incluye bindings y sshd registra al journal; de lo contrario configura el logpath real):

```ini
[sshd]
enabled = true
port = ssh
backend = systemd
findtime = 10m
maxretry = 5
bantime = 1h
```

```bash
sudo fail2ban-client -t
sudo systemctl enable --now fail2ban
sudo fail2ban-client status
sudo fail2ban-client status sshd
sudo journalctl -u fail2ban --since today
```

No hagas pruebas de fuerza bruta contra el propio servidor. Verifica que el servicio está activo y que el jail lee el log correcto.

## 6. ClamAV

ClamAV para AlmaLinux requiere EPEL. Los paquetes `clamav`, `clamd` y `clamav-update` se instalan desde ese repositorio ([documentación oficial de paquetes](https://docs.clamav.net/manual/Installing/Packages.html)).

```bash
sudo dnf install -y epel-release
sudo dnf install -y clamav clamd clamav-update
```

Revisa los archivos de configuración instalados por el RPM y quita la directiva de ejemplo si está presente; luego actualiza firmas y ejecuta primero un análisis manual acotado:

```bash
sudo freshclam
sudo clamscan --infected --recursive /opt/tornalyx/SGDM
```

Programa una actualización de firmas y un escaneo diario con `systemd timer`. Crea `/usr/local/sbin/tornalyx-clamav-scan`:

```bash
#!/usr/bin/env bash
set -u
LOG=/var/log/clamav/tornalyx-scan.log
install -d -m 0750 /var/log/clamav
/usr/bin/freshclam >>"$LOG" 2>&1 || exit $?
/usr/bin/clamscan --infected --recursive /opt/tornalyx/SGDM >>"$LOG" 2>&1
RESULT=$?
# clamscan: 0 = limpio, 1 = detección, >1 = error de ejecución.
if [ "$RESULT" -gt 1 ]; then exit "$RESULT"; fi
exit 0
```

```bash
sudo chmod 0750 /usr/local/sbin/tornalyx-clamav-scan
```

`/etc/systemd/system/tornalyx-clamav.service`:

```ini
[Unit]
Description=Actualizar firmas y escanear Tornalyx con ClamAV

[Service]
Type=oneshot
ExecStart=/usr/local/sbin/tornalyx-clamav-scan
```

`/etc/systemd/system/tornalyx-clamav.timer`:

```ini
[Unit]
Description=Escaneo diario de archivos Tornalyx

[Timer]
OnCalendar=daily
RandomizedDelaySec=1h
Persistent=true

[Install]
WantedBy=timers.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now tornalyx-clamav.timer
sudo systemctl list-timers tornalyx-clamav.timer
sudo systemctl start tornalyx-clamav.service
sudo journalctl -u tornalyx-clamav.service
```

No borres automáticamente archivos detectados: revisar la salida, aislar manualmente si corresponde y consultar falsos positivos con el responsable. Configura exclusiones mínimas para datos volátiles de contenedor; nunca excluyas todo el árbol sin justificación.

## 7. Monitoreo con Grafana

Instala Grafana desde su repositorio RPM oficial y mantenlo actualizado ([guía oficial RHEL/Fedora](https://grafana.com/docs/grafana/latest/setup-grafana/installation/redhat-rhel-fedora/)). Grafana necesita una fuente de métricas; aquí se configura Prometheus y Node Exporter localmente. Prometheus y el exporter escuchan solo en loopback, la consola de Grafana no se publica en Internet. Prometheus documenta ambos servicios y un scrape local como configuración inicial ([guía Node Exporter](https://prometheus.io/docs/guides/node-exporter/), [descargas oficiales](https://prometheus.io/download/)).

Instala Node Exporter y Prometheus desde el [sitio oficial de descargas](https://prometheus.io/download/). Los números siguientes son versiones de referencia; consulta el sitio oficial, selecciona versiones estables para la arquitectura del host y verifica el SHA256 publicado antes de instalar.

```bash
TORNALYX_PROM_VERSION=3.14.0
TORNALYX_NODE_VERSION=1.10.2
cd /tmp
curl -fLO "https://github.com/prometheus/prometheus/releases/download/v${TORNALYX_PROM_VERSION}/prometheus-${TORNALYX_PROM_VERSION}.linux-amd64.tar.gz"
curl -fLO "https://github.com/prometheus/node_exporter/releases/download/v${TORNALYX_NODE_VERSION}/node_exporter-${TORNALYX_NODE_VERSION}.linux-amd64.tar.gz"
sha256sum prometheus-*.linux-amd64.tar.gz node_exporter-*.linux-amd64.tar.gz
```

Compara los hashes con los publicados para esas versiones; no continúes si difieren. Extrae los archivos y crea los usuarios/rutas:

```bash
tar -xzf "prometheus-${TORNALYX_PROM_VERSION}.linux-amd64.tar.gz"
tar -xzf "node_exporter-${TORNALYX_NODE_VERSION}.linux-amd64.tar.gz"
sudo useradd --system --no-create-home --shell /sbin/nologin prometheus
sudo useradd --system --no-create-home --shell /sbin/nologin node_exporter
sudo install -d -o prometheus -g prometheus /etc/prometheus /var/lib/prometheus
sudo install -m 0755 "prometheus-${TORNALYX_PROM_VERSION}.linux-amd64/prometheus" /usr/local/bin/prometheus
sudo install -m 0755 "prometheus-${TORNALYX_PROM_VERSION}.linux-amd64/promtool" /usr/local/bin/promtool
sudo install -m 0755 "node_exporter-${TORNALYX_NODE_VERSION}.linux-amd64/node_exporter" /usr/local/bin/node_exporter
sudo chown root:root /usr/local/bin/prometheus /usr/local/bin/promtool /usr/local/bin/node_exporter
```

Servicio para Node Exporter:

```bash
sudo useradd --system --no-create-home --shell /sbin/nologin node_exporter
sudo chown root:root /usr/local/bin/node_exporter
sudo chmod 0755 /usr/local/bin/node_exporter
```

`/etc/systemd/system/node_exporter.service`:

```ini
[Unit]
Description=Prometheus Node Exporter
After=network-online.target

[Service]
User=node_exporter
Group=node_exporter
ExecStart=/usr/local/bin/node_exporter --web.listen-address=127.0.0.1:9100
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

Configuración mínima en `/etc/prometheus/prometheus.yml`:

```yaml
global:
  scrape_interval: 15s
scrape_configs:
  - job_name: prometheus
    static_configs:
      - targets: ["127.0.0.1:9090"]
  - job_name: node
    static_configs:
      - targets: ["127.0.0.1:9100"]
```

Servicio `/etc/systemd/system/prometheus.service`:

```ini
[Unit]
Description=Prometheus metrics collector
After=network-online.target

[Service]
User=prometheus
Group=prometheus
ExecStart=/usr/local/bin/prometheus --config.file=/etc/prometheus/prometheus.yml --storage.tsdb.path=/var/lib/prometheus --web.listen-address=127.0.0.1:9090
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now node_exporter prometheus
curl --fail http://127.0.0.1:9100/metrics
curl --fail http://127.0.0.1:9090/-/healthy
```

Configura Grafana con Prometheus `http://127.0.0.1:9090`, importa un dashboard Linux Node Exporter y define alertas de disco, memoria, CPU y `up == 0`. Para estado de contenedores, añade un exporter Docker compatible y limita su socket/acceso con cuidado; no concedas acceso al socket Docker a usuarios del dashboard. Grafana documenta el repositorio RPM ([guía oficial](https://grafana.com/docs/grafana/latest/setup-grafana/installation/redhat-rhel-fedora/)).

Para Grafana RPM:

```bash
sudo curl -fsSL https://rpm.grafana.com/gpg.key -o /tmp/grafana.gpg
sudo rpm --import /tmp/grafana.gpg
sudo tee /etc/yum.repos.d/grafana.repo >/dev/null <<'EOF'
[grafana]
name=grafana
baseurl=https://rpm.grafana.com
repo_gpgcheck=1
enabled=1
gpgcheck=1
gpgkey=https://rpm.grafana.com/gpg.key
sslverify=1
EOF
sudo dnf install -y grafana
sudo systemctl enable --now grafana-server
sudo systemctl status grafana-server
```

Configura una fuente de Prometheus privada y un dashboard con disponibilidad HTTP, CPU, memoria, disco, espacio de volúmenes Docker, reinicios de contenedores, logs de Apache y disponibilidad/conexión de MySQL. Define alertas con destinatario de guardia. La contraseña inicial de Grafana se cambia al primer acceso; no uses `admin` en operación.

## 8. Verificación final y evidencia

```bash
getenforce
sudo firewall-cmd --list-all
sudo systemctl --no-pager --full status firewalld docker httpd fail2ban grafana-server
sudo fail2ban-client status sshd
sudo docker compose -f compose.server.yml ps
sudo docker compose -f compose.server.yml logs --tail=100 app
sudo certbot renew --dry-run
```

Para activar firma de resultados, generar un par RSA de 3072 bits en un equipo administrativo, nunca dentro del repositorio:

```bash
umask 077
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out resultado-private.pem
openssl pkey -in resultado-private.pem -pubout -out resultado-public.pem
```

Instala las claves en `/etc/tornalyx/keys/` como archivos root-owned (privada `0600`, pública `0644`) y monta ambos archivos en el contenedor como `/run/secrets/resultado-private.pem` y `/run/secrets/resultado-public.pem`. Reinicia la app y verifica `GET /api/resultado/{id}/verificar`, que informa `valida`, `invalida`, `sin_firma` o `clave_no_disponible`. La firma técnica acredita que el contenido no cambió respecto de esa clave, pero no equivale a firma electrónica cualificada ni acredita identidad legal por sí sola. Para rotar la clave, conserva la pública anterior y el registro de periodo/huella para validar resultados históricos.

Ejecuta además el health check público, comprueba TLS desde un cliente externo, realiza una prueba de restauración de backup en entorno aislado y confirma que los puertos de administración/monitoreo no son accesibles desde Internet. Guarda fecha, hostname, versión, estado de cada control, evidencia redactada y responsable; no incluyas secretos ni IPs sensibles en el repositorio.

## Fuentes oficiales consultadas

- [AlmaLinux — guía posterior a instalación y firewalld](https://wiki.almalinux.org/documentation/after-installation-guide.html)
- [Docker — Engine para RHEL](https://docs.docker.com/engine/install/rhel/)
- [Certbot — instalación Apache con pip en sistemas RPM](https://certbot.eff.org/instructions?os=pip&ws=apache)
- [Fail2ban — proyecto y configuración](https://github.com/fail2ban/fail2ban)
- [ClamAV — paquetes EPEL para AlmaLinux](https://docs.clamav.net/manual/Installing/Packages.html)
- [Grafana — repositorio RPM](https://grafana.com/docs/grafana/latest/setup-grafana/installation/redhat-rhel-fedora/)
- [Prometheus — instalación y Node Exporter](https://prometheus.io/docs/guides/node-exporter/)
