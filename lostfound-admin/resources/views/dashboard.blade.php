@extends('layout')
@section('title', 'Dashboard')
@section('content')
<h1>Dashboard</h1>
<div class="grid">
@foreach($stats as $label => $n)
  <div class="stat {{ in_array($label, ['Lost reports to review','Claims to review']) && $n ? 'hot' : '' }}"><b>{{ $n }}</b><span>{{ $label }}</span></div>
@endforeach
</div>
@if(in_array(auth()->user()->role, ['admin', 'officer'], true))
<div class="row">
  <div class="card">
    <h2>Claims waiting for a decision</h2>
    @forelse($pendingClaims as $c)
      <p><a href="{{ route('claims.show', $c) }}">{{ $c->item->name }}</a> <span class="muted">claimed by {{ $c->user->name }}, {{ $c->created_at->diffForHumans() }}</span></p>
    @empty <p class="muted">No pending claims.</p> @endforelse
  </div>
  <div class="card">
    <h2>Lost reports waiting for review</h2>
    @forelse($pendingLost as $i)
      <p><a href="{{ route('items.show', $i) }}">{{ $i->name }}</a> <span class="muted">reported by {{ $i->reporter?->name ?? 'unknown' }}, {{ $i->created_at->diffForHumans() }}</span></p>
    @empty <p class="muted">No pending reports.</p> @endforelse
  </div>
</div>
@endif
@if(in_array(auth()->user()->role, ['admin', 'officer'], true))
<div class="card">
  <h2>Items by status</h2>
  @foreach(\App\Models\Item::STATUSES as $s)
    <div class="bar-row"><span>{{ ucfirst($s) }}</span><i style="width:{{ ($byStatus[$s] ?? 0) / $maxStatus * 100 }}%"></i><b>{{ $byStatus[$s] ?? 0 }}</b></div>
  @endforeach
</div>
@endif
@endsection
