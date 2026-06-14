# NAS Hosting (Synology + Docker)

Je ontwikkelt **lokaal** op de Mac (Herd, `https://smart-home-hub.test`) en
**releaset periodiek** naar de NAS via git. De NAS draait de app in Docker; de
Pi opent het dashboard in een kiosk-browser.

> Eerdere aanpak (direct in de NAS-share editen via SMB) is verlaten: builds over
> SMB duurden ~75s en een remount kon ongecommit werk wissen. Git is de bron van
> waarheid; de NAS is een deploy-target.

## Overzicht

```
Mac (lokale clone, Herd)  ──git push──▶  GitHub  ──git pull──▶  NAS (Docker, poort 8080)
   smart-home-hub.test                                                    │
                                                                     Pi kiosk-browser
```

## Mac: lokaal ontwikkelen

Eenmalige setup (lokale clone, niet op de NAS-share):

```bash
git clone https://github.com/Bramvzw/smart-home-hub.git ~/PhpstormProjects/smart-home-hub
cd ~/PhpstormProjects/smart-home-hub
composer install
npm install
cp .env.example .env       # of kopieer je bestaande .env
php artisan key:generate
herd link smart-home-hub
herd secure smart-home-hub  # https://smart-home-hub.test
```

Dagelijks ontwikkelen:

```bash
npm run dev    # vite dev-server met hot reload (<1s, geen volledige build)
```

Lokaal bouwt vite in <1s (lokale SSD) — vs. ~75s over de oude SMB-mount.

## Release naar de NAS

Lokaal afronden, assets meebouwen en committen, dan op de NAS pullen:

```bash
# lokaal
npm run build
git add -A
git commit -m "..."
git push

# op de NAS (SSH of Container Manager terminal)
cd /volume1/docker/smart-home-hub
git pull
docker compose exec hub php artisan migrate --force   # alleen bij nieuwe migraties
make cache                                             # caches opwarmen
```

De gebouwde assets in `public/build` worden meegecommit, dus de NAS hoeft niet
te bouwen — `git pull` volstaat. Een hard-refresh op de Pi (`Ctrl+Shift+R`)
laadt de nieuwe assets.

> Houd de NAS-checkout schoon: maak er geen lokale edits in, zodat `git pull`
> nooit botst. De NAS is puur een deploy-target.

## NAS: Docker (eenmalige setup)

```bash
# SSH naar de NAS of gebruik de Container Manager terminal
cd /volume1/docker/smart-home-hub

# Env instellen (kopieer en vul in)
cp .env.example .env
nano .env   # zie Omgevingsvariabelen hieronder

# App bootstrappen
docker compose run --rm hub php artisan key:generate
docker compose run --rm hub php artisan migrate --force
docker compose run --rm hub php artisan storage:link

# Starten
docker compose up -d
docker compose ps   # hub moet 'healthy' zijn na ~20 seconden
```

### Omgevingsvariabelen (minimaal in `.env`)

```env
APP_KEY=base64:...          # gegenereerd door key:generate
APP_URL=http://<nas-ip>:8080
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite

PRIVATE_NETWORK_GUARD_ENABLED=true
PRIVATE_NETWORK_ALLOWED_CIDRS=127.0.0.1/32,::1/128,192.168.68.0/24
```

Pas de CIDR aan als het thuisnetwerk een ander subnet gebruikt.

### Dagelijks beheer

```bash
make logs       # container logs volgen
make restart    # container herstarten
make cache      # Laravel caches opwarmen na config-wijziging
make shell      # shell in de container openen
```

## Pi: kiosk-browser instellen

1. Reserveer een vast IP voor de NAS in de router.
2. Open de browser in kiosk-modus:
   ```bash
   chromium-browser --kiosk --noerrdialogs --disable-infobars \
     http://<nas-ip>:8080
   ```
3. Optioneel: voeg toe aan `/etc/xdg/lxsession/LXDE-pi/autostart` voor automatisch starten.

## Operationeel

**SQLite-locatie:** `/app/database/database.sqlite` binnen de container, wat
overeenkomt met `database/database.sqlite` in de NAS-checkout. Het bestand
overleeft container-herstarts. Let op: `database.sqlite` is git-genegeerd, dus
het blijft per-omgeving (lokaal ≠ NAS) — een `git pull` raakt je NAS-data niet.

**Secrets:** de `.env` op de NAS bevat echte credentials en wordt nooit
gecommit. Gebruik `.env.example` als sjabloon.
