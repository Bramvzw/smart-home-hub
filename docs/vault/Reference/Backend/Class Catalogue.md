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
