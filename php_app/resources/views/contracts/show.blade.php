@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Contract #{{ $id }}</h1>
  <p>Contract details...</p>
  <a href="{{ route('contracts.pdf', ['id' => $id]) }}" class="btn btn-primary">Download PDF</a>
</div>
@endsection
