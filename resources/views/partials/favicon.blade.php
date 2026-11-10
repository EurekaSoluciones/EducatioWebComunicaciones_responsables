@php
    $client = config('eureka.client_id');
    $favicon = 'assets/eure/favicon.svg';
    $faviconType = 'image/svg+xml';

    if (is_string($client) && preg_match('/\A[a-z0-9_-]+\z/', $client)) {
        $clientFavicon = "assets/C/{$client}/institucional/favicon.png";
        if (is_file(public_path($clientFavicon))) {
            $favicon = $clientFavicon;
            $faviconType = 'image/png';
        }
    }

    $faviconVersion = filemtime(public_path($favicon));
@endphp
<link rel="icon" type="{{ $faviconType }}" href="{{ asset($favicon) }}?v={{ $faviconVersion }}">
