<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Season</title>
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect x='13' y='2' width='6' height='20' rx='3' fill='%23e2e8f0' stroke='%2394a3b8' stroke-width='1.2'/><circle cx='16' cy='25' r='5' fill='%23ef4444' stroke='%2394a3b8' stroke-width='1.2'/><rect x='14.5' y='12' width='3' height='14' rx='1.5' fill='%23ef4444'/><line x1='20' y1='8' x2='22' y2='8' stroke='%2394a3b8' stroke-width='1.2' stroke-linecap='round'/><line x1='20' y1='11' x2='22' y2='11' stroke='%2394a3b8' stroke-width='1.2' stroke-linecap='round'/><line x1='20' y1='14' x2='22' y2='14' stroke='%2394a3b8' stroke-width='1.2' stroke-linecap='round'/><line x1='20' y1='17' x2='21' y2='17' stroke='%2394a3b8' stroke-width='1.2' stroke-linecap='round'/><line x1='20' y1='5' x2='21' y2='5' stroke='%2394a3b8' stroke-width='1.2' stroke-linecap='round'/></svg>">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    {{ $slot }}
</body>
</html>
