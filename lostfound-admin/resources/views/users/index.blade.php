@extends('layout')
@section('title', 'Users')
@section('content')
<h1>Users</h1>
<p class="muted">Students register in the mobile app. Only Administrators can manage accounts and create staff accounts with assigned roles.</p>
<form class="filters" method="GET"><input name="q" value="{{ request('q') }}" placeholder="Search name, email or student ID"><button class="btn alt">Search</button></form>
<table>
  <tr><th>Name</th><th>Email</th><th>Role</th><th>Claims</th><th>Account</th><th>Reset password</th><th>Delete account</th></tr>
  @foreach($users as $u)
  <tr>
    <td>{{ $u->name }} <span class="muted">{{ $u->student_id }}</span></td><td>{{ $u->email }}</td><td>{{ ['admin' => 'Administrator', 'officer' => 'Lost and Found Officer', 'security' => 'Security Personnel', 'registrar' => 'Registrar', 'it_admin' => 'IT Administrator', 'user' => 'Student'][$u->role] ?? ucfirst($u->role) }}</td><td>{{ $u->claims_count }}</td>
    <td>@if($u->id !== auth()->id())<form method="POST" action="{{ route('users.toggle', $u) }}">@csrf @method('PATCH')<button class="btn sm {{ $u->is_active ? 'alt' : '' }}">{{ $u->is_active ? 'Deactivate' : 'Activate' }}</button></form>@else <span class="muted">Current account</span>@endif</td>
    <td>@if($u->id !== auth()->id())<form method="POST" action="{{ route('users.password', $u) }}" style="display:flex;gap:.4rem">@csrf @method('PATCH')<input type="password" name="password" placeholder="New password" minlength="8" required aria-label="New password for {{ $u->name }}"><button class="btn sm alt">Reset</button></form>@else <span class="muted">Use profile</span>@endif</td>
    <td>@if($u->id !== auth()->id())<form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Delete this account? Sign-in access and personal details will be removed. Linked item and claim records will remain anonymized. This cannot be undone.')">@csrf @method('DELETE')<button class="btn sm red">Delete</button></form>@else <span class="muted">Current account</span>@endif</td>
  </tr>
  @endforeach
</table>
@include('partials.pager', ['p' => $users])
<form class="card" method="POST" action="{{ route('users.store') }}">
  @csrf <h2>Create staff account</h2>
  <div class="row"><div><label for="n">Name</label><input id="n" name="name" required value="{{ old('name') }}"></div><div><label for="e">Email</label><input id="e" type="email" name="email" required value="{{ old('email') }}"></div></div>
  <div><label for="role">Staff role</label><select id="role" name="role" required><option value="">Choose a role</option><option value="officer" @selected(old('role') === 'officer')>Lost and Found Officer</option><option value="security" @selected(old('role') === 'security')>Security Personnel</option><option value="registrar" @selected(old('role') === 'registrar')>Registrar</option><option value="it_admin" @selected(old('role') === 'it_admin')>IT Administrator</option><option value="admin" @selected(old('role') === 'admin')>Administrator</option></select><small class="muted">Students create their own accounts in the mobile app.</small></div>
  <div class="row"><div><label for="p">Password (8+ characters, letters and numbers)</label><input id="p" type="password" name="password" required></div><div><label for="p2">Confirm password</label><input id="p2" type="password" name="password_confirmation" required></div></div>
  <p><button class="btn">Create staff account</button></p>
</form>
@endsection
