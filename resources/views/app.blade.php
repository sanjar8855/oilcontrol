<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <meta name="description" content="OilControl — moy almashtirish moyxonalari uchun 300 000 so'mdan to'liq boshqaruv tizimi: ombor, FIFO, mijozlar, Telegram eslatma va hisobotlar." />
        <link rel="canonical" href="{{ url()->current() }}" />

        <meta property="og:site_name" content="OilControl" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="OilControl - Moy Almashtirish Tizimi" />
        <meta property="og:description" content="Moyxonangiz uchun 300 000 so'mdan to'liq boshqaruv tizimi: ombor, FIFO, mijozlar, Telegram eslatma va hisobotlar." />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:locale" content="uz_UZ" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="OilControl - Moy Almashtirish Tizimi" />
        <meta name="twitter:description" content="Moyxonangiz uchun 300 000 so'mdan to'liq boshqaruv tizimi: ombor, FIFO, mijozlar, Telegram eslatma va hisobotlar." />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
