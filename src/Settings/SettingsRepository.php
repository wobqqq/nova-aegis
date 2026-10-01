<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Settings;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\QueryException;
use Throwable;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\Aegis\Modules\ModuleRegistry;

final class SettingsRepository
{
    /**
     * Part of the cache key: a release that changes what is cached bumps it.
     */
    private const int CACHE_VERSION = 1;

    private const int TTL = 3600;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $stored = null;

    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly Cache $cache,
        private readonly ValidationFactory $validator,
        private readonly Dispatcher $events,
    ) {
    }

    /**
     * The section's values: what was saved over its module's defaults.
     *
     * @return array<string, mixed>
     */
    public function section(string $key): array
    {
        $defaults = $this->modules->get($key)?->defaults() ?? [];

        return array_replace($defaults, $this->stored()[$key] ?? []);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed> the values as saved
     */
    public function save(string $key, array $values): array
    {
        $module = $this->modules->getOrFail($key);

        /** @var array<string, mixed> $validated */
        $validated = $this->validator->make($values, $module->rules())->validate();
        $validated = array_intersect_key(array_replace($module->defaults(), $validated), $module->defaults());

        AegisSetting::query()->updateOrCreate(['section' => $key], ['values' => $validated]);

        $this->flush();
        $this->events->dispatch(new SettingsSaved($key, $validated));

        return $validated;
    }

    public function flush(): void
    {
        $this->stored = null;
        $this->cache->forget($this->cacheKey());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function stored(): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }

        try {
            $cached = $this->cache->remember($this->cacheKey(), self::TTL, $this->load(...));
        } catch (Throwable) {
            $cached = null;
        }

        return $this->stored = is_array($cached) ? $this->normalize($cached) : $this->load();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function load(): array
    {
        try {
            /** @var array<string, array<string, mixed>> $rows */
            $rows = AegisSetting::query()->pluck('values', 'section')->all();
        } catch (QueryException) {
            return [];
        }

        return $this->normalize($rows);
    }

    /**
     * @param array<mixed> $rows
     *
     * @return array<string, array<string, mixed>>
     */
    private function normalize(array $rows): array
    {
        $sections = [];

        foreach ($rows as $section => $values) {
            if (is_string($section) && is_array($values)) {
                /** @var array<string, mixed> $values */
                $sections[$section] = $values;
            }
        }

        return $sections;
    }

    private function cacheKey(): string
    {
        return sprintf('aegis.settings.v%d', self::CACHE_VERSION);
    }
}
