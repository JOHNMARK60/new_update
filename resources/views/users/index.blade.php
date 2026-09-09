@extends('layouts.app')

@section('heading', 'User accounts')
@section('subtitle', 'Manage administrator and cashier access.')

@section('header-search')
    <form class="header-search" method="get" action="{{ route('users.index') }}"><input name="q" value="{{ request('q') }}" placeholder="Search name or email…" aria-label="Search users"></form>
@endsection

@section('actions')
    <a class="btn primary" href="{{ route('users.create') }}">Create account</a>
@endsection

@section('content')
    <section class="card table-wrap"><div class="module-card-header"><div><h2>System users</h2><p>{{ $users->total() }} account(s) found.</p></div>@if(request('q'))<a href="{{ route('users.index') }}">Clear search</a>@endif</div><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody>@forelse($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td><span class="badge">{{ ucfirst($user->role) }}</span></td><td>{{ ucfirst($user->status) }}</td><td>{{ $user->created_at?->format('M j, Y') }}</td><td><div class="row"><a class="btn small" href="{{ route('users.edit', $user) }}">Edit</a>@unless($user->is(auth()->user()))<form method="post" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="btn small danger-btn">Delete</button></form>@endunless</div></td></tr>@empty<tr><td class="empty" colspan="6">No users match your search.</td></tr>@endforelse</tbody></table></section>
    {{ $users->links() }}
@endsection
