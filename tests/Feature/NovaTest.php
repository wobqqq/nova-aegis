<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Laravel\Nova\Nova;
use Wobqqq\Aegis\Nova\AegisCard;
use Wobqqq\Aegis\Nova\AegisTool;

it('shows the tool and the card to the administrators the gate lets in', function (): void {
    $adminUser = admin();
    $editorUser = editor();
    $admin = Request::create('/nova');
    $admin->setUserResolver(static fn (): Wobqqq\Aegis\Tests\Fixtures\User => $adminUser);
    $editor = Request::create('/nova');
    $editor->setUserResolver(static fn (): Wobqqq\Aegis\Tests\Fixtures\User => $editorUser);

    expect((new AegisTool())->authorize($admin))->toBeTrue()
        ->and((new AegisTool())->authorize($editor))->toBeFalse()
        ->and(AegisCard::make()->authorize($admin))->toBeTrue()
        ->and(AegisCard::make()->authorize($editor))->toBeFalse();
});

it('adds an Aegis entry to the Nova menu and its assets', function (): void {
    $tool = new AegisTool();
    $tool->boot();

    expect($tool->menu(Request::create('/nova'))->jsonSerialize())->toMatchArray(['name' => 'Aegis', 'path' => '/nova/aegis'])
        ->and(collect(Nova::allScripts())->map->name()->all())->toContain('aegis');
});

it('hands the card its component and the tool path', function (): void {
    expect(AegisCard::make()->jsonSerialize())->toMatchArray(['component' => 'aegis-card', 'width' => '1/2', 'toolPath' => '/nova/aegis']);
});
