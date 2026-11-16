@extends('layout')
@section('title', 'Sign in')
@section('content')
<div class="brand login-brand">
  <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M16 3.5 27 8v7.1c0 6.7-4.4 11.2-11 13.4C10.4 26.3 6 21.8 6 15.1V8l10-4.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M12.2 16.1h7.6M16 12.3v7.6" stroke="#235347" stroke-width="2.2" stroke-linecap="round"/></svg></span>
  <span>Lost and Found admin<small>TEAM BANGAN &middot; STAFF PORTAL</small></span>
</div>
<p class="muted">Staff sign in here. Students and other users sign in on the mobile app.</p>
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
