<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $email
 * @property bool $is_admin
 * @property Carbon|null $last_login_at
 */
final class User extends Authenticatable
{
    /** @var array<string> */
    protected $guarded = [];

    #[Override]
    protected function casts(): array
    {
        return ['is_admin' => 'boolean', 'last_login_at' => 'datetime'];
    }
}
