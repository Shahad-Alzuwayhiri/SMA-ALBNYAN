@extends('layouts.app')
@section('title', 'لوحة تحكم المدير')

@push('styles')
<link rel="stylesheet" href="{{ asset('static/css/dashboard_custom.css') }}">
@endpush

@section('content')
<div class="dashboard-layout">
  <div class="dashboard-main">
    <div class="modern-header" style="display:flex;justify-content:flex-end;align-items:center;">
      <a href="javascript:history.back()" class="btn btn-outline" style="font-size:1.7em;">← العودة</a>
    </div>

    <div class="dashboard-title">لوحة تحكم المدير</div>

    <!-- quick status cards -->
    <div class="stats-row" style="max-width:1100px;margin:6px auto 14px;">
      <div class="stat-card" style="background:linear-gradient(180deg,var(--purple-1),var(--purple-2));">
        <div class="card-icon">📄</div>
        <div class="stat-title">كل العقود</div>
        <div class="stat-value">{{ $metrics['total_count'] ?? 0 }}</div>
        <div class="stat-desc">جميع العقود</div>
      </div>
      <div class="stat-card" style="background:var(--sama-accent);">
        <div class="card-icon">⏳</div>
        <div class="stat-title">تحت الإجراء</div>
        <div class="stat-value">{{ $metrics['pending_count'] ?? 0 }}</div>
        <div class="stat-desc">بانتظار المعالجة</div>
      </div>
      <div class="stat-card" style="background:var(--accent-red);">
        <div class="card-icon">❌</div>
        <div class="stat-title">مغلقة</div>
        <div class="stat-value">{{ $metrics['closed_count'] ?? 0 }}</div>
        <div class="stat-desc">العقود المنتهية</div>
      </div>
      <div class="stat-card" style="background:var(--sama-gold);color:var(--primary-navy);">
        <div class="card-icon">👥</div>
        <div class="stat-title">الموظفين</div>
        <div class="stat-value">{{ $metrics['employees_count'] ?? $metrics['users_count'] ?? 0 }}</div>
        <div class="stat-desc">إجمالي الموظفين</div>
      </div>
    </div>

    <!-- ميزات لوحة المدير -->
    <details style="width:100%;max-width:1100px;margin:6px auto 18px;color:var(--primary-blue);">
      <summary style="cursor:pointer;font-weight:700">📋 وصف ميزات لوحة المدير (انقر للعرض)</summary>
      <pre style="white-space:pre-wrap;direction:rtl;text-align:right;color:var(--primary-blue);padding:12px;background:rgba(12,37,64,0.03);border-radius:8px;margin-top:8px;">1. إحصاءات رئيسية فورية
2. الرسوم البيانية والتحليلات
3. إدارة الملفات والتوقيعات
4. التنبيهات والإشعارات
5. قائمة المهام (To-Do List)
6. آخر الأنشطة
7. روابط وإجراءات سريعة
8. متابعة الموظفين والفِرق
9. الأرشيف والسجلات
10. إضافات اختيارية: تقويم، ويدجت الطقس، لوحة إنجازات، مراسلة داخلية.</pre>
    </details>

    <!-- اللوحات المركزية -->
    <div class="panels" style="max-width:1100px;margin:18px auto;display:grid;grid-template-columns:2fr 1fr 300px;gap:18px;">
      <!-- اللوحة اليسرى: مخطط + ملخصات -->
      <div style="display:flex;flex-direction:column;gap:18px;">
        <div class="auth-card" style="padding:12px;">
          <h4 style="margin:0 0 8px;text-align:right;color:var(--navy)">مخططات وتحليلات</h4>
          <canvas id="chart_main" style="width:100%;height:260px" aria-label="مخطط أداء"></canvas>
          <script id="chart-data" type="application/json">{!! json_encode($chart_data ?? null) !!}</script>
        </div>

        <div class="auth-card" style="padding:12px;">
          <h4 style="margin:0 0 8px;text-align:right;color:var(--navy)">قائمة المهام</h4>
          <ul style="margin:0;padding:0;list-style:none;direction:rtl">
            @if($tasks ?? false)
              @foreach($tasks as $task)
                <li style="padding:8px 6px;border-bottom:1px solid #f2f4f6;display:flex;justify-content:space-between;align-items:center">
                  <div>{{ $task['title'] }}</div>
                  <div style="font-size:0.85rem;color:var(--muted)">— {{ $task['assigned_by'] ?? $task['owner'] ?? 'نظام' }}</div>
                </li>
              @endforeach
            @else
              <li style="padding:8px 6px;border-bottom:1px solid #f2f4f6">لا توجد مهام حالياً</li>
            @endif
          </ul>
        </div>
      </div>

      <!-- اللوحة اليمنى: ملفات للمعاينة، إشعارات، أنشطة -->
      <div style="display:flex;flex-direction:column;gap:18px;">
        <div class="auth-card" style="padding:12px;">
          <h4 style="margin:0 0 8px;text-align:right;color:var(--navy)">ملفات للمعاينة</h4>
          <div style="display:flex;flex-direction:column;gap:8px">
            @php $files_shown = 0; @endphp
            @if($recent_activities ?? false)
              @foreach($recent_activities as $activity)
                @if(in_array($activity['type'] ?? '', ['file', 'upload']) && $files_shown < 6)
                  <div style="padding:6px;border-bottom:1px solid #f3f5f7">{{ $activity['title'] ?? $activity['name'] }}</div>
                  @php $files_shown++; @endphp
                @endif
              @endforeach
            @endif
            @if($files_shown == 0)
              <div class="muted">لا توجد ملفات حديثة للمعاينة</div>
            @endif
          </div>
        </div>

        <div class="auth-card" style="padding:12px;">
          <h4 style="margin:0 0 8px;text-align:right;color:var(--navy)">التنبيهات</h4>
          <div style="font-size:0.95rem;color:var(--muted);direction:rtl">
            @if($notifications ?? false)
              <ul style="margin:0;padding:0;list-style:none">
                @foreach(array_slice($notifications, 0, 8) as $notification)
                  <li style="padding:8px 6px;border-bottom:1px dashed #f2f4f6;display:flex;justify-content:space-between;align-items:center">
                    <div style="max-width:75%">{{ $notification['message'] ?? $notification['title'] }}</div>
                    <div style="font-size:0.8rem;color:var(--primary-blue)">{{ $notification['human_time'] ?? $notification['created_at'] }}</div>
                  </li>
                @endforeach
              </ul>
            @else
              <div>لا توجد تنبيهات جديدة</div>
            @endif
          </div>
        </div>

        <div class="auth-card" style="padding:12px;">
          <h4 style="margin:0 0 8px;text-align:right;color:var(--navy)">آخر الأنشطة</h4>
          <div style="font-size:0.95rem;color:var(--muted);direction:rtl">
            @if($recent_activities ?? false)
              @foreach(array_slice($recent_activities, 0, 10) as $activity)
                <div style="padding:8px 6px;border-bottom:1px dashed #f2f4f6">
                  <div style="font-weight:700">{{ $activity['summary'] ?? $activity['title'] ?? ($activity['type'] . ' — ' . ($activity['actor'] ?? 'نظام')) }}</div>
                  <div style="font-size:0.85rem;color:var(--muted)">{{ $activity['human_time'] ?? $activity['created_at'] }}</div>
                </div>
              @endforeach
            @else
              <div>لا توجد أنشطة حديثة</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- جدول العقود -->
    <h3>كل العقود</h3>
    <div style="overflow:auto">
      <table class="contracts-table" role="table" aria-label="قائمة العقود">
        <thead>
          <tr>
            <th>رقم العقد</th>
            <th>الموظف</th>
            <th>العميل</th>
            <th>الحالة</th>
            <th>تاريخ الإنشاء</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @if($all_contracts ?? false)
            @foreach($all_contracts as $contract)
              <tr>
                <td style="white-space:nowrap">{{ $contract['serial'] }}</td>
                <td>{{ $contract['employee_name'] }}</td>
                <td>{{ $contract['client_name'] }}</td>
                <td>{!! $contract['status_display'] !!}</td>
                <td>{{ $contract['created_at'] ? date('Y-m-d', strtotime($contract['created_at'])) : '' }}</td>
                <td style="white-space:nowrap">
                  <a href="{{ route('contracts.show', $contract['id']) }}" class="btn btn-outline">عرض</a>
                  @if($contract['status'] == 'pending')
                    <form method="post" action="{{ route('manager.approve', $contract['id']) }}" style="display:inline">
                      @csrf
                      <button class="btn btn-success btn-sm" type="submit">اعتماد</button>
                    </form>
                    <form method="post" action="{{ route('manager.reject', $contract['id']) }}" style="display:inline">
                      @csrf
                      <button class="btn btn-danger btn-sm" type="submit">رفض</button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          @else
            <tr><td colspan="6" style="text-align:center;padding:18px;color:var(--muted)">لا توجد عقود لعرضها</td></tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const dataTag = document.getElementById('chart-data');
  let data = null;
  if (dataTag) {
    try {
      data = JSON.parse(dataTag.textContent);
    } catch (e) {
      data = null;
    }
  }
  const ctx = document.getElementById('chart_main');
  if (!ctx) return;
  if (typeof Chart !== 'undefined' && data) {
    new Chart(ctx.getContext('2d'), data);
  } else {
    // رسم بديل بسيط (شريط رمزي) لعرض الشكل دون مكتبة
    if (ctx.getContext) {
      const c = ctx.getContext('2d');
      c.fillStyle = '#f3f4f6';
      c.fillRect(0,0,ctx.width,ctx.height);
      c.fillStyle = '#cbd5e1';
      c.fillRect(20, ctx.height - 60, 60, 40);
      c.fillRect(100, ctx.height - 90, 60, 70);
      c.fillRect(180, ctx.height - 40, 60, 20);
      c.fillStyle = 'var(--muted)';
      c.font = '13px sans-serif';
      c.fillText('مخطط (Chart.js غير محمّل)', 10, 20);
    }
  }
})();
</script>
@endpush
@endsection