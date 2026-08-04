<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>

    <!-- OpenGraph Meta Tags -->
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $shortUrl }}">
    <meta property="og:type" content="website">
    @if ($ogImageUrl)
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @if (!empty($ogImageType))
    <meta property="og:image:type" content="{{ $ogImageType }}">
    @endif
    @if (str_starts_with($ogImageUrl, 'https://'))
    <meta property="og:image:secure_url" content="{{ $ogImageUrl }}">
    @endif
    <meta property="og:image:alt" content="{{ $title }}">
    @endif

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="{{ $ogImageUrl ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if ($ogImageUrl)
    <meta name="twitter:image" content="{{ $ogImageUrl }}">
    @endif

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f9fafb;
            color: #374151;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        a {
            color: #6366f1;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <p><a href="{{ $targetUrl }}">Click here to visit the link</a></p>
    </div>
</body>
</html>
