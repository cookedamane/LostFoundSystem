@extends('layout')
@section('title', $item->exists ? 'Edit item' : 'Register found item')
@section('content')
<h1>{{ $item->exists ? 'Edit ' . $item->name : 'Register found item' }}</h1>
<form class="card" method="POST" enctype="multipart/form-data" action="{{ $item->exists ? route('items.update', $item) : route('items.store') }}">
  @csrf @if($item->exists) @method('PUT') @endif
  <label for="name">Item name</label><input id="name" name="name" value="{{ old('name', $item->name) }}" required maxlength="150">
  <div class="row">
    <div><label for="category_id">Category</label>
      <select id="category_id" name="category_id" required>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $item->category_id)==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div><label for="date_event">Date {{ $item->type === 'lost' ? 'lost' : 'found' }}</label><input id="date_event" type="date" name="date_event" max="{{ now()->toDateString() }}" value="{{ old('date_event', optional($item->date_event)->toDateString()) }}" required></div>
  </div>
  <label for="location">Department building / area</label><select id="location" name="location" required><option value="">Choose a department location</option>@foreach($locations as $location)<option value="{{ $location }}" @selected(old('location', $item->location)===$location)>{{ $location }}</option>@endforeach</select><small class="muted">This list comes from the current department settings. If those settings were just changed, reopen this form. Pickup is coordinated at {{ \App\Models\DepartmentSetting::value('pickup_location', config('depfind.pickup_location')) }}.</small>
  <label for="description">Description</label><textarea id="description" name="description" required maxlength="2000">{{ old('description', $item->description) }}</textarea>
  <label for="photo">Photo (JPG, PNG or WEBP, up to 2 MB)</label><input id="photo" type="file" name="photo" accept="image/*">
  @if($item->exists)
    <div class="row">
      <div><label for="status">Status</label><select id="status" name="status">@foreach(\App\Models\Item::STATUSES as $s)<option value="{{ $s }}" @selected(old('status', $item->status)==$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
      <div><label for="admin_note">Note shown to the user</label><input id="admin_note" name="admin_note" value="{{ old('admin_note', $item->admin_note) }}" maxlength="1000"></div>
    </div>
  @endif
  @foreach($errors->all() as $e)<div class="err-text">{{ $e }}</div>@endforeach
  <p><button class="btn">{{ $item->exists ? 'Save changes' : 'Register item' }}</button> <a class="btn alt" href="{{ $item->exists ? route('items.show', $item) : route('items.index') }}">Cancel</a></p>
</form>
@endsection
