@extends('layout')
@section('title', 'Department settings')
@section('content')
<div class="bar"><div><p class="eyebrow">ADMINISTRATION</p><h1>Department settings</h1><p class="muted">Define this DepFind service boundary. Users can only report and browse items at the locations listed here.</p></div><span class="pill">Administrator only</span></div>
<form class="card settings-form" method="POST" action="{{ route('settings.update') }}">
  @csrf @method('PUT')
  <label for="department_name">Department name</label><input id="department_name" name="department_name" value="{{ old('department_name', $department) }}" maxlength="120" required>
  <label for="locations">Buildings and areas served <span class="muted">(one per line)</span></label>
  <textarea id="locations" name="locations" rows="7" maxlength="3000" required>{{ old('locations', $locations) }}</textarea>
  <p class="muted">Use only buildings and areas assigned to this department. These choices are shared by the staff portal and mobile app. Active reports or claims must be resolved before their location can be removed; historical records are never deleted by changing these settings.</p>
  @error('locations')<div class="err-text">{{ $message }}</div>@enderror
  <label for="pickup_location">Designated item collection / pickup location</label><input id="pickup_location" name="pickup_location" value="{{ old('pickup_location', $pickup) }}" maxlength="150" required>
  <p class="muted">DepFind records and coordinates returns. Staff and claimants arrange pickup at this location; the system does not transport items.</p>
  <button class="btn">Save department settings</button>
</form>
@endsection
