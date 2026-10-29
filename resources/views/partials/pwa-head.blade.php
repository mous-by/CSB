{{-- Application installable (PWA) avec l'icône de CSB. $app : « site » (site public) ou « team » (administration). --}}
<link rel="manifest" href="{{ route($app === 'team' ? 'site.manifest.team' : 'site.manifest') }}">
<link rel="apple-touch-icon" href="{{ asset($app === 'team' ? 'assets/pwa/admin-apple-touch-icon.png' : 'assets/pwa/apple-touch-icon.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $app === 'team' ? 'CSB Admin' : 'CSB' }}">
