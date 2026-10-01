<?php

declare(strict_types=1);

use Wobqqq\Aegis\Tests\Fixtures\User;
use Wobqqq\Aegis\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

function admin(): User
{
    return User::query()->create(['email' => 'admin@example.com', 'is_admin' => true, 'last_login_at' => now()]);
}

function editor(): User
{
    return User::query()->create(['email' => 'editor@example.com', 'is_admin' => false, 'last_login_at' => now()]);
}
