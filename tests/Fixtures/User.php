<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $email
 * @property bool $is_admin
 * @property \Illuminate\Support\Carbon|null $last_login_at
 */
final class User extends Authenticatable
{
    /** @var array<string> */
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_admin' => 'boolean', 'last_login_at' => 'datetime'];
    }
}
