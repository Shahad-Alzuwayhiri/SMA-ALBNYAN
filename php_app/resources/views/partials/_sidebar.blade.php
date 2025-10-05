<aside id="mainSidebar" class="sama-sidebar fixed top-0 right-0 w-[240px] h-screen bg-[#d6dbe3] shadow-inner flex flex-col justify-between p-4" role="navigation" aria-label="القائمة الجانبية">
  <div>
    <div class="flex items-center justify-end mb-4">
      <a href="{{ route('dashboard') }}" class="flex items-center gap-2 brand" title="لوحة التحكم" style="text-decoration:none;color:var(--text-default);font-weight:800">
        <span class="logo" aria-hidden="true">CS</span>
        <span class="brand-text">شركة سما البنيان التجارية</span>
      </a>
    </div>

    <div class="group-3" style="display:flex;flex-direction:column;gap:12px;">
      <div class="dashboard-black" style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('dashboard') }}" class="sidebar-item {{ isset($page) && $page == 'dashboard' ? 'active' : '' }}" title="لوحة التحكم" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img class="vector" src="{{ asset('static/img/image.svg') }}" alt="dashboard icon" style="width:18px;height:18px;" />
          <div class="text-wrapper-2">لوحة التحكم</div>
        </a>
      </div>

      <div>
        <a href="{{ route('contracts.index') }}" class="sidebar-item {{ isset($page) && in_array($page, ['contracts_list','contracts_detail','contracts_edit','contracts_create']) ? 'active' : '' }}" title="العقود" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/documents.svg') }}" alt="contracts" style="width:18px;height:18px;" />
          <div class="text-wrapper-3">العقود</div>
        </a>
      </div>

      <div>
        <a href="{{ route('contracts.index') }}" class="sidebar-item {{ isset($page) && $page == 'contracts_in_progress' ? 'active' : '' }}" title="تحت الإجراء" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/stopwatch.svg') }}" alt="in-progress" style="width:18px;height:18px;" />
          <div class="text-wrapper-4">تحت الإجراء</div>
          @if(!empty($in_progress_count) && $in_progress_count>0)
            <span class="badge" aria-hidden="true">{{ $in_progress_count }}</span>
          @endif
        </a>
      </div>

      <div>
        <a href="{{ route('contracts.index') }}" class="sidebar-item {{ isset($page) && $page == 'contracts_closed' ? 'active' : '' }}" title="العقود المغلقة" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/close.svg') }}" alt="closed" style="width:18px;height:18px;" />
          <div class="text-wrapper-5">العقود المغلقة</div>
        </a>
      </div>

      <div>
        <a href="{{ route('notifications') }}" class="sidebar-item {{ isset($page) && $page == 'notifications' ? 'active' : '' }}" title="الإشعارات" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/alarm.svg') }}" alt="notifications" style="width:18px;height:18px;" />
          <div class="text-wrapper-6">الإشعارات</div>
          @if(!empty($unread_notifications) && $unread_notifications>0)
            <span class="badge red">{{ $unread_notifications }}</span>
          @endif
        </a>
      </div>

      <div>
        <a href="{{ route('profile') }}" class="sidebar-item {{ isset($page) && $page == 'profile' ? 'active' : '' }}" title="الملف الشخصي" style="display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;">
          <img src="{{ asset('static/img/customer.svg') }}" alt="profile" style="width:18px;height:18px;" />
          <div class="text-wrapper-7">الملف الشخصي</div>
        </a>
      </div>

      <div>
        <a href="{{ route('logout') }}" class="sidebar-item logout" style="display:flex;align-items:center;gap:10px;width:100%;background:transparent;border:0;color:#b52020;padding:4px;border-radius:8px;text-decoration:none;text-align:right">
          <img src="{{ asset('static/img/logout.svg') }}" alt="logout" style="width:18px;height:18px;" />
          <div class="text-wrapper-8">خروج</div>
        </a>
      </div>
    </div>
  </div>

</aside>
