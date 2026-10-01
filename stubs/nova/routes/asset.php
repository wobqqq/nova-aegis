<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Asset;
use Laravel\Nova\Nova;

$serve = static function (array $assets, string $name): Asset {
    $asset = collect($assets)->first(static fn (Asset $asset): bool => (string)$asset->name() === $name);

    abort_if($asset === null, 404);

    return $asset;
};

Route::get('/scripts/{script}', static fn (Request $request, string $script): Asset => $serve(Nova::allScripts(), $script))->name('scripts');
Route::get('/styles/{style}', static fn (Request $request, string $style): Asset => $serve(Nova::allStyles(), $style))->name('styles');
