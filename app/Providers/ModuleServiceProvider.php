<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * ModuleServiceProvider — registers all AKRU domain modules.
 *
 * Each module can define its own routes, migrations, seeders,
 * and service bindings. This provider orchestrates discovery.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * All registered AKRU modules.
     *
     * Order matters — follow dependency chain from Blueprint §5.5:
     * Core → Identity → MasterData → Accounting → Sales → Purchase →
     * Inventory → Finance → Tax → Document → Workflow → Audit →
     * Notification → Reporting → Subscription → Partner
     */
    protected array $modules = [
        'Core',
        'Identity',
        'MasterData',
        'Accounting',
        'Sales',
        'Purchase',
        'Inventory',
        'Finance',
        'Tax',
        'Document',
        'Workflow',
        'Audit',
        'Notification',
        'Reporting',
        'Subscription',
        'Partner',
        'Platform',
    ];

    public function register(): void
    {
        // Register shared engines
        $this->app->singleton(
            \App\Services\Posting\PostingService::class,
        );
        $this->app->singleton(
            \App\Services\TaxEngine\TaxCalculator::class,
        );
        $this->app->singleton(
            \App\Services\AuditEngine\AuditLogger::class,
        );
        $this->app->singleton(
            \App\Services\ApprovalEngine\ApprovalEngine::class,
        );
        $this->app->singleton(
            \App\Services\SequenceEngine\SequenceGenerator::class,
        );
        $this->app->singleton(
            \App\Services\SyncEngine\SyncManager::class,
        );
    }

    public function boot(): void
    {
        foreach ($this->modules as $module) {
            $this->bootModule($module);
        }
    }

    protected function bootModule(string $module): void
    {
        $basePath = app_path("Modules/{$module}");

        // Register routes
        $routesPath = "{$basePath}/routes/web.php";
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        $apiRoutesPath = "{$basePath}/routes/api.php";
        if (file_exists($apiRoutesPath)) {
            $this->loadRoutesFrom($apiRoutesPath);
        }

        // Register migrations
        $migrationsPath = "{$basePath}/database/migrations";
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Register views (if module has its own views)
        $viewsPath = "{$basePath}/resources/views";
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, strtolower($module));
        }
    }
}
