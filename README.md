# TheValueFlow — Plataforma de Tracking

Plataforma de tracking de afiliación con app de gestión (Laravel 11 + Inertia + Vue 3) y servidor de tracking en Go.

## Estructura

- `app/` — Plataforma Laravel (dashboard, CRUDs, facturación, reporting)
- `tracker/` — Servidor de tracking Go (clicks, impresiones, conversiones)
- `infra/` — Scripts de provisión, deploy y systemd
- `docker-compose.yml` — MySQL + Redis local para desarrollo

## Servidores producción

| Servidor | IP pública | IP privada | Rol |
|----------|------------|------------|-----|
| AffTheValueFlow | 167.233.46.114 | 10.5.0.3 | Laravel + MySQL + Redis |
| AffGoServer | 167.233.41.29 | 10.5.0.2 | Tracker Go + Caddy |

## Desarrollo local

```bash
docker compose up -d
cd app && cp .env.example .env
# Configurar DB mysql tvf / redis
composer install && npm install && npm run build
php artisan migrate --seed
php artisan serve
```

## Deploy

```bash
./infra/deploy/deploy-app.sh
./infra/deploy/deploy-tracker.sh
```

## Credenciales demo (seed)

- Admin: `admin@thevalueflow.com` / `password`
- Partner: `partner@demo.com` / `password`
- Client: `client@demo.com` / `password`

## Tailscale

Instalar y autenticar en ambos servidores:

```bash
tailscale up --auth-key=<KEY>
```

Luego restringir SSH solo a la interfaz Tailscale con `infra/scripts/install-tailscale.sh`.

## Endpoints de tracking

- `GET /c?o={offer_id}&p={partner_id}&s={source}` — Click
- `GET /i?o=&p=&s=` — Impresión email HTML
- `GET /li?o=&p=&click_id=` — Impresión landing
- `GET /p?click_id=&payout=&status=&txid=` — Postback conversión
