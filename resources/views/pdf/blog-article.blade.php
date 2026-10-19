{{--
    A blog post as a typeset A4 PDF (BlogController::download, posts with
    downloadable: true). Rendered by dompdf: DejaVu Sans only, tables instead
    of flexbox, local images only, `position: fixed` repeats on every page.
    Cover → contents and key takeaways → body (diagrams as print SVG from
    App\Support\Blog\DiagramPdf) → FAQ → sources → about the author.
--}}
@php

    $pdDate = fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('j F Y');
    $pdSeal = public_path('images/health-id/sheet-seal.png');
    $pdLogo = public_path('brand/opescare-logo-512.png');
    $pdTitle = $post['title'];
    $pdMain = trim(preg_split('/[:—–]/u', $pdTitle)[0]);
    $pdSub = trim(mb_substr($pdTitle, mb_strlen($pdMain)), " :—–");
    $pdH2 = array_values(array_filter($post['toc'], fn ($h) => $h['level'] === 2));
    $pdFig = 0;
@endphp
<!DOCTYPE html>
<html lang="{{ $post['locale'] }}">
<head>
<meta charset="UTF-8">
<title>{{ $pdTitle }} — {{ $post['author'] }}</title>
<style>
    @page { margin: 24mm 20mm 22mm 20mm; }
    body { margin: 0; font-family: "DejaVu Sans", sans-serif; font-size: 10.2pt; line-height: 1.6; color: #1E293B; }

    /* Running head and foot (every page; the cover paints over them). */
    .run-head { position: fixed; top: -15mm; left: 0; right: 0; height: 8mm; border-bottom: .4pt solid #C9D7E6; font-size: 7.5pt; color: #64748B; letter-spacing: .6pt; text-transform: uppercase; }
    .run-head td { padding: 0 0 2mm; vertical-align: bottom; }
    .run-foot { position: fixed; bottom: -14mm; left: 0; right: 0; height: 8mm; font-size: 7.5pt; color: #64748B; }
    .run-foot td { padding-top: 2mm; border-top: .4pt solid #C9D7E6; vertical-align: top; }
    .run-foot .pg { text-align: right; color: #0F4C81; font-weight: bold; white-space: nowrap; }
    .run-foot .pg .n:after { content: counter(page); }

    /* Cover */
    .cover { position: absolute; top: -24mm; left: -20mm; width: 210mm; height: 297mm; background: #0E1729; z-index: 10; }
    .cover .rule { position: absolute; left: 0; top: 0; width: 210mm; height: 3mm; background: #0F4C81; }
    .cover .rule2 { position: absolute; left: 0; top: 3mm; width: 210mm; height: .6mm; background: #EADFC8; }
    .cover .brand { position: absolute; top: 20mm; left: 22mm; width: 166mm; }
    .cover .brand img { width: 13mm; height: 13mm; vertical-align: middle; }
    .cover .brand span { font-size: 15pt; font-weight: bold; color: #FFFFFF; letter-spacing: .5pt; vertical-align: middle; padding-left: 3mm; }
    .cover .kicker { position: absolute; top: 74mm; left: 22mm; width: 166mm; font-size: 8.5pt; color: #EADFC8; letter-spacing: 2.4pt; text-transform: uppercase; }
    .cover h1 { position: absolute; top: 84mm; left: 22mm; width: 166mm; margin: 0; font-size: 34pt; line-height: 1.12; color: #FFFFFF; letter-spacing: -.4pt; }
    .cover .sub { position: absolute; top: 132mm; left: 22mm; width: 150mm; font-size: 13pt; line-height: 1.4; color: #9FC3E7; }
    .cover .dek { position: absolute; top: 152mm; left: 22mm; width: 140mm; font-size: 10pt; line-height: 1.55; color: #C9D3E3; }
    .cover .seal { position: absolute; top: 196mm; left: 22mm; }
    .cover .seal img { width: 40mm; height: 40mm; }
    .cover .byline { position: absolute; top: 204mm; left: 72mm; width: 116mm; }
    .cover .byline .by { font-size: 7.5pt; color: #8FA5C6; letter-spacing: 1.8pt; text-transform: uppercase; }
    .cover .byline .name { font-size: 17pt; font-weight: bold; color: #FFFFFF; margin-top: 1mm; }
    .cover .byline .date { font-size: 9pt; color: #C9D3E3; margin-top: 2mm; }
    .cover .foot { position: absolute; bottom: 14mm; left: 22mm; width: 166mm; font-size: 7.5pt; color: #8FA5C6; border-top: .4pt solid #2A3A57; padding-top: 3mm; }

    .page-break { page-break-after: always; height: 0; }

    /* Contents + takeaways */
    .eyebrow { font-size: 7.5pt; color: #0F4C81; letter-spacing: 1.8pt; text-transform: uppercase; font-weight: bold; margin: 0 0 3mm; }
    .toc { width: 100%; border-collapse: collapse; margin-bottom: 6mm; }
    .toc td { padding: 1.4mm 0; border-bottom: .4pt solid #E2E8F0; font-size: 10.5pt; }
    .toc td.no { width: 10mm; color: #0F4C81; font-weight: bold; }
    .toc a { color: #0F172A; text-decoration: none; }
    .box { background: #EEF4FA; border-left: 1.2mm solid #0F4C81; padding: 4mm 5mm; margin: 0 0 6mm; }
    .box ul { margin: 0; padding-left: 4.5mm; }
    .box li { margin: 0 0 1.2mm; font-size: 9.2pt; line-height: 1.45; }

    /* Body */
    .prose h2 { font-size: 15pt; line-height: 1.25; color: #0E1729; margin: 9mm 0 3mm; padding-top: 2mm; border-top: 1.2pt solid #0F4C81; page-break-after: avoid; }
    .prose h3 { font-size: 11.5pt; color: #0F4C81; margin: 6mm 0 2mm; page-break-after: avoid; }
    .prose p { margin: 0 0 3.2mm; text-align: left; }
    .prose ul, .prose ol { margin: 0 0 3.5mm; padding-left: 6mm; }
    .prose li { margin: 0 0 1.6mm; }
    .prose blockquote { margin: 0 0 6mm; padding: 4mm 6mm; background: #0E1729; color: #FFFFFF; font-size: 12pt; line-height: 1.5; }
    .prose blockquote p { margin: 0; }
    .prose strong { color: #0E1729; }
    .prose a { color: #0F4C81; text-decoration: none; }
    .prose hr { border: 0; border-top: .4pt solid #C9D7E6; margin: 6mm 0; }
    .prose code { font-family: "DejaVu Sans Mono", monospace; font-size: 8.8pt; }
    .fig { margin: 5mm 0 6mm; page-break-inside: avoid; border: .5pt solid #E2E8F0; background: #F8FAFC; padding: 3mm; }
    .fig img { width: 100%; }
    .fig .cap { font-size: 8pt; color: #64748B; margin-top: 2mm; }
    .fig .cap b { color: #0F4C81; }

    /* Back matter */
    h2.section { font-size: 13pt; color: #0E1729; margin: 9mm 0 3mm; padding-top: 2mm; border-top: 1.2pt solid #0F4C81; page-break-after: avoid; }
    .faq { margin: 0 0 3.5mm; page-break-inside: avoid; }
    .faq .q { font-weight: bold; color: #0E1729; margin: 0 0 1mm; }
    .faq .a { margin: 0; color: #334155; }
    .sources { margin: 0; padding-left: 6mm; font-size: 8.8pt; color: #334155; }
    .sources li { margin: 0 0 1.6mm; }
    .sources .u { color: #0F4C81; word-wrap: break-word; }
    .author { margin-top: 8mm; border: .6pt solid #C9D7E6; padding: 5mm; page-break-inside: avoid; }
    .author td { vertical-align: top; }
    .author img { width: 18mm; height: 18mm; }
    .author .name { font-size: 12pt; font-weight: bold; color: #0E1729; margin: 0 0 1.5mm; }
    .author .bio { margin: 0; font-size: 9.4pt; color: #334155; }
    .online { margin-top: 5mm; font-size: 8.5pt; color: #64748B; }
</style>
</head>
<body>

<table class="run-head" width="100%"><tr>
    <td>OpesCare · {{ $pdMain }}</td>
    <td style="text-align:right">{{ $post['author'] }}</td>
</tr></table>
<table class="run-foot" width="100%"><tr>
    <td>{{ __('blog.pdf_footer') }}</td>
    <td class="pg"><span class="n"></span></td>
</tr></table>

{{-- Cover --}}
<div class="cover">
    <div class="rule"></div><div class="rule2"></div>
    <div class="brand"><img src="{{ $pdLogo }}" alt=""><span>OpesCare</span></div>
    <div class="kicker">{{ $post['category'] !== '' ? $post['category'] : 'OpesCare' }} · opescare.com</div>
    <h1>{{ $pdMain }}</h1>
    @if($pdSub !== '')<div class="sub">{{ $pdSub }}</div>@endif
    <div class="dek">{{ $post['description'] }}</div>
    <div class="seal"><img src="{{ $pdSeal }}" alt=""></div>
    <div class="byline">
        <div class="by">{{ __('blog.by') }}</div>
        <div class="name">{{ $post['author'] }}</div>
        <div class="date">{{ __('blog.pdf_published', ['date' => $pdDate($post['date'])]) }}</div>
    </div>
    <div class="foot">{{ __('blog.pdf_read_online', ['url' => $url]) }}</div>
</div>
<div class="page-break"></div>

{{-- Contents and key takeaways --}}
<p class="eyebrow">{{ __('blog.pdf_contents') }}</p>
<table class="toc">
    @foreach($pdH2 as $i => $h)
        <tr><td class="no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</td><td><a href="#{{ $h['id'] }}">{{ preg_replace('/^\d+\.\s*/', '', $h['text']) }}</a></td></tr>
    @endforeach
    @if($post['faq'] !== [])<tr><td class="no">·</td><td><a href="#pd-faq">{{ __('blog.faq_title') }}</a></td></tr>@endif
    @if($post['sources'] !== [])<tr><td class="no">·</td><td><a href="#pd-sources">{{ __('blog.sources_title') }}</a></td></tr>@endif
    @if(($post['author_bio'] ?? '') !== '')<tr><td class="no">·</td><td><a href="#pd-author">{{ __('blog.about_author') }}</a></td></tr>@endif
</table>

@if($post['key_takeaways'] !== [])
    <p class="eyebrow">{{ __('blog.key_takeaways') }}</p>
    <div class="box"><ul>@foreach($post['key_takeaways'] as $k)<li>{{ $k }}</li>@endforeach</ul></div>
@endif
<div class="page-break"></div>

{{-- Body: the same sanitised HTML as the web article; diagrams as print SVG. --}}
<div class="prose">
    @foreach(\App\Support\Blog\BlogDiagrams::segments($post['html']) as $seg)
        @if(isset($seg['diagram']))
            @php $pdSvg = \App\Support\Blog\DiagramPdf::file($seg['diagram'], $post['locale']); $pdFig++; @endphp
            @if($pdSvg)
                <div class="fig">
                    <img src="{{ $pdSvg }}" alt="{{ \App\Support\Blog\BlogDiagrams::title($seg['diagram']) }}">
                    <div class="cap"><b>{{ __('blog.pdf_figure', ['n' => $pdFig, 'title' => \App\Support\Blog\BlogDiagrams::title($seg['diagram'])]) }}</b> · {{ __('blog.pdf_figure_online') }}</div>
                </div>
            @endif
        @else
            {!! $seg['html'] !!}
        @endif
    @endforeach
</div>

@if($post['faq'] !== [])
    <h2 class="section" id="pd-faq">{{ __('blog.faq_title') }}</h2>
    @foreach($post['faq'] as $q)
        <div class="faq"><p class="q">{{ $q['q'] }}</p><p class="a">{{ $q['a'] }}</p></div>
    @endforeach
@endif

@if($post['sources'] !== [])
    <h2 class="section" id="pd-sources">{{ __('blog.sources_title') }}</h2>
    <ol class="sources">
        @foreach($post['sources'] as $s)
            <li>{{ $s['title'] }}@if($s['publisher'] !== '') — {{ $s['publisher'] }}@endif
                @if($s['url'] !== '')<br><span class="u">{{ $s['url'] }}</span>@endif
                @if($s['accessed']) ({{ __('blog.accessed', ['date' => $pdDate($s['accessed'])]) }})@endif
            </li>
        @endforeach
    </ol>
@endif

@if(($post['author_bio'] ?? '') !== '')
    <table class="author" width="100%" id="pd-author"><tr>
        <td style="width:22mm"><img src="{{ $pdSeal }}" alt=""></td>
        <td>
            <p class="eyebrow" style="margin-bottom:1.5mm">{{ __('blog.about_author') }}</p>
            <p class="name">{{ $post['author'] }}</p>
            <p class="bio">{{ $post['author_bio'] }}</p>
        </td>
    </tr></table>
@endif

<p class="online">{{ __('blog.pdf_read_online', ['url' => $url]) }}</p>
</body>
</html>
