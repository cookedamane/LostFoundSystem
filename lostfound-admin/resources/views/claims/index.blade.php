@extends('layout')
@section('title', 'Claims')
@section('content')
<h1>Claims</h1>
<form class="filters" method="GET">
  <select name="status"><option value="">All claims</option>@foreach(['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <button class="btn alt">Filter</button>
</form>
<table>
  <tr><th>Item</th><th>Claimant</th><th>Submitted</th><th>Status</th><th></th></tr>
  @forelse($claims as $c)
  <tr><td>{{ $c->item->name }}</td><td>{{ $c->user->name }}</td><td>{{ $c->created_at->format('M d, Y g:i A') }}</td>
    <td><span class="pill s-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
    <td><a class="btn sm {{ $c->status === 'pending' && in_array(auth()->user()->role, ['admin', 'officer'], true) ? '' : 'alt' }}" href="{{ route('claims.show', $c) }}">{{ $c->status === 'pending' && in_array(auth()->user()->role, ['admin', 'officer'], true) ? 'Review' : 'View' }}</a></td></tr>
  @empty <tr><td colspan="5" class="muted">No claims yet. They appear here when users claim a found item in the app.</td></tr> @endforelse
</table>
@include('partials.pager', ['p' => $claims])
@endsection
