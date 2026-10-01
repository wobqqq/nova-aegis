<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Modules;

use InvalidArgumentException;
use Wobqqq\Aegis\Contracts\Module;

final class ModuleRegistry
{
    /** @var array<string, Module> */
    private array $modules = [];

    public function register(Module $module): void
    {
        $key = $module->key();

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid Aegis module key.', $key));
        }

        $this->modules[$key] = $module;
    }

    /**
     * @return array<string, Module>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function get(string $key): ?Module
    {
        return $this->modules[$key] ?? null;
    }

    public function getOrFail(string $key): Module
    {
        return $this->get($key) ?? throw new InvalidArgumentException(sprintf('There is no Aegis module "%s".', $key));
    }
}
