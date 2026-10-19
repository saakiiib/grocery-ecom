@php
    // One @graph per page: a single business entity (GroceryStore/Organization),
    // the WebSite node, plus whatever page-specific nodes the controller shares
    // (Product, BreadcrumbList…). Referencing @id avoids duplicate entities.
    $seoGraph = [\App\Support\Seo::organization(), \App\Support\Seo::website()];
    foreach (($schema ?? []) as $seoNode) {
        if (is_array($seoNode) && $seoNode !== []) {
            $seoGraph[] = $seoNode;
        }
    }
    $seoJsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $seoContext = '@'.'context';
@endphp
<script type="application/ld+json">
{!! json_encode([$seoContext => 'https://schema.org', '@graph' => $seoGraph], $seoJsonFlags) !!}
</script>
