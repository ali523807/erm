@php
    $selectedPermissions = collect($selectedPermissions ?? [])->filter()->values()->all();
    $disabled = $disabled ?? false;
@endphp

<div class="row g-2">
    @foreach($permissionGroups as $groupName => $permissions)
        <div class="col-12 col-md-6">
            <div class="border rounded-3 p-3 h-100">
                <strong class="d-block mb-2">{{ $groupName }}</strong>
                <div class="d-flex flex-column gap-2">
                    @foreach($permissions as $permissionKey => $permissionLabel)
                        <label class="form-check mb-0">
                            <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" class="form-check-input"
                                   @checked(in_array($permissionKey, $selectedPermissions, true)) @disabled($disabled)>
                            <span class="form-check-label small">{{ $permissionLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
