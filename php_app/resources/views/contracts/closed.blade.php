@extends('layouts.app')
@section('title', 'العقود المغلقة')

@push('styles')
<link rel="stylesheet" href="{{ asset('static/css/style.css') }}">
@endpush

@section('content')
<div class="dashboard-layout">
  <div class="dashboard-main">
    <div class="modern-header">
      <a href="javascript:history.back()" class="btn btn-outline">← العودة</a>
      <h2>العقود المغلقة</h2>
    </div>

    <div class="modern-table-wrap">
      <table class="modern-table">
        <thead>
          <tr>
            <th>PDF</th>
            <th>رقم العقد</th>
            <th>العميل</th>
            <th>الحالة</th>
            <th>تاريخ الإنشاء</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($contracts_closed as $contract)
            <tr>
              <td>
                <a href="{{ route('contracts.pdf', $contract->id) }}" class="modern-pdf-btn">PDF</a>
              </td>
              <td>{{ $contract->serial ?? '#' . $contract->id }}</td>
              <td>{{ $contract->client_name ?? 'غير محدد' }}</td>
              <td>
                <span class="modern-status modern-status-closed">{{ $contract->status_display ?? 'مغلق' }}</span>
              </td>
              <td>{{ $contract->created_at ? $contract->created_at->format('Y-m-d') : '' }}</td>
              <td>
                <a href="{{ route('contracts.show', $contract->id) }}" class="modern-action-btn">عرض</a>
                @if(auth()->user()->isManager())
                  <a href="{{ route('contracts.archive', $contract->id) }}" class="modern-action-btn">أرشفة</a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="text-align:center;padding:20px;color:var(--muted)">
                لا توجد عقود مغلقة حالياً
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script src="{{ asset('static/js/pdf_viewer.js') }}"></script>
@endpush
@endsection