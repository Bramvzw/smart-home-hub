# Backend Class Catalogue

Inventory of app and module classes that define the architecture. Update this when adding or removing first-class PHP classes.

---

## Shared App

| Class | Responsibility |
|---|---|
| `App\Contracts\ModuleContract` | Contract implemented by module service providers for dashboard registration. |
| `App\Services\ModuleRegistry` | Collects module metadata and navigation entries. |
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

## Tasks Module

Status: active. This module owns the local in-app Kanban board at `/tasks`.

| Class | Responsibility |
|---|---|
| `Modules\Tasks\Providers\TasksServiceProvider` | Registers Tasks module metadata. |
| `Modules\Tasks\Http\Controllers\TasksController` | Thin HTTP boundary for board, column, task, archive and reorder JSON responses. |
| `Modules\Tasks\Actions\Boards\CreateBoard` | Creates a board with default columns. |
| `Modules\Tasks\Actions\Boards\DeleteBoard` | Deletes a board and returns the remaining default board. |
| `Modules\Tasks\Actions\Boards\EnsureDefaultBoard` | Creates or returns the default `Tasks` board with `Todo`, `Doing`, `Done` columns. |
| `Modules\Tasks\Actions\Columns\*` | Creates, updates, deletes and reorders columns. |
| `Modules\Tasks\Actions\Tasks\*` | Creates, updates, moves, archives, deletes and reorders tasks. |
| `Modules\Tasks\Http\Requests\*` | Request validation for board, column and task endpoints. |
| `Modules\Tasks\Http\Resources\TaskBoardStateResource` | Stable JSON state transformer for the Kanban board UI. |
| `Modules\Tasks\View\ViewModels\TasksBoardViewModel` | Read-side board state assembly for Blade and JSON responses. |
| `Modules\Tasks\Models\TaskBoard` | Board Eloquent model. |
| `Modules\Tasks\Models\TaskColumn` | Column Eloquent model and `Done` column detection. |
| `Modules\Tasks\Models\KanbanTask` | Task card model with labels, checklist, due date, completed and archived state. |
| `Modules\Tasks\Models\TaskLabel` | Per-board label model. |
| `Modules\Tasks\Models\TaskChecklistItem` | Checklist item model. |
| `Modules/Tasks/resources/views/index.blade.php` | Kanban app mount point with initial state and route templates. |
| `Modules/Tasks/resources/assets/js/tasks-board.js` | Kanban app entry point. |
| `Modules/Tasks/resources/assets/css/tasks.css` | Dark compact Kanban visual system based on the Claude design handoff. |
