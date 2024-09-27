<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <link rel="shortcut icon" href="caensup_logo.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="/css/output.css">

    <!-- Web App Settings -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="caensup_logo.jpeg">

    @yield('head')
    @yield("script")
    <style>
        @yield("style")
        .fc-timegrid-event {
            border: none;
        }

        .fc-timegrid-event .fc-event-main {
            padding: 2px 4px;
        }

        .fc-timegrid-event .fc-event-title {
            font-weight: bold;
            color: white;
        }

        .fc-timegrid-event .fc-event-description,
        .fc-timegrid-event .fc-event-room {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
<main>
    @yield('content')
</main>
@yield('script_end')
</body>
</html>

