@extends('layout')
@section('title', 'Sign in')
@section('content')
<div class="brand login-brand">
  <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><circle cx="13.5" cy="13.5" r="9" stroke="currentColor" stroke-width="2.4"/><path d="m20.2 20.2 7.1 7.1" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/><path d="M13.5 9.5v8M9.5 13.5h8" stroke="#235347" stroke-width="1.8" stroke-linecap="round"/></svg></span>
  <span>DepFind<small>{{ \App\Models\DepartmentSetting::value('department_name', config('depfind.department')) }} &middot; STAFF PORTAL</small></span>
</div>
<p class="muted">Department-only lost and found. Admins and staff sign in here; students and employees use the app.</p>
<form method="POST" action="{{ url('/login') }}" class="card">
  @csrf
  <label for="email">Email</label>
  <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
  <label for="password">Password</label>
  <input id="password" type="password" name="password" required>
  @error('email')<div class="err-text">{{ $message }}</div>@enderror
  @error('password')<div class="err-text">{{ $message }}</div>@enderror
  <p><button class="btn">Sign in</button></p>
</form>
@endsection
