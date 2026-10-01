<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;
use Override;
use Wobqqq\Aegis\Support\Lang;

final class AegisTool extends Tool
{
    public const string GATE = 'viewAegis';

    public function __construct()
    {
        parent::__construct();

        $this->canSee(static fn (Request $request): bool => Gate::has(self::GATE) && Gate::forUser($request->user())->allows(self::GATE));
    }

    #[Override]
    public function boot(): void
    {
        Nova::script('aegis', __DIR__ . '/../../dist/js/tool.js');
        Nova::style('aegis', __DIR__ . '/../../dist/css/tool.css');
    }

    #[Override]
    public function menu(Request $request): MenuSection
    {
        return MenuSection::make(Lang::get('aegis::aegis.menu'))->path('/aegis')->icon('shield-check');
    }
}
