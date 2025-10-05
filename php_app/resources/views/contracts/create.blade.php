@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Create Contract</h1>
  <form method="POST" action="{{ route('contracts.store') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label">Partner Name</label>
      <input type="text" name="partner2_name" class="form-control" required>
    </div>
    <button class="btn btn-primary">Create</button>
  </form>
</div>
@endsection
