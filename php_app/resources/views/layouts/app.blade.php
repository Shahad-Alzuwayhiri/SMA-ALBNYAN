<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $BRAND['name'] ?? '' }}@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('static/css/style.css') }}">
    @if(!empty($auth_page))
      <link rel="stylesheet" href="{{ asset('static/css/auth.css') }}">
    @else
      <link rel="stylesheet" href="{{ asset('static/css/dashboard_custom.css') }}">
      <link rel="stylesheet" href="{{ asset('static/css/contracts_modern.css') }}">
      <link rel="stylesheet" href="{{ asset('static/css/topnav_custom.css') }}">
    @endif
    @stack('head')
</head>
<body class="{{ !empty($auth_page) ? 'auth-bg bg-[#e3e8ee]' : 'dashboard-bg bg-[#e3e8ee]' }}">
  @unless(!empty($auth_page))
    @include('partials._topnav')
    @include('partials._sidebar')
    <div style="height:64px"></div>
    <div class="sama-header">
      {{ $BRAND['name'] ?? 'سما التجارية' }}
    </div>
  @endunless

    <div class="container">
        @if(session('status'))
            <div class="alert alert-info">{{ session('status') }}</div>
        @endif
        @if(session('flash'))
            @php $f = session('flash'); @endphp
            <div class="alert alert-{{ $f['type'] ?? 'info' }}">{{ $f['message'] ?? '' }}</div>
        @endif

        @yield('content')
    </div>

  @unless(!empty($auth_page))
    <div class="sama-footer">
      جميع الحقوق محفوظة &copy; {{ $BRAND['name'] ?? 'سما التجارية' }} 2025
    </div>
  @endunless

  <div class="sidebar-overlay" aria-hidden="true"></div>

    @stack('scripts')
    <script src="{{ asset('static/js/dashboard.js') }}"></script>
</body>
</html>
