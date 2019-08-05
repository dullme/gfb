<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
        <title>{{ $page->title }}</title>
    </head>
    <body style="margin: 0; padding: 0;">
        <div style="width: 100%">
            {!! $page->text !!}
        </div>
    </body>
</html>
