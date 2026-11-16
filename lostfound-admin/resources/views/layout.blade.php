<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'Admin') | Lost and Found</title>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
@auth
<div class="shell">
  <nav class="side">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M16 3.5 27 8v7.1c0 6.7-4.4 11.2-11 13.4C10.4 26.3 6 21.8 6 15.1V8l10-4.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M12.2 16.1h7.6M16 12.3v7.6" stroke="#235347" stroke-width="2.2" stroke-linecap="round"/></svg></span>
      <span>Lost and Found<small>Admin desk &middot; TEAM BANGAN</small></span>
    </div>
    @php $role = auth()->user()->role; $pending = in_array($role, ['admin', 'officer', 'registrar'], true) ? \App\Models\Claim::where('status','pending')->count() : 0; @endphp
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'on' : '' }}">Dashboard</a>
    @if(in_array($role, ['admin', 'officer', 'security'], true))<a href="{{ route('items.index') }}" class="{{ request()->routeIs('items.*') ? 'on' : '' }}">Items</a>@endif
    @if(in_array($role, ['admin', 'officer', 'registrar'], true))<a href="{{ route('claims.index') }}" class="{{ request()->routeIs('claims.*') ? 'on' : '' }}">Claims @if($pending)<span class="bubble">{{ $pending }}</span>@endif</a>@endif
    @if($role === 'admin')<a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'on' : '' }}">Categories</a><a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'on' : '' }}">Users</a>@endif
    @if(in_array($role, ['admin', 'officer'], true))<a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'on' : '' }}">Reports</a>@endif
    @if(in_array($role, ['admin', 'it_admin'], true))<a href="{{ route('backup') }}">Backup</a><a href="{{ route('activity.index') }}" class="{{ request()->routeIs('activity.*') ? 'on' : '' }}">Activity log</a>@endif
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn alt sm" style="width:100%">Sign out ({{ auth()->user()->name }})</button></form>
  </nav>
  <main>
@else
  <main class="login">
@endauth
    @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif
    @if($errors->any() && auth()->check())<div class="flash err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    @yield('content')
  </main>
@auth</div>@endauth
</body>
</html>
