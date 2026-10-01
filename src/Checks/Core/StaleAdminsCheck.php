<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Support\Lang;

final readonly class StaleAdminsCheck implements Check
{
    public function __construct(private Config $config)
    {
    }

    #[Override]
    public function run(): CheckResult
    {
        $label = Lang::get('aegis::aegis.checks.stale_admins.label');
        $model = $this->config->get('aegis.users.model');
        $column = $this->config->get('aegis.users.last_login_column');
        $days = $this->config->get('aegis.users.stale_after_days');
        $days = is_numeric($days) && (int)$days > 0 ? (int)$days : 90;

        if (!is_string($model) || !is_a($model, Model::class, true) || !is_string($column) || preg_match('/^\w+$/', $column) !== 1) {
            return CheckResult::info('stale_admins', $label, Lang::get('aegis::aegis.checks.stale_admins.unconfigured'));
        }

        $stale = $model::query()
            ->where(static fn (Builder $query) => $query->whereNull($column)->orWhere($column, '<', Date::now()->subDays($days)))
            ->count();

        return $stale === 0
            ? CheckResult::pass('stale_admins', $label, Lang::get('aegis::aegis.checks.stale_admins.pass', ['days' => $days]))
            : CheckResult::warn('stale_admins', $label, Lang::get('aegis::aegis.checks.stale_admins.warn', ['count' => $stale, 'days' => $days]));
    }
}
