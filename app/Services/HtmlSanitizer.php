<?php

declare(strict_types=1);

namespace App\Services;

final class HtmlSanitizer
{
    public function sanitize(string $html): string
    {
        $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $allowed = '<p><div><br><strong><b><em><i><u><s><strike><a><ul><ol><li><blockquote><h2><h3>';
        $html = strip_tags($html, $allowed);

        return (string) preg_replace_callback('/<([a-z0-9]+)\b([^>]*)>/i', function (array $match): string {
            $alignment = '';
            $link = '';
            if (strtolower($match[1]) === 'a' && preg_match('/\bhref\s*=\s*([\'"])(.*?)\1/is', $match[2], $href)) {
                $url = html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                    $link = ' href="'.htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" rel="noopener noreferrer"';
                }
            }
            if (preg_match('/\bstyle\s*=\s*([\'"])(.*?)\1/is', $match[2], $style)
                && preg_match('/(?:^|;)\s*text-align\s*:\s*(left|center|right|justify)\s*(?:;|$)/i', $style[2], $align)) {
                $alignment = ' style="text-align: '.strtolower($align[1]).'"';
            } elseif (preg_match('/\balign\s*=\s*[\'"]?(left|center|right|justify)\b/i', $match[2], $align)) {
                $alignment = ' style="text-align: '.strtolower($align[1]).'"';
            }

            return '<'.strtolower($match[1]).$alignment.$link.'>';
        }, $html);
    }
}
