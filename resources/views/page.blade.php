<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name = "viewport" content="width=device-width,initial-scale=1.0 user-scalable = no , maximum-scale=1.0">
        <title>{{ $page->title }}</title>
    </head>
    <body>
        <div style="width: 100%">
            {!! $page->text !!}
        </div>
    </body>
</html>
