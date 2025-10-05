@extends('layouts.app')
@section('title', 'العقود تحت الإجراء')

@push('styles')
<link rel="stylesheet" href="{{ asset('static/css/contracts_modern.css') }}">
@endpush

@section('content')
<div class="modern-header">
  <a href="javascript:history.back()" class="btn btn-outline">← العودة</a>
  <h2>العقود تحت الإجراء</h2>
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
      @forelse($contracts_in_progress as $contract)
        <tr>
          <td>
            <a href="{{ route('contracts.pdf', $contract->id) }}" class="modern-pdf-btn">PDF</a>
          </td>
          <td>{{ $contract->serial ?? '#' . $contract->id }}</td>
          <td>{{ $contract->client_name ?? 'غير محدد' }}</td>
          <td>
            <span class="modern-status modern-status-progress">{{ $contract->status_display ?? 'قيد التنفيذ' }}</span>
          </td>
          <td>{{ $contract->created_at ? $contract->created_at->format('Y-m-d') : '' }}</td>
          <td>
            <a href="{{ route('contracts.show', $contract->id) }}" class="modern-action-btn">عرض</a>
            @if(auth()->user()->isManager())
              <a href="{{ route('contracts.edit', $contract->id) }}" class="modern-action-btn">تعديل</a>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:20px;color:var(--muted)">
            لا توجد عقود تحت الإجراء حالياً
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

@push('scripts')
<script src="{{ asset('static/js/pdf_viewer.js') }}"></script>
@endpush
@endsection