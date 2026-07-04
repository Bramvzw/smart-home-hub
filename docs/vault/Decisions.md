# Decision Log

Standing decisions and their rationale. **Consult this before proposing changes that touch these areas** — these choices were made deliberately; don't re-litigate them without new information. Add a new entry whenever a non-obvious choice is made (agents included).

Format: date · decision · rationale · revisit-when.

---

## Security & deployment

| Date | Decision | Rationale | Revisit when |
|---|---|---|---|
| 2025 | **Single-user app, no login/roles.** Access control is network-layer first. | It's a household hub on a private LAN with a kiosk display; a login screen on a wall-mounted kiosk defeats the purpose. | The app becomes multi-household/multi-user. |
| 2025 | **Private-network guard on by default** (`EnsurePrivateNetworkAccess`, CIDR allowlist, only LAN proxies trusted for forwarded headers). | Blocks internet exposure at app level even when someone port-forwards; XFF spoofing can't bypass it. | — |
| 2026-07-04 | **HTTP Basic Auth is opt-in, off by default** (`HUB_AUTH_USERNAME`/`HUB_AUTH_PASSWORD`). | Kiosk-first: forcing auth would break the wall display. It exists as defense-in-depth for untrusted LANs and for users who relax the guard. `/up` stays open for healthchecks. | The demo/public deployment story changes. |
| 2026-07-04 | **Compose binds `0.0.0.0` by default** (`HUB_HTTP_BIND` to override). | The Pi kiosk must reach the NAS over LAN; loopback-only would break the primary use case. Auth + guard cover the LAN risk. | — |
| 2026-07-04 | **`APP_DEBUG` forced `"false"` at container level.** | A real env var beats `.env`, so a stray debug flag can never leak stack traces in production. | — |
| 2026-07-04 | **Cached provider tokens are encrypted at rest** (Spotify, Google, Tuya). Legacy plaintext Tuya entries are discarded (not re-encrypted) — Tuya tokens are cheap to refetch. | Cache/DB files live on NAS storage (SMB shares); credentials must not be usable at rest. | — |
| 2025 | **PII never in the tracked repo** (address, coordinates, NAS login, personal venue choices). Real values live only in untracked `.env`/`.release.env`; code/docs/tests use neutral placeholders (Amsterdam coords). | The repo is public. | — |

## Language & localization

| Date | Decision | Rationale | Revisit when |
|---|---|---|---|
| 2026 | **All UI copy, briefing summaries, notifications and health messages are English.** | Public repo targets an international audience; mixed-language UI reads as unfinished. Tests assert on these strings — update assertions together with copy. | — |
| 2026-07-04 | **AI prompts (Recipes generator, Briefing composer) stay Dutch for now.** Allowlisted in `CopyGuardTest`. | For recipes the prompt language determines the generated menu's language and the owner wants Dutch menus; the briefing prompt already makes output language configurable via `briefing.language`. Plan: make the recipes language configurable in the generalization phase — don't just translate the prompts. | Generalization phase starts. |
| 2026-07-04 | **Internal identifiers `recepten`/`aanbiedingen` (tab keys, `recepten.js`) stay for now.** Visible labels are already English. | Renaming touches Blade + JS + filenames for zero user-facing gain; batch it with the generalization cleanup. | Generalization phase starts. |

## Architecture & quality gates

| Date | Decision | Rationale | Revisit when |
|---|---|---|---|
| 2025 | **Modular monolith** (nwidart/laravel-modules), rules in `AGENTS.md`: Actions/Services/DTOs/ViewModels/Builders, thin controllers, no cross-module imports except via `app/Contracts`. | See AGENTS.md. | — |
| 2025 | **Integrations are pluggable adapters behind contracts** (`LightProvider`, `RetailerAdapter`, `ConcertProvider`, tagged offer providers), registered via container tags, enabled by config keys being present. | Adding a provider = one class + config; modules degrade gracefully without keys. | — |
| 2026-07-04 | **PHPStan (Larastan) level 8 with a baseline as CI gate.** Baseline froze 627 pre-existing errors; new code must be clean. `missingType.iterableValue`/`missingType.generics` are globally ignored (docblock hygiene, later pass); `env()`-in-module-config is ignored because nwidart module configs are real config (cached by `config:cache` like root config). | Highest signal now without a months-long cleanup first; the baseline is a burn-down list — never add to it. | Baseline reaches ~0 → drop ignores, raise strictness. |
| 2026-07-04 | **Quality-gate roadmap order: Larastan → NL-string/PII guards → scheduled health push → PHPat architecture tests → coverage/Infection.** | Ordered by defect-catching value per unit of work. | — |
| 2026-07-04 | **PHPat enforces module isolation in CI** (`tests/Architecture/ArchitectureTest.php`, runs inside `composer analyse`). Three couplings are sanctioned: Entertainment→Spotify (library checks), Lighting→Weather (weather-triggered presets), DashboardController→Briefing ViewModel (dashboard embed). Module tests may integration-test across modules. | These couplings *are* the cross-module intelligence the product is built on; everything new goes through `app/Contracts` or gets added to the sanctioned list here + in the rule, deliberately. | A coupling grows beyond one direction → extract a contract. |

| 2026-07-05 | **Weather reuses the PhonePing ntfy topic as fallback** (`WEATHER_NTFY_* ?: PHONE_PING_NTFY_*`), and `NTFY_DRY_RUN=true` on dev machines logs pushes instead of sending them. | One configured topic covers the whole hub (owner convenience); the dry-run flag exists because a dev `.env` with real topics once pushed test notifications to a real phone. | Generalization: make the topic explicit per module in settings. |

## Deployment & operations

| Date | Decision | Rationale | Revisit when |
|---|---|---|---|
| 2025 | **Deploys only via `make release`; never hand-walk NAS docker commands. Never deploy automatically** — the owner releases explicitly. | Synology quirks (frozen opcache, SQLite-over-SMB, compose path) are encoded in the Makefile; ad-hoc commands break them. | — |
| 2025 | **`composer test`, never `php artisan test` directly.** | The composer script clears config first; bare artisan runs against stale config. | — |
| 2025 | **Commit style: `PREFIX: imperative summary`** (FIX/DOCS/SECURITY/CI/STYLE). No Co-Authored-By lines. | Repo convention. | — |

## Product positioning

| Date | Decision | Rationale | Revisit when |
|---|---|---|---|
| 2026-07-04 | **Positioning: "the self-hosted dashboard that actually does things"** — actuator vs. link-launchers (Homepage/Dashy), AI briefing as flagship, cross-module intelligence as the moat. Compete on depth (modules compose), never on integration breadth vs. Home Assistant. | Analysis of the dashboard landscape; breadth loses and rots, composition is defensible. | — |
| 2026-07-04 | **NL defaults (AH/Lidl, bol/Tweakers, Amsterdam coords) stay as defaults** — documented as overridable, not removed. | They're honest defaults, already env-driven; removing them helps nobody. | — |
| 2026-07-04 | **Hedon venue decoupling is planned, not done**: `hedon` still sits in the relevance enum, briefing logic and views. Target: config-driven `favourite_venues`, `HedonProvider` demoted to an example scraper. | The only place a personal choice leaked into data models. | Generalization phase. |
