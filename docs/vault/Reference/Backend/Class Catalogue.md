# Backend Class Catalogue

Inventory of app and module classes that define the architecture. Update this when adding or removing first-class PHP classes.

---

## Shared App

| Class | Responsibility |
|---|---|
| `App\Contracts\ModuleContract` | Contract implemented by module service providers for dashboard registration. |
| `App\Contracts\BriefingSource` | Shared contract for modules that contribute sections to the daily briefing. |
| `App\Services\ModuleRegistry` | Collects module metadata and navigation entries. |
| `App\Services\Ntfy\HubNotifier` | Shared ntfy transport for new hub-level notifications. |
| `App\Support\Briefing\BriefingSection` | Shared DTO for a contributed briefing section. |
| `App\Providers\ModuleServiceProvider` | Base provider for modules. |
| `App\Providers\DashboardServiceProvider` | Dashboard-level service provider. |
| `App\Http\Middleware\EnsurePrivateNetworkAccess` | Global HTTP guard that restricts the dashboard to configured private-network CIDR ranges. |
| `App\View\Components\Dashboard\Layout` | Shared dashboard layout component. |
| `App\Http\Controllers\DashboardController` | Dashboard route controller. |
| `App\Models\User` | Laravel user model. |

## Spotify Module

| Class | Responsibility |
|---|---|
| `Modules\Spotify\Providers\SpotifyServiceProvider` | Registers Spotify module metadata and service bindings. |
| `Modules\Spotify\Http\Controllers\SpotifyController` | Thin HTTP boundary for Spotify pages and JSON actions. |
| `Modules\Spotify\Services\SpotifyService` | Backward-compatible facade over smaller Spotify services. |
| `Modules\Spotify\Services\SpotifyTokenService` | OAuth authorization URL, access token and refresh token behavior. |
| `Modules\Spotify\Services\SpotifyApiClient` | Raw Spotify API transport, auth retry and transport errors. |
| `Modules\Spotify\Services\SpotifyPlaybackService` | Playback, queue, devices and player commands. |
| `Modules\Spotify\Services\SpotifyLibraryService` | Playlists, search and saved-track library behavior. |
| `Modules\Spotify\Events\PlaybackChanged` | Event emitted after playback state changes. |
| `Modules\Spotify\Events\TrackLiked` | Event emitted after liked-track state changes. |
| `Modules\Spotify\View\Components\*` | Blade components for Spotify player UI. |
| `Modules\Spotify\View\ViewModels\*` | Presentation view objects for Spotify component fragments. |
| `Modules\Spotify\Http\Requests\*` | Request validation for Spotify endpoints. |

## Lighting Module

Status: active. This module owns cloud-backed light controls at `/lighting`.

| Class | Responsibility |
|---|---|
| `Modules\Lighting\Providers\LightingServiceProvider` | Registers Lighting module metadata, routes and dashboard navigation. |
| `Modules\Lighting\Http\Controllers\LightingController` | Thin HTTP boundary for the Lighting page and per-light JSON updates. |
| `Modules\Lighting\Http\Requests\UpdateLightRequest` | Validation and colour normalisation for per-light update requests. |
| `Modules\Lighting\Actions\ControlLight` | Write action for dispatching validated light changes to the owning provider. |
| `Modules\Lighting\Actions\ApplyLightingPreset` | Write action for applying configured presets across reachable provider lights. |
| `Modules\Lighting\Contracts\LightProvider` | Shared provider contract for listing and controlling lights. |
| `Modules\Lighting\Data\Light` | Shared light DTO used by providers, views and JSON resources. |
| `Modules\Lighting\Data\LightPreset` | Typed preset definition used by the service, view model and JSON preset response. |
| `Modules\Lighting\Data\LightingPresetResult` | Typed result for applied, skipped and failed preset targets. |
| `Modules\Lighting\Data\LightingSnapshot` | Aggregated light list plus unreachable provider labels. |
| `Modules\Lighting\Exceptions\LightingControlBusy` | Exception for write requests that cannot enter the Lighting control queue in time. |
| `Modules\Lighting\Exceptions\UnknownLightingPreset` | Exception for unknown configured preset keys. |
| `Modules\Lighting\Http\Resources\LightResource` | Stable JSON transformer for light update responses. |
| `Modules\Lighting\Services\LightingService` | Aggregates configured providers, caches snapshots and isolates failures. |
| `Modules\Lighting\Services\Providers\TuyaApiClient` | Signed Tuya Cloud OpenAPI transport. |
| `Modules\Lighting\Services\Providers\TuyaTokenService` | Tuya access token caching and refresh. |
| `Modules\Lighting\Services\Providers\TuyaProvider` | Calex/Tuya provider mapping for power, brightness and colour. |
| `Modules\Lighting\Services\Providers\GoveeApiClient` | Govee Developer API transport with API-key headers. |
| `Modules\Lighting\Services\Providers\GoveeProvider` | Govee provider mapping for power, brightness and RGB colour. |
| `Modules\Lighting\Support\Color` | Colour conversion helpers for shared hex, Govee RGB and Tuya HSV. |
| `Modules\Lighting\View\ViewModels\LightingViewModel` | Read-side page data assembly for the Lighting screen. |

## Calendar Module

Status: active. This module owns read-only ICS calendar feeds at `/calendar`.

| Class | Responsibility |
|---|---|
| `Modules\Calendar\Providers\CalendarServiceProvider` | Registers Calendar module metadata, routes and dashboard navigation. |
| `Modules\Calendar\Http\Controllers\CalendarController` | Thin HTTP boundary for the Calendar page. |
| `Modules\Calendar\Services\CalendarService` | Fetches, caches, parses and merges configured ICS feeds. |
| `Modules\Calendar\Data\CalendarFeed` | Merged calendar feed result with stale-feed metadata. |
| `Modules\Calendar\Data\CalendarEvent` | Typed calendar event DTO. |
| `Modules\Calendar\Data\CalendarSource` | Configured calendar source identity. |
| `Modules\Calendar\View\ViewModels\CalendarViewModel` | Read-side page data assembly for the Calendar screen. |
| `Modules\Calendar\Briefing\CalendarBriefingSource` | Today's agenda contribution for the daily briefing. |

## Weather Module

Status: active. This module owns rainfall monitoring and ntfy alerts at `/weather`.

| Class | Responsibility |
|---|---|
| `Modules\Weather\Providers\WeatherServiceProvider` | Registers Weather module metadata, service bindings and dashboard navigation. |
| `Modules\Weather\Http\Controllers\WeatherController` | Thin HTTP boundary for the Weather page. |
| `Modules\Weather\Actions\CheckRainForecast` | Scheduled action that checks the forecast and sends rain alerts when needed. |
| `Modules\Weather\Actions\CheckWindForecast` | Scheduled action that checks the forecast and sends hard-wind alerts when needed. |
| `Modules\Weather\Actions\SendDailyWeatherSummary` | Scheduled action that sends the morning weather summary. |
| `Modules\Weather\Data\WeatherForecast` | Typed forecast snapshot for the configured location. |
| `Modules\Weather\Data\WeatherHour` | Typed hourly forecast block with rain-threshold detection. |
| `Modules\Weather\Data\WeatherDay` | Typed daily forecast summary for today/tomorrow cards and morning summaries. |
| `Modules\Weather\Data\RainAlertResult` | Typed result for scheduled rain-alert checks. |
| `Modules\Weather\Data\WeatherAlertResult` | Typed result for non-rain weather notifications. |
| `Modules\Weather\Services\OpenMeteoClient` | Open-Meteo forecast API transport. |
| `Modules\Weather\Services\NtfyWeatherNotifier` | ntfy transport for weather alerts. |
| `Modules\Weather\Services\WeatherService` | Maps forecasts, evaluates rain windows and owns alert cooldown/period state. |
| `Modules\Weather\Support\WeatherCode` | WMO weather-code labels for UI and notifications. |
| `Modules\Weather\View\ViewModels\WeatherViewModel` | Read-side page data assembly for the Weather screen. |
| `Modules\Weather\Briefing\WeatherBriefingSource` | Weather contribution for the daily briefing. |

## News Module

Status: active. This module owns RSS/Atom news aggregation, read tracking and keyword alerts at `/news`.

| Class | Responsibility |
|---|---|
| `Modules\News\Providers\NewsServiceProvider` | Registers News module metadata, routes, migrations, config and dashboard unread status. |
| `Modules\News\Http\Controllers\NewsController` | Thin HTTP boundary for the News page, JSON state, read actions and manual refresh. |
| `Modules\News\Http\Resources\NewsItemResource` | Stable JSON transformer for stored news items. |
| `Modules\News\Actions\RefreshFeeds` | Manual/scheduled action that refreshes configured feeds. |
| `Modules\News\Actions\CheckNewsKeywords` | Sends shared ntfy alerts for unnotified keyword matches. |
| `Modules\News\Actions\MarkItemsRead` | Marks selected news items read. |
| `Modules\News\Actions\MarkAllRead` | Marks all unread items read globally or per topic. |
| `Modules\News\Data\RawFeedItem` | Normalized feed item DTO before persistence. |
| `Modules\News\Data\NewsRefreshResult` | Typed summary for fetched, stored, skipped and failed feed refresh work. |
| `Modules\News\Exceptions\FeedUnavailable` | Typed exception for feed request or parse failures. |
| `Modules\News\Models\NewsItem` | Stored RSS/Atom item with global read state, notification state and keyword matches. |
| `Modules\News\Models\Builders\NewsItemBuilder` | Reusable unread, topic, recency and latest-per-topic query filters. |
| `Modules\News\Services\FeedClient` | HTTP feed transport and `laminas-feed` parser. |
| `Modules\News\Services\NewsService` | Feed iteration, failure isolation, dedupe, keyword matching and retention pruning. |
| `Modules\News\View\ViewModels\NewsViewModel` | Read-side topic grouping, unread counts and last refreshed state. |
| `Modules\News\Briefing\NewsBriefingSource` | Unread news contribution for the daily briefing. |

## Briefing Module

Status: active. This module owns daily Dutch morning briefings at `/briefing`.

| Class | Responsibility |
|---|---|
| `Modules\Briefing\Providers\BriefingServiceProvider` | Registers Briefing module metadata and binds the Prism text generator. |
| `Modules\Briefing\Http\Controllers\BriefingController` | Thin HTTP boundary for today's briefing and manual regenerate. |
| `Modules\Briefing\Http\Resources\BriefingResource` | Stable JSON transformer for the briefing contract. |
| `Modules\Briefing\Actions\GenerateBriefing` | Orchestrates source collection, composition, persistence, pruning and optional ntfy push. |
| `Modules\Briefing\Contracts\BriefingTextGenerator` | Testable interface for the AI text generator. |
| `Modules\Briefing\Data\ComposedBriefing` | Typed result for generated body, model and fallback state. |
| `Modules\Briefing\Models\Briefing` | Stored daily briefing with sections, generated timestamp, model and fallback flag. |
| `Modules\Briefing\Services\BriefingSourceRegistry` | Resolves tagged sources, filters nulls, isolates failures and sorts sections. |
| `Modules\Briefing\Services\BriefingComposer` | AI-first composer that falls back to templated output on failure. |
| `Modules\Briefing\Services\PrismBriefingTextGenerator` | Prism/Anthropic text-generation adapter. |
| `Modules\Briefing\Services\TemplatedBriefingComposer` | Deterministic non-AI Dutch fallback composer. |
| `Modules\Briefing\View\ViewModels\BriefingViewModel` | Read-side state for today's briefing page. |

## Recipes Module

Status: active. This module owns weekly supermarket-offer recipes at `/recipes`.

| Class | Responsibility |
|---|---|
| `Modules\Recipes\Providers\RecipesServiceProvider` | Registers Recipes metadata, offer providers and the Prism recipe generator binding. |
| `Modules\Recipes\Http\Controllers\RecipesController` | Thin HTTP boundary for the Recipes page, offers, recipe details and manual generation. |
| `Modules\Recipes\Http\Resources\RecipeResource` | Stable JSON transformer for generated recipes and shopping lists. |
| `Modules\Recipes\Http\Resources\OfferResource` | Stable JSON transformer for stored grocery offers. |
| `Modules\Recipes\Actions\FetchOffers` | Runs configured offer providers for the current week. |
| `Modules\Recipes\Actions\GenerateRecipes` | Fetches or reuses offers, generates recipes, records fallback state and sends ntfy. |
| `Modules\Recipes\Contracts\OfferProvider` | Store-source contract for normalized supermarket offer providers. |
| `Modules\Recipes\Contracts\RecipeTextGenerator` | Testable interface for AI recipe generation. |
| `Modules\Recipes\Data\OfferData` | Normalized grocery offer DTO before persistence. |
| `Modules\Recipes\Data\OfferFetchResult` | Typed result for stores fetched, stores failed and offer count. |
| `Modules\Recipes\Data\GeneratedRecipeSet` | Typed result for generated recipes plus model name. |
| `Modules\Recipes\Exceptions\OfferSourceUnavailable` | Typed exception for unavailable or invalid store sources. |
| `Modules\Recipes\Models\GroceryOffer` | Stored supermarket offer for an ISO week. |
| `Modules\Recipes\Models\Recipe` | Stored generated recipe with ingredients, steps and shopping list. |
| `Modules\Recipes\Models\RecipeRun` | Per-week generation/store status, including AI-unavailable fallback state. |
| `Modules\Recipes\Services\AlbertHeijnOfferProvider` | Best-effort Albert Heijn offer source using anonymous token flow. |
| `Modules\Recipes\Services\LidlOfferProvider` | Best-effort Lidl offer source using the configured offers JSON endpoint. |
| `Modules\Recipes\Services\OfferAggregator` | Runs offer providers, isolates store failures and upserts weekly offers. |
| `Modules\Recipes\Services\RecipeGenerator` | Validates generated recipe payloads and persists recipes. |
| `Modules\Recipes\Services\PrismRecipeTextGenerator` | Prism/Anthropic JSON recipe-generation adapter. |
| `Modules\Recipes\View\ViewModels\RecipesViewModel` | Read-side weekly recipe and offer state assembly. |

## Deals Module

Status: active. This module owns price watchlists at `/deals`.

| Class | Responsibility |
|---|---|
| `Modules\Deals\Providers\DealsServiceProvider` | Registers Deals metadata, retailer adapters and service bindings. |
| `Modules\Deals\Http\Controllers\DealsController` | Thin HTTP boundary for watchlist, matching review, history and manual checks. |
| `Modules\Deals\Http\Resources\WatchedProductResource` | Stable JSON transformer for watched products. |
| `Modules\Deals\Http\Resources\ProductListingResource` | Stable JSON transformer for retailer listings. |
| `Modules\Deals\Actions\AddWatchedProduct` | Creates a product and unconfirmed retailer candidate listings. |
| `Modules\Deals\Actions\ConfirmListing` | Confirms a candidate listing for tracking. |
| `Modules\Deals\Actions\RemoveListing` | Removes a wrong candidate listing. |
| `Modules\Deals\Actions\CheckPrices` | Checks confirmed listings and sends one ntfy per price drop. |
| `Modules\Deals\Contracts\RetailerAdapter` | Retailer source contract for search and price lookup. |
| `Modules\Deals\Data\ListingCandidate` | Normalized listing candidate DTO. |
| `Modules\Deals\Data\PriceCheckResult` | Typed summary of checked listings, drops and failed retailers. |
| `Modules\Deals\Exceptions\RetailerUnavailable` | Typed exception for unavailable retailer sources. |
| `Modules\Deals\Models\WatchedProduct` | Watched product Eloquent model. |
| `Modules\Deals\Models\ProductListing` | Retailer listing Eloquent model. |
| `Modules\Deals\Models\PricePoint` | Historical observed price point. |
| `Modules\Deals\Services\ProductMatcher` | Searches all configured retailers and isolates failures. |
| `Modules\Deals\Services\PriceChecker` | Fetches prices, writes history and detects drops. |
| `Modules\Deals\Services\Retailers\BolAdapter` | bol.com retailer adapter. |
| `Modules\Deals\Services\Retailers\AmazonAdapter` | Configurable Amazon best-effort adapter. |
| `Modules\Deals\Services\Retailers\TweakersAdapter` | Configurable Tweakers best-effort adapter. |
| `Modules\Deals\View\ViewModels\DealsViewModel` | Read-side watchlist state assembly. |

## Entertainment Module

Status: active. This module owns film, concert and music discovery at `/entertainment`.

| Class | Responsibility |
|---|---|
| `Modules\Entertainment\Providers\EntertainmentServiceProvider` | Registers Entertainment metadata, curator and concert providers. |
| `Modules\Entertainment\Http\Controllers\EntertainmentController` | Thin HTTP boundary for dashboard state, feedback, taste and refresh. |
| `Modules\Entertainment\Http\Resources\*` | Stable JSON transformers for films, concerts, releases and taste. |
| `Modules\Entertainment\Actions\RefreshFilms` | Fetches TMDB candidates, curates and stores film recommendations. |
| `Modules\Entertainment\Actions\RefreshConcerts` | Fetches concert providers, computes relevance and stores concerts. |
| `Modules\Entertainment\Actions\RefreshMusicReleases` | Stores recent followed-artist Spotify releases. |
| `Modules\Entertainment\Actions\NotifyEntertainment` | Sends bundled music and relevant concert notifications once. |
| `Modules\Entertainment\Actions\RecordFilmFeedback` | Persists thumbs feedback for film recommendations. |
| `Modules\Entertainment\Actions\UpdateTasteProfile` | Updates the single taste profile row. |
| `Modules\Entertainment\Actions\DismissFilm` | Hides a film recommendation. |
| `Modules\Entertainment\Contracts\ConcertProvider` | Concert source contract. |
| `Modules\Entertainment\Contracts\EntertainmentCurator` | Testable AI curation/relevance contract. |
| `Modules\Entertainment\Data\ConcertData` | Normalized concert DTO. |
| `Modules\Entertainment\Data\FilmPick` | AI film-pick DTO. |
| `Modules\Entertainment\Models\TasteProfile` | Single taste profile model. |
| `Modules\Entertainment\Models\FilmFeedback` | Stored thumbs feedback. |
| `Modules\Entertainment\Models\FilmRecommendation` | Stored film recommendation. |
| `Modules\Entertainment\Models\Concert` | Stored concert listing with relevance. |
| `Modules\Entertainment\Models\MusicRelease` | Stored followed-artist release. |
| `Modules\Entertainment\Services\Tmdb\TmdbClient` | TMDB transport for films and watch providers. |
| `Modules\Entertainment\Services\Concerts\*Provider` | Ticketmaster, Bandsintown and Hedon concert providers. |
| `Modules\Entertainment\Services\Music\SpotifyReleasesService` | Spotify followed-artist release reader. |
| `Modules\Entertainment\Services\PrismEntertainmentCurator` | Prism-backed film/concert curation with fallback behavior. |
| `Modules\Entertainment\View\ViewModels\EntertainmentViewModel` | Read-side entertainment state assembly. |

## Planner Module

Status: active. This module owns weekly agenda planning at `/planner`.

| Class | Responsibility |
|---|---|
| `Modules\Planner\Providers\PlannerServiceProvider` | Registers Planner metadata and composer binding. |
| `Modules\Planner\Http\Controllers\PlannerController` | Thin HTTP boundary for plans, Google OAuth, item acceptance and intentions. |
| `Modules\Planner\Http\Resources\*` | Stable JSON transformers for plans, items and intentions. |
| `Modules\Planner\Actions\GenerateWeeklyPlan` | Reads busy times, generates a validated plan and optionally sends ntfy. |
| `Modules\Planner\Actions\AcceptPlanItem` | Inserts one proposed item into Google Calendar and marks it accepted. |
| `Modules\Planner\Actions\AcceptAllPlanItems` | Accepts every proposed item in the latest plan. |
| `Modules\Planner\Actions\RejectPlanItem` | Marks a plan item rejected. |
| `Modules\Planner\Actions\Intentions\*` | Creates, updates and deletes planning intentions. |
| `Modules\Planner\Contracts\PlanComposer` | Testable AI summary/arrangement contract. |
| `Modules\Planner\Data\BusyTime` | Google/free-busy time block DTO. |
| `Modules\Planner\Data\PlanItemData` | Proposed or unplaceable plan item DTO. |
| `Modules\Planner\Data\ComposedPlan` | Composer result DTO. |
| `Modules\Planner\Models\GoogleCalendarToken` | Stored Google Calendar OAuth token. |
| `Modules\Planner\Models\PlannerIntention` | Flexible recurring intention model. |
| `Modules\Planner\Models\PlannerPlan` | Weekly plan model. |
| `Modules\Planner\Models\PlannerPlanItem` | Proposed/accepted/rejected/unplaceable plan block. |
| `Modules\Planner\Services\Google\GoogleCalendarTokenService` | Google OAuth URL, code exchange and refresh behavior. |
| `Modules\Planner\Services\Google\GoogleCalendarClient` | Google Calendar free-busy and event insert transport. |
| `Modules\Planner\Services\SlotFinder` | Deterministic feasible-slot finder. |
| `Modules\Planner\Services\WeeklyPlanner` | Intentions placement and composed plan validation. |
| `Modules\Planner\Services\PrismPlanComposer` | Default plan summary composer. |
| `Modules\Planner\View\ViewModels\PlannerViewModel` | Read-side planner state assembly. |

## Tasks Module

Status: active. This module owns the local in-app Kanban board at `/tasks`.

| Class | Responsibility |
|---|---|
| `Modules\Tasks\Providers\TasksServiceProvider` | Registers Tasks module metadata. |
| `Modules\Tasks\Http\Controllers\TasksController` | Thin HTTP boundary for board, column, task, archive and reorder JSON responses. |
| `Modules\Tasks\Http\Controllers\TaskRecurrenceController` | Thin HTTP boundary for habit status, habit completion and recurrence management JSON endpoints. |
| `Modules\Tasks\Actions\Boards\CreateBoard` | Creates a board with default columns. |
| `Modules\Tasks\Actions\Boards\DeleteBoard` | Deletes a board and returns the remaining default board. |
| `Modules\Tasks\Actions\Boards\EnsureDefaultBoard` | Creates or returns the default `Tasks` board with `Todo`, `Doing`, `Done` columns. |
| `Modules\Tasks\Actions\Columns\*` | Creates, updates, deletes and reorders columns. |
| `Modules\Tasks\Actions\Tasks\*` | Creates, updates, moves, archives, deletes and reorders tasks. |
| `Modules\Tasks\Actions\Recurrences\CreateRecurrence` | Creates habit or maintenance recurrence definitions. |
| `Modules\Tasks\Actions\Recurrences\UpdateRecurrence` | Updates habit or maintenance recurrence definitions. |
| `Modules\Tasks\Actions\Recurrences\DeleteRecurrence` | Deletes recurrence definitions. |
| `Modules\Tasks\Actions\Recurrences\CompleteHabit` | Idempotently records a habit completion for the relevant period. |
| `Modules\Tasks\Actions\Recurrences\UndoHabitCompletion` | Removes a habit completion for the relevant period. |
| `Modules\Tasks\Actions\Recurrences\MaterializeDueMaintenance` | Creates due maintenance Kanban cards and sends shared-hub ntfy reminders. |
| `Modules\Tasks\Actions\Recurrences\CompleteMaintenanceCard` | Records maintenance card completion and calculates the next due date. |
| `Modules\Tasks\Http\Requests\*` | Request validation for board, column, task and recurrence endpoints. |
| `Modules\Tasks\Http\Resources\TaskBoardStateResource` | Stable JSON state transformer for the Kanban board UI. |
| `Modules\Tasks\Http\Resources\HabitResource` | Stable JSON transformer for habit status, progress and streak data. |
| `Modules\Tasks\Http\Resources\RecurrenceResource` | Stable JSON transformer for habit and maintenance recurrence definitions. |
| `Modules\Tasks\View\ViewModels\TasksBoardViewModel` | Read-side board state assembly for Blade and JSON responses. |
| `Modules\Tasks\View\ViewModels\TaskRecurrencesViewModel` | Read-side habit and maintenance recurrence state assembly. |
| `Modules\Tasks\Services\StreakCalculator` | Cadence-aware current progress, current streak and best streak calculation. |
| `Modules\Tasks\Briefing\TasksBriefingSource` | Open task and habit-status contribution for the daily briefing. |
| `Modules\Tasks\Models\TaskBoard` | Board Eloquent model. |
| `Modules\Tasks\Models\TaskColumn` | Column Eloquent model and `Done` column detection. |
| `Modules\Tasks\Models\KanbanTask` | Task card model with labels, checklist, due date, completed, archived and optional recurrence link state. |
| `Modules\Tasks\Models\TaskLabel` | Per-board label model. |
| `Modules\Tasks\Models\TaskChecklistItem` | Checklist item model. |
| `Modules\Tasks\Models\TaskRecurrence` | Habit and maintenance recurrence Eloquent model. |
| `Modules\Tasks\Models\TaskRecurrenceCompletion` | Completion Eloquent model for recurrence periods and occurrences. |
| `Modules\Tasks\Models\Builders\TaskRecurrenceBuilder` | Reusable habit, maintenance, active and due recurrence query filters. |
| `Modules/Tasks/resources/views/index.blade.php` | Kanban app mount point with initial state and route templates. |
| `Modules/Tasks/resources/assets/js/tasks-board.js` | Kanban app entry point. |
| `Modules/Tasks/resources/assets/css/tasks.css` | Dark compact Kanban visual system based on the Claude design handoff. |
