@extends('layout')
@section('title', 'Categories')
@section('content')
<div class="bar"><div><p class="eyebrow">ITEM CLASSIFICATION</p><h1>Categories</h1><p class="muted">Keep item categories clear and consistent for staff and app users.</p></div></div>
<form class="card" method="POST" action="{{ route('categories.store') }}">
  @csrf <h2>Add category</h2>
  <div class="row"><div><label for="name">Name</label><input id="name" name="name" required maxlength="80" value="{{ old('name') }}"></div>
  <div><label for="description">Description</label><input id="description" name="description" maxlength="255" value="{{ old('description') }}"></div></div>
  @error('name')<div class="err-text">{{ $message }}</div>@enderror
  <p><button class="btn">Add category</button></p>
</form>
<div class="table-wrap"><table>
  <tr><th>Name</th><th>Description</th><th>Items</th><th></th></tr>
  @foreach($categories as $c)
  <tr>
    <td><form id="c{{ $c->id }}" method="POST" action="{{ route('categories.update', $c) }}">@csrf @method('PUT')</form><input form="c{{ $c->id }}" name="name" value="{{ $c->name }}" aria-label="Category name"></td>
    <td><input form="c{{ $c->id }}" name="description" value="{{ $c->description }}" aria-label="Description"></td>
    <td>{{ $c->items_count }}</td>
    <td style="white-space:nowrap"><button form="c{{ $c->id }}" class="btn sm">Save</button>
      <form style="display:inline" method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="btn sm red">Delete</button></form></td>
  </tr>
  @endforeach
</table></div>
@endsection
