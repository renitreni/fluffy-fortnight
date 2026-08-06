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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f3f4f6;
            color: #374151;
            padding: 16px;
        }
        .card-wrapper {
            position: relative;
            display: inline-block;
            width: min(92vw, 520px);
        }
        .card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.18);
        }
        .card--image-only img {
            display: block;
            width: 100%;
            height: auto;
        }
        .click-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
        }
        .fallback {
            text-align: center;
            background: #fff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
            max-width: 420px;
        }
        .fallback p {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 12px;
        }
        .fallback a {
            font-size: 14px;
            font-weight: 600;
            color: #2563eb;
            text-decoration: none;
        }
        .fallback a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    @if ($ogImageUrl)
        <div class="card-wrapper">
            <a href="{{ $targetUrl }}" class="click-overlay" aria-label="Visit link"></a>
            <div class="card card--image-only">
                <img src="{{ $ogImageUrl }}" alt="Preview image">
            </div>
        </div>
    @else
        <div class="fallback">
            <p>Preview image not available.</p>
            <a href="{{ $targetUrl }}">Open link</a>
        </div>
    @endif
</body>
</html>
