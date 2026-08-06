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
        }
        .card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            max-width: 520px;
            width: 90%;
        }
        .card-image {
            width: 100%;
            aspect-ratio: 1200 / 630;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-image-placeholder {
            color: #9ca3af;
            font-size: 14px;
        }
        .card-footer {
            padding: 8px 12px;
            border-top: 1px solid #f3f4f6;
        }
        .card-footer-title {
            font-size: 13px;
            font-weight: 500;
            color: #111827;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-footer-url {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.3;
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-footer-url a {
            color: #6b7280;
            text-decoration: none;
        }
        .card-footer-url a:hover {
            text-decoration: underline;
        }
        .click-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
        }
        .card-wrapper {
            position: relative;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="card-wrapper">
        <a href="{{ $targetUrl }}" class="click-overlay" aria-label="Visit link"></a>
        <div class="card">
            @if ($ogImageUrl)
            <div class="card-image">
                <img src="{{ $ogImageUrl }}" alt="{{ $title }}">
            </div>
            @else
            <div class="card-image">
                <span class="card-image-placeholder">No preview image</span>
            </div>
            @endif
            <div class="card-footer">
                @if ($title)
                <div class="card-footer-title">{{ $title }}</div>
                @endif
                <div class="card-footer-url">{{ parse_url($shortUrl, PHP_URL_HOST) }}</div>
            </div>
        </div>
    </div>
</body>
</html>
