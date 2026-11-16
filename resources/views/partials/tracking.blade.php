@php
    $gaMeasurementId = setting('ga_measurement_id');
    $fbPixelId = setting('fb_pixel_id');
@endphp

@if ($gaMeasurementId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaMeasurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', @json($gaMeasurementId));
    </script>
@endif

@if ($fbPixelId)
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($fbPixelId));
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1">
    </noscript>
@endif
