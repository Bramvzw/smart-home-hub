# NAS Hosting (Synology + Docker)

De NAS draait de app via Docker. De Mac werkt direct in de NAS-share via SMB — geen git deploy stap nodig voor PHP/Blade wijzigingen. De Pi opent het dashboard in een kiosk-browser.

## Overzicht

```
Mac (PhpStorm) ──SMB──▶ NAS-share ◀── Docker container (poort 8080)
                                              │
                                         Pi kiosk-browser
```

## Mac: SMB-share mounten

1. Finder → **Ga → Verbind met server** → `smb://<nas-ip>/docker`
2. Mount op bijv. `/Volumes/docker`
3. Symlink (eenmalig) zodat PhpStorm op hetzelfde pad blijft werken:
   ```bash
   ln -s /Volumes/docker/smart-home-hub ~/PhpstormProjects/smart-home-hub
   ```

PHP- en Blade-wijzigingen zijn direct live zodra je opslaat. Voor frontend assets:

```bash
npm run build   # schrijft naar public/build op de NAS-share
```

De draaiende container pikt de nieuwe assets meteen op — geen herstart nodig.

## NAS: Docker starten

### Eenmalig (eerste keer)

```bash
# SSH naar de NAS of gebruik Container Manager terminal
cd /volume1/docker/smart-home-hub

# Env instellen (kopieer en vul in)
cp .env.example .env
nano .env   # zie Omgevingsvariabelen hieronder

# App bootstrappen
docker compose run --rm hub php artisan key:generate
docker compose run --rm hub php artisan migrate --force
docker compose run --rm hub php artisan storage:link
```

### Starten

```bash
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

**SQLite-locatie:** `/app/database/database.sqlite` binnen de container, wat overeenkomt met `database/database.sqlite` in de NAS-share. Het bestand overleeft container-herstarts zolang de NAS-share intact is.

**Assets bijwerken:** na `npm run build` op de Mac zijn de nieuwe bestanden direct beschikbaar. Een hard-refresh op de Pi-browser (`Ctrl+Shift+R`) laadt de nieuwe assets.

**Secrets:** de `.env` op de NAS bevat echte credentials en wordt nooit gecommit. Gebruik `.env.example` als sjabloon.
