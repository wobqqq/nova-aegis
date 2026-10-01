<?php

declare(strict_types=1);

namespace Laravel\Nova;

use Illuminate\Support\ServiceProvider;

class NovaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/nova.php' => config_path('nova.php')], 'nova-config');
        }
    }
}
