@extends('layouts.app')
@section('title', 'الملف الشخصي')

@push('styles')
<link rel="stylesheet" href="{{ asset('static/css/dashboard_custom.css') }}">
@endpush

@section('content')
<div class="dashboard-main">
  <button onclick="window.history.back()" class="btn-back" title="رجوع">&#8594;</button>
  
  <div class="dashboard-title">الملف الشخصي</div>
  
  @if (session('success'))
    <div class="alert alert-success">
      {{ session('success') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('PUT')
    
    <div class="mb-3">
      <label>الاسم</label>
      <input type="text" name="name" value="{{ auth()->user()->name }}" class="form-control" required>
    </div>
    
    <div class="mb-3">
      <label>البريد الإلكتروني</label>
      <input type="email" name="email" value="{{ auth()->user()->email }}" class="form-control" required>
    </div>
    
    <div class="mb-3">
      <label>كلمة المرور الجديدة (اختياري)</label>
      <input type="password" name="password" class="form-control" placeholder="اتركه فارغًا إذا لم ترد تغييرها">
    </div>
    
    <div class="mb-3">
      <label>تأكيد كلمة المرور</label>
      <input type="password" name="password_confirmation" class="form-control">
    </div>
    
    <div class="mb-3">
      <label>الدور</label>
      <input type="text" value="{{ auth()->user()->role == 'manager' ? 'مدير' : 'موظف' }}" class="form-control" readonly>
    </div>
    
    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
  </form>
</div>
@endsection