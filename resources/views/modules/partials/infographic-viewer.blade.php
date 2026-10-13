@php
    $bundledInfographics = require database_path('data/infographics.php');
    $englishInfographics = require database_path('data/infographics-en.php');
    $infographicTranslations = [];
    $visibleInfographicTitles = $infographicsByMonth->flatten()->pluck('title');
    foreach ($bundledInfographics as $index => $lesson) {
        if (! $visibleInfographicTitles->contains($lesson['title'])) continue;
        $english = $englishInfographics[$index];
        $infographicTranslations[] = [$english[0], $lesson['title']];
        $infographicTranslations[] = [$english[1], $lesson['description']];
        foreach ($lesson['infographic_sections'] as $stepIndex => $step) {
            $infographicTranslations[] = [$english[2][$stepIndex][0], $step[0]];
            $infographicTranslations[] = [$english[2][$stepIndex][1], $step[1]];
        }
    }
@endphp
<script type="application/json" data-infographic-translations>{!! json_encode($infographicTranslations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<dialog id="inayInfographicViewer" class="inay-infographic-viewer" aria-labelledby="inayInfographicTitle">
    <header class="inay-infographic-viewer-header">
        <div><span>INAY KAALAMAN</span><h2 id="inayInfographicTitle" tabindex="-1">Infographic</h2></div>
        <button type="button" data-infographic-close aria-label="Close infographic">✕</button>
    </header>
    <div class="inay-infographic-reader" tabindex="0" role="region" aria-label="Nilalaman ng infographic" data-infographic-reader></div>
    <footer class="inay-infographic-viewer-footer">
        <p data-infographic-save-message role="status" aria-live="polite"></p>
        <button type="button" data-infographic-retry hidden>Subukang i-save muli</button>
        <button type="button" data-infographic-close>Bumalik</button>
    </footer>
</dialog>
