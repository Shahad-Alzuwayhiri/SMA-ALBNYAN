<aside id="mainSidebar" class="sama-sidebar fixed top-0 right-0 w-[240px] h-screen bg-[#d6dbe3] shadow-inner flex flex-col justify-between p-4" role="navigation" aria-label="القائمة الجانبية" aria-hidden="false">
  <div>
    <div class="flex items-center justify-end mb-4">
      <a href="{{ route('dashboard') }}" class="flex items-center gap-2 brand" title="لوحة التحكم" style="text-decoration:none;color:var(--text-default);font-weight:800">
        <span class="logo" aria-hidden="true">CS</span>
        <span class="brand-text">شركة سما البنيان التجارية</span>
      </a>
    </div>

    <div class="group-3" style="display:flex;flex-direction:column;gap:12px;">
      <div class="dashboard-black" style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="لوحة التحكم" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img class="vector" src="{{ asset('static/img/image.svg') }}" alt="dashboard icon" style="width:18px;height:18px;" />
          <div class="text-wrapper-2">لوحة التحكم</div>
        </a>
      </div>

      <div>
        <a href="{{ route('contracts.index') }}" class="sidebar-item {{ request()->routeIs('contracts.*') ? 'active' : '' }}" title="العقود" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/documents.svg') }}" alt="contracts" style="width:18px;height:18px;" />
          <div class="text-wrapper-3">العقود</div>
        </a>
      </div>

      <div>
        <a href="#" class="sidebar-item" title="تحت الإجراء" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/stopwatch.svg') }}" alt="in-progress" style="width:18px;height:18px;" />
          <div class="text-wrapper-4">تحت الإجراء</div>
        </a>
      </div>

      <div>
        <a href="#" class="sidebar-item" title="العقود المغلقة" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/close.svg') }}" alt="closed" style="width:18px;height:18px;" />
          <div class="text-wrapper-5">العقود المغلقة</div>
        </a>
      </div>

      <div>
        <a href="#" class="sidebar-item" title="الملف الشخصي" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/customer.svg') }}" alt="profile" style="width:18px;height:18px;" />
          <div class="text-wrapper-6">الملف الشخصي</div>
        </a>
      </div>
    </div>
  </div>

  <div class="bottom-section">
    <a href="#" class="sidebar-item logout" title="تسجيل الخروج" style="display:flex;align-items:center;gap:10px;color:#dc2626;text-decoration:none;">
      <img src="{{ asset('static/img/logout.svg') }}" alt="logout" style="width:18px;height:18px;" />
      <div class="text-wrapper-logout">تسجيل الخروج</div>
    </a>
  </div>
</aside>