@extends('layouts.app')
@section('title', 'لوحة التحكم')

@section('content')
<div class="app-container content-with-sidebar">
  <div class="container mx-auto px-4 max-w-screen-xl space-y-6">

    {{-- Quick Actions Card --}}
    <div class="bg-white rounded-xl shadow-md p-4 mb-6">
      <h3 class="font-bold" style="text-align:center;margin:0 0 12px">إجراءات سريعة</h3>
      <div class="flex justify-center" style="gap:12px;flex-wrap:wrap">
        {{-- quick action buttons --}}
        <a href="{{ route('contracts.create') }}" class="qa-btn" aria-label="عقد جديد">
          <span class="qa-icon" aria-hidden="true">➕</span>
          <span class="qa-label">عقد جديد</span>
        </a>
        <a href="{{ route('contracts.index') }}" class="qa-btn" aria-label="تعديل عقد">
          <span class="qa-icon" aria-hidden="true">🖊</span>
          <span class="qa-label">تعديل عقد</span>
        </a>
        <a href="#" class="qa-btn" aria-label="إرسال للتوقيع">
          <span class="qa-icon" aria-hidden="true">✉️</span>
          <span class="qa-label">إرسال للتوقيع</span>
        </a>
        <a href="#" class="qa-btn" aria-label="تصدير PDF">
          <span class="qa-icon" aria-hidden="true">⬇️</span>
          <span class="qa-label">تصدير PDF</span>
        </a>
        <a href="#" class="qa-btn" aria-label="أرشفة">
          <span class="qa-icon" aria-hidden="true">📂</span>
          <span class="qa-label">أرشفة</span>
        </a>
      </div>
    </div>

    {{-- Contracts Summary Cards --}}
    <div class="max-w-cards">
      <section class="grid grid-cols-12 gap-6 mb-6 cards-grid">
        <!-- إجمالي العقود -->
        <div class="col-span-12 lg:col-span-4">
          <div class="summary-card summary-yellow min-h-[160px]">
            <div class="top-strip"></div>
            <div class="card-body">
              <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <h4 class="card-title">إجمالي العقود</h4>
                <span class="w-7 h-7 rounded-full" style="background:#f0ad4e;"></span>
              </div>
              <div class="card-number text-4xl">{{ $metrics['total_count'] ?? 0 }}</div>
              <p class="card-sub">مجموع السجلات</p>
            </div>
          </div>
        </div>

        <!-- تحت الإجراء -->
        <div class="col-span-12 lg:col-span-4">
          <div class="summary-card summary-blue min-h-[160px]">
            <div class="top-strip"></div>
            <div class="card-body">
              <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <h4 class="card-title">العقود تحت الإجراء</h4>
                <span class="w-7 h-7 rounded-full" style="background:#4a90e2;"></span>
              </div>
              <div class="card-number text-4xl">{{ $metrics['in_progress'] ?? $metrics['pending_count'] ?? 0 }}</div>
              <p class="card-sub">قيد التنفيذ</p>
            </div>
          </div>
        </div>

        <!-- مغلقة -->
        <div class="col-span-12 lg:col-span-4">
          <div class="summary-card summary-red min-h-[160px]">
            <div class="top-strip"></div>
            <div class="card-body">
              <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <h4 class="card-title">العقود المغلقة</h4>
                <span class="w-7 h-7 rounded-full" style="background:#d9534f;"></span>
              </div>
              <div class="card-number text-4xl">{{ $metrics['closed_count'] ?? 0 }}</div>
              <p class="card-sub">منتهية أو مرفوضة</p>
            </div>
          </div>
        </div>
      </section>
    </div>

    {{-- Below the Cards: Notifications (left) and Tasks (right) --}}
    <section class="grid grid-cols-12 gap-6 mb-6">
      <div class="col-span-12 md:col-span-6">
        <div class="bg-white rounded-xl shadow-md p-4">
          <h4 class="font-bold" style="margin:0 0 10px;text-align:right">الإشعارات</h4>
          @if($tasks ?? false)
            <ul style="list-style:none;padding:0;margin:0;direction:rtl">
              @foreach($tasks as $task)
                <li style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:1px solid var(--border)">
                  <div style="display:flex;align-items:center;gap:10px">
                    <form method="post" action="{{ route('tasks.update', $task['id']) }}" style="display:inline">
                      @csrf
                      <input type="checkbox" name="done" onchange="this.form.submit()" {{ $task['status'] == 'done' ? 'checked' : '' }} aria-label="وضع كمكتملة">
                    </form>
                    <div>
                      <div style="font-weight:700">{{ $task['title'] }}</div>
                      <div style="color:var(--muted);font-size:0.9rem">{{ $task['due_date'] ?? '' }}</div>
                    </div>
                  </div>
                  <div>
                    <a href="{{ route('tasks.delete', $task['id']) }}" class="btn btn-outline">حذف</a>
                  </div>
                </li>
              @endforeach
            </ul>
          @else
            <div style="color:var(--muted);text-align:right">لا توجد مهام حالياً</div>
          @endif
        </div>
      </div>

      <div class="col-span-12 md:col-span-6">
        <div class="bg-white rounded-xl shadow-md p-4">
          <h4 class="font-bold" style="margin:0 0 10px;text-align:right">مهامي</h4>
          @if($notifications ?? false)
            <ul style="list-style:none;padding:0;margin:0;direction:rtl">
              @foreach(array_slice($notifications, 0, 5) as $notification)
                <li style="padding:10px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                  <div style="max-width:75%">{{ $notification['message'] }}</div>
                  <div style="font-size:0.8rem;color:var(--muted)">{{ $notification['human_time'] ?? $notification['created_at'] }}</div>
                </li>
              @endforeach
            </ul>
            <div style="text-align:right;margin-top:10px">
              <a href="{{ route('notifications') }}" class="btn btn-outline">عرض الكل</a>
            </div>
          @else
            <div style="color:var(--muted);text-align:right">لا توجد إشعارات جديدة</div>
          @endif
        </div>
      </div>
    </section>

  </div>
</div>
@endsection