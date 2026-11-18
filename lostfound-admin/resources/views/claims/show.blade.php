@extends('layout')
@section('title', 'Claim #' . $claim->id)
@section('content')
<div class="bar"><h1>Claim on {{ $claim->item->name }}</h1><span class="pill s-{{ $claim->status }}">{{ ucfirst($claim->status) }}</span></div>
<div class="row">
  <div class="card"><h2>Claimant</h2>
    <dl class="dl"><dt>Name</dt><dd>{{ $claim->user->name }}</dd><dt>Student / employee ID</dt><dd>{{ $claim->user->student_id ?: 'Not provided' }}</dd>
    <dt>Email</dt><dd>{{ $claim->user->email }}</dd><dt>Phone</dt><dd>{{ $claim->user->phone ?: 'Not provided' }}</dd>
    <dt>Submitted</dt><dd>{{ $claim->created_at->format('F d, Y g:i A') }}</dd></dl></div>
  <div class="card"><h2>Item</h2>
    <dl class="dl"><dt>Item</dt><dd><a href="{{ route('items.show', $claim->item) }}">{{ $claim->item->name }}</a></dd><dt>Category</dt><dd>{{ $claim->item->category->name }}</dd>
    <dt>Found in department</dt><dd>{{ $claim->item->location }}</dd><dt>Pickup location</dt><dd>{{ \App\Models\DepartmentSetting::value('pickup_location', config('depfind.pickup_location')) }}</dd><dt>Item status</dt><dd><span class="pill s-{{ $claim->item->status }}">{{ ucfirst($claim->item->status) }}</span></dd></dl></div>
</div>
<div class="card"><h2>Proof of ownership</h2><p>{{ $claim->proof_of_ownership }}</p>
  @if($claim->identifying_details)<h2>Identifying details</h2><p>{{ $claim->identifying_details }}</p>@endif</div>

@if($claim->status === 'pending' && in_array(auth()->user()->role, ['admin', 'staff'], true))
<form class="card" method="POST" action="{{ route('claims.decide', $claim) }}">
  @csrf @method('PATCH')
  <h2>Your decision</h2>
  <label for="admin_remarks">Remarks for the user (required when rejecting)</label>
  <textarea id="admin_remarks" name="admin_remarks" maxlength="1000">{{ old('admin_remarks') }}</textarea>
  @error('admin_remarks')<div class="err-text">{{ $message }}</div>@enderror
  @error('decision')<div class="err-text">{{ $message }}</div>@enderror
  <p><button class="btn" name="decision" value="approve">Approve claim</button> <button class="btn red" name="decision" value="reject">Reject claim</button></p>
</form>
@elseif($claim->status !== 'pending')
<div class="card"><h2>Decision</h2>
  <p>{{ ucfirst($claim->status) }} by {{ $claim->reviewer?->name ?? 'an admin' }} on {{ $claim->reviewed_at?->format('F d, Y g:i A') }}.</p>
  @if($claim->admin_remarks)<p>Remarks: {{ $claim->admin_remarks }}</p>@endif
  @if($claim->status === 'approved' && $claim->item->status === 'claimed' && in_array(auth()->user()->role, ['admin', 'staff'], true))
    <form method="POST" action="{{ route('items.status', $claim->item) }}">@csrf @method('PATCH')
      <input type="hidden" name="status" value="returned"><input type="hidden" name="admin_note" value="Returned to the owner.">
      <button class="btn">Mark item as returned to owner</button></form>
  @endif
</div>
@else
<div class="card"><h2>Awaiting staff review</h2><p>Department staff will record the decision and coordinate pickup at {{ \App\Models\DepartmentSetting::value('pickup_location', config('depfind.pickup_location')) }}.</p></div>
@endif
@endsection
