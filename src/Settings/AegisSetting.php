<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Settings;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $section
 * @property array<string, mixed> $values
 */
final class AegisSetting extends Model
{
    protected $table = 'aegis_settings';

    /** @var list<string> */
    protected $fillable = ['section', 'values'];

    protected function casts(): array
    {
        return ['values' => 'array'];
    }
}
