<?php

namespace App\Providers;

use App\Contracts\ModuleContract;
use App\Services\ModuleRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

abstract class ModuleServiceProvider extends ServiceProvider implements ModuleContract
{
    protected string $name = '';
    protected string $nameLower = '';

    protected function modulePath(string $path = ''): string
    {
        return base_path("Modules/{$this->name}" . ($path ? "/{$path}" : ''));
    }

    public function register(): void
    {
        $configPath = $this->modulePath('Config/config.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->nameLower);
        } else {
            $configPath = $this->modulePath('config/config.php');
            if (file_exists($configPath)) {
                $this->mergeConfigFrom($configPath, $this->nameLower);
            }
        }
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerLivewireComponents();
        $this->registerWithDashboard();
    }

    protected function registerRoutes(): void
    {
        $webRoutes = $this->modulePath('routes/web.php');
        if (file_exists($webRoutes)) {
            Route::middleware('web')->group($webRoutes);
        }
    }

    protected function registerViews(): void
    {
        $sourcePath = $this->modulePath('Resources/views');
        if (!is_dir($sourcePath)) {
            $sourcePath = $this->modulePath('resources/views');
        }

        if (is_dir($sourcePath)) {
            $this->loadViewsFrom($sourcePath, $this->nameLower);
        }

        Blade::componentNamespace("Modules\\{$this->name}\\View\\Components", $this->nameLower);
    }

    protected function registerMigrations(): void
    {
        $migrationsPath = $this->modulePath('Database/Migrations');
        if (!is_dir($migrationsPath)) {
            $migrationsPath = $this->modulePath('database/migrations');
        }

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $livewireDir = $this->modulePath('Livewire');

        if (!is_dir($livewireDir)) {
            return;
        }

        $namespace = "Modules\\{$this->name}\\Livewire";

        foreach (glob($livewireDir . '/*.php') as $file) {
            $className = basename($file, '.php');
            $componentName = Str::kebab($className);
            $fullClass = "{$namespace}\\{$className}";

            if (class_exists($fullClass)) {
                Livewire::component("{$this->nameLower}::{$componentName}", $fullClass);
            }
        }
    }

    protected function registerWithDashboard(): void
    {
        $this->app->booted(function () {
            $registry = $this->app->make(ModuleRegistry::class);
            $registry->register($this);
        });
    }
}
