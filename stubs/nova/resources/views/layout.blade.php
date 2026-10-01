<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ \Laravel\Nova\Nova::name() }}</title>
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
