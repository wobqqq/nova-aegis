<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Http\Requests\UpdateSettingsRequest;
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

    public function update(UpdateSettingsRequest $request, string $section): JsonResponse
    {
        abort_if(!$this->modules->get($section) instanceof Module, 404);

        return new JsonResponse(['values' => $this->settings->save($section, $request->values())]);
    }
}
