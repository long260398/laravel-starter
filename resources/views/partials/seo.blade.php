@php
    $seoTitle = $seoTitle ?? setting('seo_title') ?? setting('site_name');
    $seoDescription = $seoDescription ?? setting('seo_description');
    $ogImageUrl = $ogImageUrl ?? public_file_url(setting('og_image'));
    $canonicalUrl = $canonicalUrl ?? url()->current();
@endphp

<title>{{ $seoTitle }}</title>

@if ($seoDescription)
    <meta name="description" content="{{ $seoDescription }}">
@endif

<link rel="canonical" href="{{ $canonicalUrl }}">

<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:url" content="{{ $canonicalUrl }}">

@if ($seoDescription)
    <meta property="og:description" content="{{ $seoDescription }}">
@endif

@if ($ogImageUrl)
    <meta property="og:image" content="{{ $ogImageUrl }}">
@endif
