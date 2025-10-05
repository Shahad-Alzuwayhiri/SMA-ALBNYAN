@extends('layouts.app')
@section('title', 'الإشعارات')

@push('styles')
<link rel="stylesheet" href="{{ asset('static/css/dashboard_custom.css') }}">
@endpush

@section('content')
<div class="dashboard-main">
  <button onclick="window.history.back()" class="btn-back" title="رجوع">&#8594;</button>
  
  <div class="dashboard-title">الإشعارات</div>
  
  <div class="notifications-container">
    @forelse($notifications as $notification)
      <div class="notification-item {{ $notification['read'] ? 'read' : 'unread' }}">
        <div class="notification-content">
          <h4>{{ $notification['title'] ?? 'إشعار' }}</h4>
          <p>{{ $notification['message'] }}</p>
          <small class="text-muted">
            {{ $notification['created_at'] ? \Carbon\Carbon::parse($notification['created_at'])->format('Y-m-d H:i') : '' }}
          </small>
        </div>
        @if(!$notification['read'])
          <div class="notification-actions">
            <a href="{{ route('notifications.read', $notification['id']) }}" class="btn btn-sm btn-outline">وضع كمقروء</a>
          </div>
        @endif
      </div>
    @empty
      <div class="empty-state">
        <div class="empty-icon">🔔</div>
        <h3>لا توجد إشعارات</h3>
        <p class="text-muted">ستظهر الإشعارات الجديدة هنا</p>
      </div>
    @endforelse
  </div>
  
  @if($notifications && count($notifications) > 0)
    <div class="notifications-actions">
      <a href="{{ route('notifications.mark-all-read') }}" class="btn btn-outline">
        وضع الكل كمقروء
      </a>
      <a href="{{ route('notifications.clear-all') }}" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف جميع الإشعارات؟')">
        حذف الكل
      </a>
    </div>
  @endif
</div>

<style>
.notifications-container {
  margin-top: 20px;
}

.notification-item {
  background: #fff;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.notification-item.unread {
  border-left: 4px solid var(--primary-blue);
  background: #f8f9fa;
}

.notification-content h4 {
  margin: 0 0 8px 0;
  color: var(--primary-blue);
  font-size: 1rem;
}

.notification-content p {
  margin: 0 0 8px 0;
  line-height: 1.5;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 16px;
}

.notifications-actions {
  margin-top: 30px;
  display: flex;
  gap: 12px;
  justify-content: center;
}
</style>
@endsection