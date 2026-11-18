@extends('layout')
@section('title', 'Reports')
@section('content')
<header class="report-print-header" aria-label="Lost and Found report">
  <span class="report-print-logo" aria-hidden="true">
    <svg viewBox="0 0 32 32" fill="none"><circle cx="13.5" cy="13.5" r="9" stroke="currentColor" stroke-width="2.4"/><path d="m20.2 20.2 7.1 7.1" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/><path d="M13.5 9.5v8M9.5 13.5h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
  </span>
  <div><strong>DepFind · {{ \App\Models\DepartmentSetting::value('department_name', config('depfind.department')) }}</strong><h1>Department Lost and Found</h1><p>Department items and claims report</p></div>
</header>
<div class="bar"><h1>Reports</h1>
  <div class="noprint"><button class="btn alt" onclick="window.print()">Print</button>
  <a class="btn alt" href="{{ route('reports.export', ['what' => 'items'] + request()->query()) }}">Items CSV</a>
  <a class="btn alt" href="{{ route('reports.export', ['what' => 'claims']) }}">Claims CSV</a>
  @if(auth()->user()->role === 'admin')<a class="btn" href="{{ route('backup') }}">Download database backup</a>@endif</div></div>
<form class="filters noprint" method="GET">
  <input type="date" name="from" value="{{ $from }}" aria-label="From date"><input type="date" name="to" value="{{ $to }}" aria-label="To date"><button class="btn alt">Apply dates</button>
</form>
<p class="muted">Lost and found report{{ $from ? ' from ' . $from : '' }}{{ $to ? ' to ' . $to : '' }}. Generated {{ now()->format('F d, Y g:i A') }}.</p>
<div class="grid">
  <div class="stat"><b>{{ $byType['found'] ?? 0 }}</b><span>Found items</span></div>
  <div class="stat"><b>{{ $byType['lost'] ?? 0 }}</b><span>Lost reports</span></div>
  <div class="stat"><b>{{ $byStatus['returned'] ?? 0 }}</b><span>Returned to owners</span></div>
  <div class="stat"><b>{{ ($byType['found'] ?? 0) ? round((($byStatus['returned'] ?? 0) / $byType['found']) * 100) : 0 }}%</b><span>Return rate</span></div>
</div>
<div class="row">
  <div class="card"><h2>Claims</h2>@foreach(['pending','approved','rejected'] as $s)<p>{{ ucfirst($s) }}: <b>{{ $claimStats[$s] ?? 0 }}</b></p>@endforeach</div>
  <div class="card"><h2>By category</h2>@forelse($byCategory as $name => $n)<p>{{ $name }}: <b>{{ $n }}</b></p>@empty<p class="muted">No items in this period.</p>@endforelse</div>
</div>
<div class="table-wrap"><table>
  <tr><th>Date</th><th>Item</th><th>Type</th><th>Category</th><th>Location</th><th>Status</th></tr>
  @forelse($items as $i)<tr><td>{{ $i->date_event->format('M d, Y') }}</td><td>{{ $i->name }}</td><td>{{ ucfirst($i->type) }}</td><td>{{ $i->category->name }}</td><td>{{ $i->location }}</td><td>{{ ucfirst($i->status) }}</td></tr>
  @empty<tr><td colspan="6" class="muted">No items in this period.</td></tr>@endforelse
</table></div>
@endsection
