<?php

declare(strict_types=1);

namespace App\Services;

final class HtmlSanitizer
{
    public function sanitize(string $html): string
    {
        $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $allowed = '<p><br><strong><em><ul><ol><li><blockquote><h2><h3>';
        $html = strip_tags($html, $allowed);

        return (string) preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html);
    }
}
