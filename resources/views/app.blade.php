<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        
        <link rel="icon" href="/favicon.png" type="image/png">
        <link rel="icon" href="/favicon-32x32.png" sizes="32x32" type="image/png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Optimized Non-Blocking Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" media="print" onload="this.media='all'" />

        <noscript>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" />
        </noscript>

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased min-w-[320px] overflow-x-hidden" style="background-color: #111635; background-image: url('/StarfieldCanvas.png'); background-size: cover; background-position: center; background-attachment: fixed;">
        @inertia
    </body>
</html>
