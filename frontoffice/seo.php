<?php

declare(strict_types=1);

function fo_context(): array
{
    $rawBasePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = ($rawBasePath === '/' || $rawBasePath === '.') ? '' : rtrim($rawBasePath, '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $scheme . '://' . $host . $basePath;
    $homeHref = ($basePath === '') ? '/' : $basePath . '/';

    return [
        'basePath' => $basePath,
        'scheme' => $scheme,
        'host' => $host,
        'baseUrl' => $baseUrl,
        'homeHref' => $homeHref,
    ];
}

function fo_slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text) ?? '';
    $text = preg_replace('/[\s_-]+/u', '-', $text) ?? '';
    return trim($text, '-');
}

function fo_meta_trim(string $text, int $max): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $max - 1)) . '…';
}

function fo_asset_url(string $basePath, string $path): string
{
    $clean = ltrim($path, '/');
    return (($basePath === '') ? '' : $basePath) . '/' . $clean;
}

function fo_render_analytics(): void
{
    $gaId = getenv('GA_MEASUREMENT_ID') ?: '';
    if ($gaId !== '') {
        $safeGa = htmlspecialchars($gaId, ENT_QUOTES, 'UTF-8');
        echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $safeGa . '"></script>';
        echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","' . $safeGa . '");</script>';
    }

    $gscToken = getenv('GSC_VERIFICATION_TOKEN') ?: '';
    if ($gscToken !== '') {
        echo '<meta name="google-site-verification" content="' . htmlspecialchars($gscToken, ENT_QUOTES, 'UTF-8') . '">';
    }
}
