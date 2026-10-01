<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Inertia\ServiceProvider;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;
use Wobqqq\Aegis\AegisServiceProvider;
use Wobqqq\Aegis\Nova\AegisTool;
use Wobqqq\Aegis\Tests\Fixtures\User;

abstract class TestCase extends BaseTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Admin');
            $table->string('email')->unique();
            $table->string('password')->default('');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Nova::$tools = [];
        Nova::tools([new AegisTool()]);

        Gate::define(AegisTool::GATE, static fn (User $user): bool => $user->is_admin);
    }

    /**
     * @return list<class-string>
     */
    #[Override]
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class, NovaCoreServiceProvider::class, AegisServiceProvider::class];
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('aegis.users.model', User::class);
        $app['config']->set('aegis.audit.schedule', false);
    }

    #[Override]
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
