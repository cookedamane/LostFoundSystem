@extends('layout')
@section('title', 'Claims')
@section('content')
<div class="bar"><div><h1>Claims</h1><p class="muted">{{ $claims->total() }} claim(s) · New app submissions appear automatically.</p></div><a class="btn alt" href="{{ route('claims.index', request()->query()) }}">Refresh now</a></div>
<form class="filters" method="GET">
  <select name="status"><option value="">All claims</option>@foreach(['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <button class="btn alt">Filter</button>
</form>
<table>
  <tr><th>Item</th><th>Claimant</th><th>Submitted</th><th>Status</th><th></th></tr>
  @forelse($claims as $c)
  <tr><td>{{ $c->item->name }}</td><td>{{ $c->user->name }}</td><td>{{ $c->created_at->format('M d, Y g:i A') }}</td>
    <td><span class="pill s-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
    <td><a class="btn sm {{ $c->status === 'pending' && in_array(auth()->user()->role, ['admin', 'staff'], true) ? '' : 'alt' }}" href="{{ route('claims.show', $c) }}">{{ $c->status === 'pending' && in_array(auth()->user()->role, ['admin', 'staff'], true) ? 'Review' : 'View' }}</a></td></tr>
  @empty <tr><td colspan="5" class="muted">No claims match this view. @if(!$hasClaimableFoundItems) Register a found item at a configured department location; users can then claim it in the app and the request will appear here. @else New app claims will appear here automatically when submitted. @endif</td></tr> @endforelse
</table>
@include('partials.pager', ['p' => $claims])
<script>
  // Keep the staff queue current when a student submits a claim from the app.
  window.setTimeout(() => window.location.reload(), 15000);
</script>
@endsection
