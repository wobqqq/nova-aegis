<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TlsCertificatesScanRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['host' => ['required', 'string', 'max:255']];
    }

    public function target(): string
    {
        return trim($this->string('host')->toString());
    }
}
