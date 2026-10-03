<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The section's own rules validate the values when they are saved; here only their shape is read.
 */
final class UpdateSettingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        $values = $this->input('values');

        return is_array($values) ? array_filter($values, is_string(...), ARRAY_FILTER_USE_KEY) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
