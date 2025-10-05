@extends('layouts.app')

@section('title', ' - العقود')

@section('content')
<div class="contracts-page">
    <div class="page-header">
        <h1>العقود</h1>
        <a href="{{ route('contracts.create') }}" class="btn btn-primary">إنشاء عقد جديد</a>
    </div>
    
    <div class="contracts-grid">
        @forelse($contracts ?? [] as $contract)
            <div class="contract-card">
                <div class="contract-header">
                    <h3>{{ $contract['title'] ?? 'عقد مشاركة' }}</h3>
                    <span class="contract-status status-{{ $contract['status'] ?? 'pending' }}">
                        {{ $contract['status_display'] ?? 'بانتظار الاعتماد' }}
                    </span>
                </div>
                <div class="contract-details">
                    <p>العميل: {{ $contract['client_name'] ?? '---' }}</p>
                    <p>التاريخ: {{ $contract['created_at'] ?? '---' }}</p>
                    @if(!empty($contract['investment_amount']))
                        <p>المبلغ: {{ number_format($contract['investment_amount']) }} ريال</p>
                    @endif
                </div>
                <div class="contract-actions">
                    <a href="{{ route('contracts.show', $contract['id']) }}" class="btn btn-sm">عرض</a>
                    <a href="{{ route('contracts.pdf', $contract['id']) }}" class="btn btn-sm btn-secondary">PDF</a>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <p>لا توجد عقود بعد</p>
                <a href="{{ route('contracts.create') }}" class="btn btn-primary">إنشاء أول عقد</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
