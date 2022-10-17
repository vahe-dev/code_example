<!DOCTYPE html>
<html lang="en">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link href="{{ asset("css/ui/style.css?v=") . time() }}" rel="stylesheet">
    </head>

    <body>
        <script src="{{ asset("js/clientCaptcha.min.js") }}"></script>
    </body>
</html>