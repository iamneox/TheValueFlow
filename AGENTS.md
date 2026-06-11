# TheValueFlow — Contexto del proyecto

Plataforma de tracking de afiliación: app de gestión (Laravel 11 + Inertia + Vue 3) y servidor de tracking en Go. Plan completo en `.cursor/plans/`.

## Servidores Hetzner Cloud (Nuremberg)

### AffTheValueFlow — Plataforma (Laravel)
- **IP pública:** 167.233.46.114 | **IP privada:** 10.5.0.3
- **Tipo:** CPX42 (8 vCPU compartidas, 16 GB RAM, 320 GB SSD)
- **Rol:** App de gestión — Laravel 11 + Inertia + Vue 3, MySQL 8, Redis, Horizon, Nginx.
- MySQL y Redis NUNCA escuchan en la IP pública; solo en red privada y Tailscale.

### AffGoServer — Tracker (Go)
- **IP pública:** 167.233.41.29 | **IP privada:** 10.5.0.2
- **Tipo:** CCX23 (4 vCPU dedicadas, 16 GB RAM, 160 GB SSD)
- **Rol:** Servidor de tracking en Go — endpoints `/c`, `/i`, `/li`, `/p`, Redis local como buffer, Caddy con TLS on-demand para los dominios de tracking de los partners.
- Escribe en el MySQL de AffTheValueFlow a través de la red privada de Hetzner.

## Red y acceso
- Ambos servidores deben estar en la MISMA red privada de Hetzner (rango 10.5.0.0/16), cada uno con su IP privada única.
- SSH SOLO vía Tailscale (puerto 22 cerrado en las IPs públicas tras el hardening).
- Usuario de despliegue: `deploy` (login root deshabilitado tras la provisión inicial).
- Solo 80/443 abiertos al público en ambos servidores.
