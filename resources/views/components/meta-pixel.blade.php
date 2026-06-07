@php
    $metaPixelId = \App\Models\Setting::get('meta_pixel_id');
@endphp
@if($metaPixelId)
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ $metaPixelId }}');
fbq('track', 'PageView');
@if(request()->routeIs('cliente.planes'))
fbq('track', 'ViewContent', {
    content_name: 'Planes',
    content_category: 'Plans'
});
@endif
@if(request()->routeIs('cliente.estados-solicitud') || request()->routeIs('cliente.solicitudes.*'))
fbq('track', 'Lead', {
    content_name: 'Solicitud de Plan',
    content_category: 'Plans'
});
@endif
@if(request()->is('register') || request()->routeIs('register'))
fbq('track', 'ViewContent', {
    content_name: 'Registro',
    content_category: 'Registration'
});
@endif
@if(request()->is('login') || request()->routeIs('login'))
fbq('track', 'ViewContent', {
    content_name: 'Login',
    content_category: 'Authentication'
});
@endif
@if(request()->routeIs('flow.return'))
{{-- Purchase event is handled in the success view directly --}}
@endif
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
@endif
