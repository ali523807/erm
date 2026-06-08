@extends('settings.layout')

@section('title', 'Team and roles')

@section('settings.content')
    <section class="panel">
        <div class="panel-header align-items-start">
            <div>
                <span class="eyebrow">Access Control</span>
                <h2>Team & Roles</h2>
                <p>Add company users, assign operational roles, and keep tenant access separated from the platform owner panel. Create custom roles from Roles & Permissions.</p>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('temporary_password'))
            <div class="alert alert-warning">
                <strong>Temporary password:</strong> {{ session('temporary_password') }}
                <span class="d-block small mt-1">Share it with the new user and ask them to change it after first login.</span>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="border rounded-3 p-3 h-100">
                    <h3 class="section-subtitle">Add Team Member</h3>
                    <p class="text-muted small mb-3">Use this for rental company staff only. Platform owner users stay in the separate platform login.</p>

                    <form method="POST" action="{{ route('settings.team.store') }}" class="row g-3">
                        @csrf

                        <div class="col-12">
                            <x-input label="Full Name" name="name" id="name" value="{{ old('name') }}" required/>
                        </div>

                        <div class="col-12">
                            <x-input label="Email Address" type="email" name="email" id="email" value="{{ old('email') }}" required/>
                        </div>

                        <div class="col-12">
                            <x-input label="Login Password" type="password" name="password" id="password" placeholder="Leave blank to auto-generate"/>
                            <div class="form-text">If left blank, a temporary password will be shown after adding the member.</div>
                        </div>

                        <div class="col-12">
                            <x-input label="Confirm Password" type="password" name="password_confirmation" id="password_confirmation"/>
                        </div>

                        <div class="col-12">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" @selected(old('role', 'operations') === $role->slug)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Choose the closest responsibility. You can create more roles in Roles & Permissions.</div>
                        </div>

                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-dark">
                                <x-lucide-user-plus class="w-4 h-4 me-1"/>
                                Add Member
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex justify-content-between gap-3 flex-wrap mb-3">
                        <div>
                            <h3 class="section-subtitle mb-1">Current Team</h3>
                            <p class="text-muted small mb-0">{{ $company->name }} has {{ $members->count() }} active user{{ $members->count() === 1 ? '' : 's' }}.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    <tr>
                                        <td>
                                            <strong>{{ $member->name }}</strong>
                                            <span class="d-block text-muted small">{{ $member->email }}</span>
                                        </td>
                                        <td style="min-width: 180px;">
                                            <form method="POST" action="{{ route('settings.team.update', $member) }}" class="d-flex gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" class="form-select form-select-sm" aria-label="Role for {{ $member->name }}">
                                                    @foreach($roles as $role)
                                                        <option value="{{ $role->slug }}" @selected($member->pivot->role === $role->slug)>{{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-soft-primary btn-sm" title="Save role">
                                                    <x-lucide-save class="w-4 h-4"/>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                {{ $member->pivot->joined_at ? \Illuminate\Support\Carbon::parse($member->pivot->joined_at)->format('M d, Y') : 'Not recorded' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('settings.team.edit', $member) }}" class="btn btn-soft-primary btn-sm" title="Edit member" wire:navigate>
                                                <x-lucide-pencil class="w-4 h-4"/>
                                            </a>
                                            <form method="POST" action="{{ route('settings.team.destroy', $member) }}" onsubmit="return confirm('Remove this user from the company?')" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-soft-danger btn-sm" title="Remove member" @disabled(auth()->id() === $member->id)>
                                                    <x-lucide-trash-2 class="w-4 h-4"/>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="panel mt-4">
        <div class="panel-header">
            <div>
                <h2>Role Guide</h2>
                <p>Start with simple responsibilities now; the same role keys can later drive module-level permissions.</p>
            </div>
        </div>

        <div class="row g-3">
            @foreach($roles as $role)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong>{{ $role->name }}</strong>
                            <span class="badge text-bg-light">{{ $role->slug }}</span>
                        </div>
                        <p class="text-muted small mb-0">{{ $role->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
