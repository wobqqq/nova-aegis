<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Modules\ModuleRegistry;
use Wobqqq\Aegis\Settings\SettingsRepository;

final readonly class SettingsController
{
    public function __construct(private ModuleRegistry $modules, private SettingsRepository $settings)
    {
    }

    public function index(): JsonResponse
    {
        $sections = [];

        foreach ($this->modules->all() as $key => $module) {
            $sections[] = [
                'key' => $key,
                'label' => $module->label(),
                'description' => $module->description(),
                'fields' => $module->fields(),
                'values' => $this->settings->section($key),
            ];
        }

        return new JsonResponse(['sections' => $sections]);
    }

    public function update(Request $request, string $section): JsonResponse
    {
        abort_if(!$this->modules->get($section) instanceof Module, 404);

        $values = $request->input('values');
        $values = is_array($values) ? array_filter($values, is_string(...), ARRAY_FILTER_USE_KEY) : [];

        return new JsonResponse(['values' => $this->settings->save($section, $values)]);
    }
}
