# TVF Tracker (Go)

Servidor de tracking de afiliación para TheValueFlow. Recibe clicks, impresiones y postbacks de conversión, los bufferiza en Redis Streams y los persiste en MySQL mediante un worker en batch.

## Requisitos

- Go 1.22+
- Redis (buffer local en AffGoServer)
- MySQL 8 (AffTheValueFlow, red privada `10.5.0.3`)

## Endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/c?o=&p=&s=&sub1..sub5` | Click tracking. Genera `click_id` (ULID) y redirige a la landing. |
| GET | `/i?o=&p=&s=` | Pixel de apertura de email (GIF 1×1). |
| GET | `/li?o=&p=&click_id=` | Impresión de landing (GIF 1×1). |
| GET | `/p?click_id=&payout=&status=&txid=` | Postback de conversión. Responde `OK`. |
| GET | `/internal/allow-host?domain=` | Validación TLS on-demand para Caddy (puerto 8081). |

## Puertos

- **8080** — Tráfico público (proxy Caddy)
- **8081** — Endpoints internos (`/internal/allow-host`)

## Variables de entorno

| Variable | Default | Descripción |
|----------|---------|-------------|
| `REDIS_ADDR` | `127.0.0.1:6379` | Redis local |
| `MYSQL_DSN` | — | DSN completo (alternativa a vars individuales) |
| `MYSQL_HOST` | `10.5.0.3` | IP privada MySQL |
| `MYSQL_PORT` | `3306` | Puerto MySQL |
| `MYSQL_USER` | `tvf` | Usuario MySQL |
| `MYSQL_PASSWORD` | — | **Requerido** si no hay `MYSQL_DSN` |
| `MYSQL_DATABASE` | `tvf` | Base de datos |
| `LISTEN_ADDR` | `:8080` | Servidor de tracking |
| `INTERNAL_LISTEN_ADDR` | `:8081` | Servidor interno |
| `MAXMIND_DB_PATH` | — | Ruta a GeoLite2 (opcional; stub si vacío) |
| `WORKER_BATCH_SIZE` | `500` | Tamaño de batch del worker |
| `WORKER_FLUSH_INTERVAL` | `2s` | Intervalo de flush a MySQL |
| `CONFIG_SYNC_INTERVAL` | `30s` | Intervalo de sync de config desde Redis |
| `CLICK_CACHE_TTL` | `2160h` | TTL cache `click_id` en Redis (90 días) |

## Config en Redis

La app Laravel publica la config en claves Redis que el tracker sincroniza periódicamente:

```
offer:{slug}   → {"id":1,"slug":"demo","status":"active","landing_url":"https://...","payout":10,"revenue":15}
partner:{id}   → {"id":1,"status":"active"}
domain:{host}  → {"domain":"track.example.com","partner_id":1,"status":"active"}
```

## Redis Streams

| Stream | Contenido |
|--------|-----------|
| `tvf:stream:clicks` | Eventos de click |
| `tvf:stream:impressions` | Impresiones email/landing |
| `tvf:stream:conversions` | Conversiones postback |

Consumer group: `tvf-worker`

## Build y despliegue

```bash
cd tracker
go build -o tvf-tracker ./cmd/tracker

# En AffGoServer (/opt/tvf-tracker)
sudo cp tvf-tracker /opt/tvf-tracker/
sudo cp ../infra/tvf-tracker.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now tvf-tracker
```

Ejemplo `.env` en `/opt/tvf-tracker/.env`:

```env
MYSQL_HOST=10.5.0.3
MYSQL_USER=tvf
MYSQL_PASSWORD=secret
MYSQL_DATABASE=tvf
REDIS_ADDR=127.0.0.1:6379
```

## Arquitectura

```
Cliente → Caddy (:443) → Tracker (:8080) → Redis Streams
                                              ↓
                                         Worker (batch)
                                              ↓
                                    MySQL AffTheValueFlow (10.5.0.3)
```

Caddy consulta `http://127.0.0.1:8081/internal/allow-host?domain=` antes de emitir certificados TLS on-demand.

## Tablas MySQL

El worker inserta en:

- `raw_clicks`
- `raw_impressions`
- `conversions`
- `stats_hourly` (upsert agregado por hora)
