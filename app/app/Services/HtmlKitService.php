<?php

namespace App\Services;

use App\Models\Creative;
use App\Models\Offer;
use App\Models\Partner;
use App\Models\TrafficSource;
use DOMDocument;
use DOMXPath;

class HtmlKitService
{
    public function __construct(private TrackingLinkService $trackingLinkService) {}

    public function processKit(
        Creative $creative,
        Offer $offer,
        Partner $partner,
        ?TrafficSource $source = null,
        array $subs = []
    ): string {
        $html = $creative->html_content ?? '';

        if (empty($html) && $creative->file_path) {
            $path = storage_path('app/public/'.$creative->file_path);
            if (file_exists($path)) {
                $html = file_get_contents($path);
            }
        }

        if (empty($html)) {
            throw new \RuntimeException('El creative no tiene contenido HTML.');
        }

        $link = $this->trackingLinkService->generate($offer, $partner, $source, null, $subs);
        $domain = $link->trackingDomain;
        $impressionUrl = $this->trackingLinkService->buildImpressionPixelUrl($offer, $partner, $source, $domain);

        $html = $this->rewriteLinks($html, $link->url);
        $html = $this->injectImpressionPixel($html, $impressionUrl);

        return $html;
    }

    protected function rewriteLinks(string $html, string $trackingUrl): string
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//a[@href]') as $anchor) {
            $href = $anchor->getAttribute('href');
            if (str_starts_with($href, 'http') || str_starts_with($href, '//')) {
                $anchor->setAttribute('href', $trackingUrl);
            }
        }

        $result = $doc->saveHTML();
        libxml_clear_errors();

        return $result ?: $html;
    }

    protected function injectImpressionPixel(string $html, string $pixelUrl): string
    {
        $pixel = '<img src="'.htmlspecialchars($pixelUrl, ENT_QUOTES).'" width="1" height="1" alt="" style="display:none;border:0;" />';

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $pixel.'</body>', $html, 1);
        }

        return $html.$pixel;
    }
}
