@extends('layout')
@section('title', 'Items')
@section('content')
<div class="bar"><div><p class="eyebrow">{{ \App\Models\DepartmentSetting::value('department_name', config('depfind.department')) }}</p><h1>Department items</h1><p class="muted">Only items from this department’s configured locations belong here.</p></div><a class="btn" href="{{ route('items.create') }}">Register found item</a></div>
<form class="filters" method="GET">
  <input name="q" value="{{ request('q') }}" placeholder="Search name or location">
  <select name="type"><option value="">All types</option>@foreach(['found','lost'] as $t)<option value="{{ $t }}" @selected(request('type')==$t)>{{ ucfirst($t) }}</option>@endforeach</select>
  <select name="status"><option value="">All statuses</option>@foreach(\App\Models\Item::STATUSES as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <select name="category_id"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
  <button class="btn alt">Filter</button>
</form>
<table>
  <tr><th></th><th>Item</th><th>Type</th><th>Category</th><th>Location</th><th>Date</th><th>Status</th></tr>
  @forelse($items as $i)
  <tr>
    <td>@if($i->photo_url)<img class="thumb" src="{{ $i->photo_url }}" alt="">@else<span class="thumb"></span>@endif</td>
    <td>@if(in_array(auth()->user()->role, ['admin', 'staff'], true))<a href="{{ route('items.show', $i) }}">{{ $i->name }}</a>@else{{ $i->name }}@endif</td>
    <td><span class="pill t-{{ $i->type }}">{{ ucfirst($i->type) }}</span></td>
    <td>{{ $i->category->name }}</td><td>{{ $i->location }}</td><td>{{ $i->date_event->format('M d, Y') }}</td>
    <td><span class="pill s-{{ $i->status }}">{{ ucfirst($i->status) }}</span></td>
  </tr>
  @empty <tr><td colspan="7" class="muted">No items match. Register a found item to get started.</td></tr> @endforelse
</table>
@include('partials.pager', ['p' => $items])
@if(in_array(auth()->user()->role, ['admin', 'staff'], true))
<form class="card noprint" method="POST" action="{{ route('items.import') }}" enctype="multipart/form-data">
  @csrf
  <h2>Import found items from CSV</h2>
  <p class="muted">Columns: name, category, location, date, description. Location must exactly match one configured department location. Unknown categories are created.</p>
  <input type="file" name="csv" accept=".csv,.txt" required>
  @error('csv')<div class="err-text">{{ $message }}</div>@enderror
  <p><button class="btn alt">Import</button></p>
</form>
@endif
@endsection
