<?php

declare(strict_types=1);

require_once __DIR__ . '/seo.php';

$ctx = fo_context();
$baseUrl = $ctx['baseUrl'];

header('Content-Type: text/plain; charset=UTF-8');
?>User-agent: *
Allow: /

Disallow: /admin/
Disallow: /backend/
Disallow: /backoffice/

Sitemap: <?= $baseUrl ?>/sitemap.xml