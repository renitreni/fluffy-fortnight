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

    <!-- Auto-redirect for regular users -->
    <meta http-equiv="refresh" content="0;url={{ $targetUrl }}">
    <script>
        window.location.href = '{{ $targetUrl }}';
    </script>

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
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
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
        <div class="spinner"></div>
        <p>Redirecting you to <a href="{{ $targetUrl }}">{{ $targetUrl }}</a>...</p>
    </div>
</body>
</html>
