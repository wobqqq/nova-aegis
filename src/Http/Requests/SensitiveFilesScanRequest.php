<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SensitiveFilesScanRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['url' => ['required', 'string', 'max:255', 'url:http,https']];
    }

    public function target(): string
    {
        return trim($this->string('url')->toString());
    }
}
