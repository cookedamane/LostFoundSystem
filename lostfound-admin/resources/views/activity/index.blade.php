@extends('layout')
@section('title', 'Activity log')
@section('content')
<div class="bar"><div><p class="eyebrow">AUDIT TRAIL</p><h1>Activity log</h1><p class="muted">Recent staff actions, account changes, claim decisions, and data exports.</p></div></div>
<div class="table-wrap"><table>
  <tr><th>When</th><th>Staff account</th><th>Action</th><th>Details</th><th>Source IP</th></tr>
  @forelse($events as $event)
  <tr><td>{{ \Illuminate\Support\Carbon::parse($event->created_at)->format('M d, Y g:i A') }}</td><td>{{ $event->user_name ?? 'Deleted account' }}<br><span class="muted">{{ $event->user_email }}</span></td><td>{{ str_replace('_', ' ', $event->action) }}</td><td>{{ $event->details }}</td><td>{{ $event->ip_address }}</td></tr>
  @empty<tr><td colspan="5" class="muted">No activity has been recorded.</td></tr>@endforelse
</table></div>
@include('partials.pager', ['p' => $events])
@endsection
