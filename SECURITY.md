# Security Policy

## Reporting a vulnerability

Please report vulnerabilities privately via [GitHub Security Advisories](https://github.com/Bramvzw/smart-home-hub/security/advisories/new) — do not open a public issue for security problems. You can expect an initial response within a week.

## Deployment model & hardening

This hub is designed as a **single-user LAN application** (NAS + kiosk display). Defense layers, from the outside in:

1. **Private-network guard** (on by default): requests from outside the configured CIDRs get a 403. Configure via `PRIVATE_NETWORK_GUARD_ENABLED` and `PRIVATE_NETWORK_ALLOWED_CIDRS`. Forwarded headers are only trusted from LAN proxies, so the guard cannot be bypassed by spoofing `X-Forwarded-For`.
2. **HTTP Basic Auth** (optional): set `HUB_AUTH_USERNAME` and `HUB_AUTH_PASSWORD` to require credentials on every request. Recommended whenever untrusted devices share your LAN, and required if you relax the private-network guard. The `/up` health endpoint stays open for container healthchecks.
3. **Container binding**: by default the app listens on all interfaces (port 8080) so a kiosk can reach it. Set `HUB_HTTP_BIND=127.0.0.1` to only expose it through a local reverse proxy.

### Do

- Keep the private-network guard enabled unless you fully understand the consequences.
- Use a reverse proxy with TLS (and ideally its own auth) if you want remote access — never port-forward the container directly to the internet.
- Set a strong `APP_KEY` and keep it stable: OAuth tokens (Spotify, Google) and cached provider tokens are encrypted with it.

### Don't

- Don't expose the hub directly to the internet: it controls physical devices and holds API credentials for paid services.
- Don't commit your `.env` — all secrets (API keys, OAuth client secrets, coordinates) belong there and only there.

## Scope notes

- The app is single-user by design; there is no role/permission model. Anyone who can reach it (and passes the layers above) can do everything it does.
- Price/offer providers that scrape or use unofficial APIs are best-effort integrations and are disabled unless configured.
