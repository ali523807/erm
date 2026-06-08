@extends('settings.layout')

@section('title', 'Edit team member')

@section('settings.content')
    <section class="panel">
        <div class="panel-header align-items-start">
            <div>
                <span class="eyebrow">Team Member</span>
                <h2>Edit {{ $member->name }}</h2>
                <p>Update the staff profile used inside this rental company tenant and assign the correct operational role.</p>
            </div>
            <a href="{{ route('settings.team') }}" class="btn btn-soft-secondary" wire:navigate>
                <x-lucide-arrow-left class="w-4 h-4 me-1"/>
                Back to Team
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('settings.team.details.update', $member) }}" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-12 col-md-6">
                <x-input label="Full Name" name="name" id="name" value="{{ old('name', $member->name) }}" required/>
            </div>

            <div class="col-12 col-md-6">
                <x-input label="Email Address" type="email" name="email" id="email" value="{{ old('email', $member->email) }}" required/>
            </div>

            <div class="col-12 col-md-6">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->slug }}" @selected(old('role', $memberRole) === $role->slug)>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">If this is the only owner, the system will not allow changing them to another role.</div>
            </div>

            <div class="col-12">
                <hr>
                <h3 class="section-subtitle">Reset Login Password</h3>
                <p class="text-muted small mb-3">Leave these fields blank to keep the current password unchanged.</p>
            </div>

            <div class="col-12 col-md-6">
                <x-input label="New Password" type="password" name="password" id="password"/>
            </div>

            <div class="col-12 col-md-6">
                <x-input label="Confirm New Password" type="password" name="password_confirmation" id="password_confirmation"/>
            </div>

            <div class="col-12">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('settings.team') }}" class="btn btn-soft-secondary" wire:navigate>Cancel</a>
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-save class="w-4 h-4 me-1"/>
                        Save Member
                    </button>
                </div>
            </div>
        </form>
    </section>
@endsection
