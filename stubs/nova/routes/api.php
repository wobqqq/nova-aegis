<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::any('/{path}', static fn () => abort(404))->where('path', '.*')->name('resource');
