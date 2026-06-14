# Raspberry Pi Hosting

> **Alternatief:** als de NAS altijd aan staat, overweeg dan de app op de NAS te hosten en de Pi alleen als kiosk-browser te gebruiken. Zie [NAS Hosting.md](NAS%20Hosting.md).



The Raspberry Pi should host the dashboard for always-on local access. Do not
depend on a MacBook running Herd for the production-like home setup.

Recommended local URL:

```text
https://smart-home-hub.test
```

## Network

Reserve a fixed IP for the Pi in the router, then point local clients to it:

```text
192.168.68.112 smart-home-hub.test
```

Add that line to `/etc/hosts` on devices that need to open the dashboard, or
configure it in local DNS if the router supports custom hostnames.

The Laravel private-network guard is controlled by:

```env
PRIVATE_NETWORK_GUARD_ENABLED=true
PRIVATE_NETWORK_ALLOWED_CIDRS=127.0.0.1/32,::1/128,192.168.68.0/24
```

Update the CIDR if the home subnet changes.

## Runtime Shape

Run Laravel directly on the Pi with:

- PHP-FPM
- Caddy or Nginx as the web server
- SQLite or another configured database
- built Vite assets in `public/build`

Build frontend assets on the development machine or on the Pi:

```bash
npm run build
```

On the Pi, keep dependencies production-oriented:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Use systemd services for any long-running queue workers if modules start using
queued jobs.

## HTTPS

For local HTTPS, generate or configure a certificate for
`smart-home-hub.test` on the Pi. Browsers must trust the local CA or certificate
authority used for that certificate.

Do not port-forward ports 80 or 443 from the public internet to the Pi unless a
separate authentication and update strategy is added.
