<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Card;
use Laravel\Nova\Nova;
use Override;

final class AegisCard extends Card
{
    public $width = '1/2';

    public $height = 'dynamic';

    public function __construct(?string $component = null)
    {
        parent::__construct($component);

        $this->canSee(static fn (Request $request): bool => Gate::has(AegisTool::GATE) && Gate::forUser($request->user())->allows(AegisTool::GATE));
    }

    #[Override]
    public function component(): string
    {
        return 'aegis-card';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), ['toolPath' => Nova::url('/aegis')]);
    }
}
