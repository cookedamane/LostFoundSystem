@extends('layout')
@section('title', 'Dashboard')
@section('content')
<div class="bar"><div><p class="eyebrow">{{ \App\Models\DepartmentSetting::value('department_name', config('depfind.department')) }}</p><h1>Department overview</h1><p class="muted">Manage items found and lost inside this department. Pickup is coordinated at {{ \App\Models\DepartmentSetting::value('pickup_location', config('depfind.pickup_location')) }}.</p></div><div class="bar-actions"><span class="pill">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Staff' }}</span>@if(($stats['Found items on display'] ?? 0) === 0)<a class="btn" href="{{ route('items.create') }}">Register a found item</a>@endif</div></div>
<div class="grid">
@foreach($stats as $label => $n)
  <div class="stat {{ in_array($label, ['Lost reports to review','Claims to review']) && $n ? 'hot' : '' }}"><b>{{ $n }}</b><span>{{ $label }}</span></div>
@endforeach
</div>
@if(in_array(auth()->user()->role, ['admin', 'staff'], true))
<div class="row">
  <div class="card">
    <div class="bar"><h2>Claims waiting for a decision</h2><a class="btn alt sm" href="{{ route('claims.index', ['status' => 'pending']) }}">Open claims</a></div>
    @forelse($pendingClaims as $c)
      <p><a href="{{ route('claims.show', $c) }}">{{ $c->item->name }}</a> <span class="muted">claimed by {{ $c->user->name }}, {{ $c->created_at->diffForHumans() }}</span></p>
    @empty
      @if(($stats['Found items on display'] ?? 0) === 0)
        <p class="muted">There are no found items listed for users to claim yet. Register a found item at a configured department location; app claims will appear here automatically.</p>
      @else
        <p class="muted">No claims are waiting. When a user claims a listed found item in the app, it will appear here.</p>
      @endif
    @endforelse
  </div>
  <div class="card">
    <div class="bar"><h2>Lost reports waiting for review</h2><a class="btn alt sm" href="{{ route('items.index', ['type' => 'lost', 'status' => 'pending']) }}">Open reports</a></div>
    @forelse($pendingLost as $i)
      <p><a href="{{ route('items.show', $i) }}">{{ $i->name }}</a> <span class="muted">reported by {{ $i->reporter?->name ?? 'unknown' }}, {{ $i->created_at->diffForHumans() }}</span></p>
    @empty <p class="muted">No pending lost reports at the configured department locations. New app reports submitted for those locations appear here.</p> @endforelse
  </div>
</div>
@endif
@if(in_array(auth()->user()->role, ['admin', 'staff'], true))
<div class="card">
  <h2>Items by status</h2>
  @foreach(\App\Models\Item::STATUSES as $s)
    <div class="bar-row"><span>{{ ucfirst($s) }}</span><i style="width:{{ ($byStatus[$s] ?? 0) / $maxStatus * 100 }}%"></i><b>{{ $byStatus[$s] ?? 0 }}</b></div>
  @endforeach
</div>
@endif
@endsection
