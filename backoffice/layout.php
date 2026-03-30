<?php

/**
 * Shared layout helpers for the backoffice
 */

function bo_base_path(): string
{
    // Use an absolute URL path rooted at DOCUMENT_ROOT so that
    // mod_rewrite URL rewrites (e.g. contenu-liste → contenu/liste.php)
    // don't break relative asset/nav links in the browser.
    $docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $backendDir = rtrim(str_replace('\\', '/', __DIR__), '/');

    if ($docRoot !== '' && str_starts_with($backendDir, $docRoot)) {
        // Returns e.g. /TP-Iran/backend/
        return substr($backendDir, strlen($docRoot)) . '/';
    }

    // Fallback: relative path based on physical script depth
    $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $relative   = trim(substr($scriptFile, strlen($backendDir)), '/');
    $depth      = substr_count($relative, '/');
    return str_repeat('../', $depth);
}

function bo_head(string $title, string $desc = ''): void
{
    $safeTitle = htmlspecialchars($title);
    $safeDesc  = htmlspecialchars($desc ?: 'IranWatch backoffice – ' . $title);
    $base      = bo_base_path();
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeTitle} – IranWatch Admin</title>
    <meta name="description" content="{$safeDesc}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{$base}assets/admin.css">
</head>
<body>
HTML;
}

function bo_nav(string $active = ''): void
{
    $base  = bo_base_path();
    $links = [
        'dashboard' => [$base . 'dashboard.php',       '🏠 Dashboard'],
        'articles'  => [$base . 'contenu-liste',   '📰 Articles'],
        'tags'      => [$base . 'tag-liste',        '🏷️ Tags'],
        'images'    => [$base . 'image/ajouter.php',    '🖼️ Images'],
    ];
    echo '<header class="bo-header" role="banner">';
    echo '<div class="bo-logo"><span class="bo-logo-icon">⚡</span><span>IranWatch</span><sup>Admin</sup></div>';
    echo '<nav class="bo-nav" aria-label="Admin navigation"><ul>';
    foreach ($links as $key => [$href, $label]) {
        $cls = ($key === $active) ? ' class="active" aria-current="page"' : '';
        echo "<li><a href=\"{$href}\"{$cls}>{$label}</a></li>";
    }
    echo '</ul></nav></header>';
}

function bo_foot(): void
{
    echo <<<HTML
<footer class="bo-footer" role="contentinfo">
    <p>&copy; <?= date('Y') ?> IranWatch Admin. For internal use only.</p>
</footer>
<script>
/* Flash message auto-dismiss */
document.querySelectorAll('.flash').forEach(function(el){
    setTimeout(function(){ el.style.opacity='0'; el.style.transform='translateY(-8px)';
        setTimeout(function(){ el.remove(); }, 300); }, 3500);
});
</script>
</body></html>
HTML;
}

function bo_flash(?string $msg, string $type = 'success'): void
{
    if (!$msg) return;
    $safe = htmlspecialchars($msg);
    $icon = $type === 'error' ? '✖' : '✔';
    echo "<div class=\"flash flash--{$type}\" role=\"alert\" aria-live=\"polite\">{$icon} {$safe}</div>";
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^\w\s-]/u', '', $text);
    $text = preg_replace('/[\s_-]+/', '-', $text);
    return trim($text, '-');
}
