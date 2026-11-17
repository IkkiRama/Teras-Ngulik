<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="Teras Ngulik — belajar coding, data, keamanan digital, dan riset lewat praktik interaktif.">
  <meta name="theme-color" content="#2F55D4">
  <link rel="icon" type="image/png" href="/assets/logo.png">
  @viteReactRefresh
  @vite('resources/js/app.tsx')
  @inertiaHead
</head>
<body>@inertia</body>
</html>
