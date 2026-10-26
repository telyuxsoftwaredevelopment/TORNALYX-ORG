# Operación y seguridad del servidor

La guía de referencia única para preparar e instalar Tornalyx en el servidor está en [Guía de instalación y hardening](guia-instalacion-servidor.md). Incluye AlmaLinux, Docker, firewall, TLS, Fail2ban, ClamAV, monitoreo, firma de resultados y verificaciones posteriores.

La política de backup de la aplicación debe conservar copia diaria completa con rotación y pruebas de restauración periódicas. No considerar un backup verificado hasta restaurarlo en una instancia aislada y documentar el resultado.
