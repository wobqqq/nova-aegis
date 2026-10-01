<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks;

use Psr\Log\LoggerInterface;
use Throwable;
use Wobqqq\Aegis\Modules\ModuleRegistry;
use Wobqqq\Aegis\Settings\SettingsRepository;

final readonly class CheckRunner
{
    public function __construct(
        private CheckRegistry $checks,
        private ModuleRegistry $modules,
        private SettingsRepository $settings,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<CheckResult>
     */
    public function checks(): array
    {
        $results = [];

        foreach ($this->checks->all() as $check) {
            try {
                $results[] = $check->run();
            } catch (Throwable $e) {
                $this->logger->error('An Aegis check failed.', ['check' => $check::class, 'exception' => $e::class]);
                $results[] = CheckResult::fail(class_basename($check), class_basename($check), (string)__('aegis::aegis.checks.failed'));
            }
        }

        return $results;
    }

    /**
     * @return list<CheckResult>
     */
    public function modules(): array
    {
        $results = [];

        foreach ($this->modules->all() as $key => $module) {
            $status = $module->status($this->settings->section($key));

            if ($status instanceof CheckResult) {
                $results[] = $status;
            }
        }

        return $results;
    }
}
