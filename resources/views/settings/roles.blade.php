@extends('settings.layout')

@section('title', 'Roles and permissions')

@section('settings.content')
    <section class="panel">
        <div class="panel-header align-items-start">
            <div>
                <span class="eyebrow">Permissions</span>
                <h2>Roles & Permissions</h2>
                <p>Create company roles and decide which areas of the rental workflow each role should access.</p>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="border rounded-3 p-3 h-100">
                    <h3 class="section-subtitle">Create Custom Role</h3>
                    <p class="text-muted small mb-3">Use custom roles when the default sales, operations, accounts, or maintenance roles are not enough.</p>

                    <form method="POST" action="{{ route('settings.roles.store') }}" class="row g-3">
                        @csrf

                        <div class="col-12">
                            <x-input label="Role Name" name="name" id="name" value="{{ old('name') }}" placeholder="Example: Branch Manager" required/>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" placeholder="What should this role be used for?">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <h4 class="section-subtitle mb-2">Permissions</h4>
                            @include('settings.partials._permission-checkboxes', ['selectedPermissions' => old('permissions', [])])
                        </div>

                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-dark">
                                <x-lucide-plus class="w-4 h-4 me-1"/>
                                Create Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="d-flex flex-column gap-3">
                    @foreach($roles as $role)
                        <div class="border rounded-3 p-3">
                            <form method="POST" action="{{ route('settings.roles.update', $role) }}" class="row g-3">
                                @csrf
                                @method('PUT')

                                <div class="col-12 col-lg-6">
                                    <label for="role-name-{{ $role->id }}" class="form-label">Role Name</label>
                                    <input id="role-name-{{ $role->id }}" name="name" class="form-control" value="{{ old("roles.{$role->id}.name", $role->name) }}" @readonly($role->is_system)>
                                    @if($role->is_system)
                                        <div class="form-text">Default role names are fixed so existing team assignments remain stable.</div>
                                    @endif
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label">Role Key</label>
                                    <input class="form-control" value="{{ $role->slug }}" readonly>
                                </div>

                                <div class="col-12">
                                    <label for="role-description-{{ $role->id }}" class="form-label">Description</label>
                                    <textarea id="role-description-{{ $role->id }}" name="description" class="form-control" rows="2">{{ old("roles.{$role->id}.description", $role->description) }}</textarea>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                        <h4 class="section-subtitle mb-0">Allowed Actions</h4>
                                        @if($role->slug === 'owner')
                                            <span class="badge text-bg-success">Always full access</span>
                                        @elseif($role->is_system)
                                            <span class="badge text-bg-light">Default role</span>
                                        @else
                                            <span class="badge text-bg-warning">Custom role</span>
                                        @endif
                                    </div>

                                    @include('settings.partials._permission-checkboxes', [
                                        'selectedPermissions' => $role->slug === 'owner' ? collect($permissionGroups)->flatMap(fn ($items) => array_keys($items))->all() : ($role->permissions ?? []),
                                        'disabled' => $role->slug === 'owner',
                                    ])
                                </div>

                                <div class="col-12 d-flex justify-content-end gap-2">
                                    @if(! $role->is_system)
                                        <button type="submit" form="delete-role-{{ $role->id }}" class="btn btn-soft-danger" onclick="return confirm('Delete this role?')">
                                            <x-lucide-trash-2 class="w-4 h-4 me-1"/>
                                            Delete
                                        </button>
                                    @endif
                                    <button type="submit" class="btn btn-soft-primary">
                                        <x-lucide-save class="w-4 h-4 me-1"/>
                                        Save Permissions
                                    </button>
                                </div>
                            </form>

                            @if(! $role->is_system)
                                <form id="delete-role-{{ $role->id }}" method="POST" action="{{ route('settings.roles.destroy', $role) }}" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
