@extends('layout')
@section('title', $item->name)
@section('content')
<div class="bar"><h1>{{ $item->name }}</h1>
  @if(in_array(auth()->user()->role, ['admin', 'staff'], true))
  <div><a class="btn alt" href="{{ route('items.edit', $item) }}">Edit</a>
  @if(auth()->user()->role === 'admin')<form style="display:inline" method="POST" action="{{ route('items.destroy', $item) }}" onsubmit="return confirm('Delete this item and its claims?')">@csrf @method('DELETE')<button class="btn red">Delete</button></form>@endif</div>@endif</div>
<div class="row">
  <div class="card">
    <dl class="dl">
      <dt>Type</dt><dd><span class="pill t-{{ $item->type }}">{{ ucfirst($item->type) }}</span></dd>
      <dt>Status</dt><dd><span class="pill s-{{ $item->status }}">{{ ucfirst($item->status) }}</span></dd>
      <dt>Category</dt><dd>{{ $item->category->name }}</dd>
      <dt>Department location</dt><dd>{{ $item->location }}</dd><dt>Pickup location</dt><dd>{{ \App\Models\DepartmentSetting::value('pickup_location', config('depfind.pickup_location')) }}</dd>
      <dt>Date</dt><dd>{{ $item->date_event->format('F d, Y') }}</dd>
      <dt>Reported by</dt><dd>{{ $item->reporter?->name ?? 'Unknown' }} @if($item->reporter?->student_id)<span class="muted">({{ $item->reporter->student_id }})</span>@endif</dd>
      <dt>Description</dt><dd>{{ $item->description }}</dd>
      @if($item->admin_note)<dt>Note to user</dt><dd>{{ $item->admin_note }}</dd>@endif
    </dl>
  </div>
  <div class="card">
    @if($item->photo_url)<img class="photo" src="{{ $item->photo_url }}" alt="Photo of {{ $item->name }}">@else<p class="muted">No photo.</p>@endif
  </div>
</div>
@if(in_array(auth()->user()->role, ['admin', 'staff'], true))
<form class="card" method="POST" action="{{ route('items.status', $item) }}">
  @csrf @method('PATCH')
  <h2>Process this {{ $item->type }} item</h2>
  <div class="row">
    <div><label for="status">Set status</label><select id="status" name="status">@foreach(\App\Models\Item::STATUSES as $s)<option value="{{ $s }}" @selected($item->status==$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div><label for="admin_note">Note shown to the user (required when rejecting)</label><input id="admin_note" name="admin_note" value="{{ old('admin_note', $item->admin_note) }}" maxlength="1000"></div>
  </div>
  @error('admin_note')<div class="err-text">{{ $message }}</div>@enderror
  <p class="muted">Listed items appear in the app. Returned and rejected items stay in the records.</p>
  <button class="btn">Update status</button>
</form>
@endif
<div class="card"><h2>Claims on this item</h2>
  @forelse($item->claims as $c)
    <p><a href="{{ route('claims.show', $c) }}">{{ $c->user->name }}</a> <span class="pill s-{{ $c->status }}">{{ ucfirst($c->status) }}</span> <span class="muted">{{ $c->created_at->diffForHumans() }}</span></p>
  @empty <p class="muted">No claims yet.</p> @endforelse
</div>
@endsection
