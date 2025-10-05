<!doctype html>
<header class="box" role="banner" aria-label="شريط الأدوات العلوي">
  <link rel="stylesheet" href="{{ asset('static/css/topnav_custom.css') }}">
  <nav class="group" role="navigation" aria-label="Main navigation">
    <div class="rectangle" aria-hidden="true"></div>
    <h1 class="text-wrapper">
      <img src="{{ asset('static/img/sama_logo.png') }}" alt="شعار الشركة" style="height:40px; vertical-align:middle; margin-left:10px;" />
      شركة سما البنيان التجارية للتطوير و الاستثمار العقاري
    </h1>
    <div class="breadcrumb">
      <form class="menu" role="search" action="{{ route('contracts.index') }}" method="get" aria-label="Search contracts">
        <div class="input-withaddons">
          <div class="auto-added-frame">
            <span class="addon" aria-hidden="true">🔍</span>
          </div>
          <input
            type="search"
            id="contract-search"
            name="query"
            class="input-fieldtext"
            placeholder="ابحث عن العقد"
            aria-label="ابحث عن العقد"
            value="{{ request()->get('query','') }}"
          />
        </div>
      </form>
    </div>
    <a class="vector-link" href="{{ route('dashboard') }}" aria-label="الصفحة الرئيسية">
      <img class="vector" src="{{ asset('static/img/vector.svg') }}" alt="" role="presentation"/>
    </a>
  </nav>
</header>
