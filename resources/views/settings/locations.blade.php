@extends('settings.layout')

@section('title', 'Locations setup')

@section('settings.content')
    <div class="space-y-4">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Branches</h2>
                    <p>Regional offices or operating locations for this tenant.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.locations.branches.store') }}" class="location-setup-form">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <x-input label="Branch Name" name="name" id="branch_name" placeholder="Main Branch" required/>
                    </div>
                    <div class="col-12 col-md-3">
                        <x-input label="Code" name="code" id="branch_code" placeholder="HQ"/>
                    </div>
                    <div class="col-12 col-md-3">
                        <x-input label="Phone" name="phone" id="branch_phone"/>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input label="Email" type="email" name="email" id="branch_email"/>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input label="Timezone" name="timezone" id="branch_timezone" value="{{ auth()->user()->currentCompany?->timezone }}"/>
                    </div>
                    <div class="col-12">
                        <x-input label="Address Line 1" name="address_line_1" id="branch_address_line_1"/>
                    </div>
                    <div class="col-12 col-md-4">
                        <x-input label="City" name="city" id="branch_city"/>
                    </div>
                    <div class="col-12 col-md-4">
                        <x-input label="State / Region" name="state_region" id="branch_state_region"/>
                    </div>
                    <div class="col-12 col-md-4">
                        <x-input label="Country" name="country" id="branch_country" value="{{ auth()->user()->currentCompany?->country }}"/>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Branch is active</span>
                        </label>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-plus class="w-4 h-4 me-1"/>
                        Add Branch
                    </button>
                </div>
            </form>

            <div class="table-responsive mt-4">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Address</th>
                        <th>Warehouses</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td>
                                <strong>{{ $branch->name }}</strong>
                                <div class="text-muted text-xs">{{ $branch->code ?? 'No code' }}</div>
                            </td>
                            <td>{{ collect([$branch->city, $branch->state_region, $branch->country])->filter()->join(', ') ?: 'No address' }}</td>
                            <td>{{ number_format($branch->warehouses_count) }}</td>
                            <td>
                                <span class="badge {{ $branch->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#branch-edit-{{ $branch->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('settings.locations.branches.destroy', $branch) }}" class="d-inline" onsubmit="return confirm('Delete this branch and its warehouses?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="branch-edit-{{ $branch->id }}">
                            <td colspan="5">
                                <form method="POST" action="{{ route('settings.locations.branches.update', $branch) }}" class="inline-edit-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <x-input label="Branch Name" name="name" id="branch_edit_name_{{ $branch->id }}" value="{{ $branch->name }}" required/>
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <x-input label="Code" name="code" id="branch_edit_code_{{ $branch->id }}" value="{{ $branch->code }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="Email" type="email" name="email" id="branch_edit_email_{{ $branch->id }}" value="{{ $branch->email }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="Phone" name="phone" id="branch_edit_phone_{{ $branch->id }}" value="{{ $branch->phone }}"/>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <x-input label="Timezone" name="timezone" id="branch_edit_timezone_{{ $branch->id }}" value="{{ $branch->timezone }}"/>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <x-input label="Address Line 1" name="address_line_1" id="branch_edit_address_{{ $branch->id }}" value="{{ $branch->address_line_1 }}"/>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <x-input label="Address Line 2" name="address_line_2" id="branch_edit_address_2_{{ $branch->id }}" value="{{ $branch->address_line_2 }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="City" name="city" id="branch_edit_city_{{ $branch->id }}" value="{{ $branch->city }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="State / Region" name="state_region" id="branch_edit_state_{{ $branch->id }}" value="{{ $branch->state_region }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="Postal Code" name="postal_code" id="branch_edit_postal_{{ $branch->id }}" value="{{ $branch->postal_code }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="Country" name="country" id="branch_edit_country_{{ $branch->id }}" value="{{ $branch->country }}"/>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <label class="form-check mb-0">
                                                <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($branch->is_active)>
                                                <span class="form-check-label">Active</span>
                                            </label>
                                            <button type="submit" class="btn btn-dark btn-sm">Save Branch</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No branches yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Warehouses / Yards</h2>
                    <p>Physical storage or dispatch sites under a branch.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.locations.warehouses.store') }}" class="location-setup-form">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="warehouse_branch_id" class="form-label">Branch</label>
                        <select id="warehouse_branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <x-input label="Warehouse / Yard Name" name="name" id="warehouse_name" placeholder="Main Yard" required/>
                    </div>
                    <div class="col-12 col-md-2">
                        <x-input label="Code" name="code" id="warehouse_code" placeholder="YRD-1"/>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="warehouse_type" class="form-label">Type</label>
                        <select id="warehouse_type" name="type" class="form-select">
                            <option value="yard">Yard</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="depot">Depot</option>
                            <option value="service_bay">Service Bay</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input label="Address Line 1" name="address_line_1" id="warehouse_address_line_1"/>
                    </div>
                    <div class="col-12 col-md-3">
                        <x-input label="City" name="city" id="warehouse_city"/>
                    </div>
                    <div class="col-12 col-md-3">
                        <x-input label="Country" name="country" id="warehouse_country" value="{{ auth()->user()->currentCompany?->country }}"/>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Warehouse is active</span>
                        </label>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-dark" @disabled($branches->isEmpty())>
                        <x-lucide-plus class="w-4 h-4 me-1"/>
                        Add Warehouse
                    </button>
                </div>
            </form>

            <div class="table-responsive mt-4">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Warehouse / Yard</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>Storage Locations</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($warehouses as $warehouse)
                        <tr>
                            <td>
                                <strong>{{ $warehouse->name }}</strong>
                                <div class="text-muted text-xs">{{ $warehouse->code ?? 'No code' }}</div>
                            </td>
                            <td>{{ $warehouse->branch?->name }}</td>
                            <td>{{ str($warehouse->type)->headline() }}</td>
                            <td>{{ number_format($warehouse->storage_locations_count) }}</td>
                            <td>
                                <span class="badge {{ $warehouse->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                    {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#warehouse-edit-{{ $warehouse->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('settings.locations.warehouses.destroy', $warehouse) }}" class="d-inline" onsubmit="return confirm('Delete this warehouse and its storage locations?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="warehouse-edit-{{ $warehouse->id }}">
                            <td colspan="6">
                                <form method="POST" action="{{ route('settings.locations.warehouses.update', $warehouse) }}" class="inline-edit-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label for="warehouse_edit_branch_{{ $warehouse->id }}" class="form-label">Branch</label>
                                            <select id="warehouse_edit_branch_{{ $warehouse->id }}" name="branch_id" class="form-select" required>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}" @selected($warehouse->branch_id === $branch->id)>{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <x-input label="Warehouse / Yard Name" name="name" id="warehouse_edit_name_{{ $warehouse->id }}" value="{{ $warehouse->name }}" required/>
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <x-input label="Code" name="code" id="warehouse_edit_code_{{ $warehouse->id }}" value="{{ $warehouse->code }}"/>
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <label for="warehouse_edit_type_{{ $warehouse->id }}" class="form-label">Type</label>
                                            <select id="warehouse_edit_type_{{ $warehouse->id }}" name="type" class="form-select">
                                                @foreach(['yard' => 'Yard', 'warehouse' => 'Warehouse', 'depot' => 'Depot', 'service_bay' => 'Service Bay'] as $value => $label)
                                                    <option value="{{ $value }}" @selected($warehouse->type === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <x-input label="Phone" name="phone" id="warehouse_edit_phone_{{ $warehouse->id }}" value="{{ $warehouse->phone }}"/>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <x-input label="Address Line 1" name="address_line_1" id="warehouse_edit_address_{{ $warehouse->id }}" value="{{ $warehouse->address_line_1 }}"/>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <x-input label="Address Line 2" name="address_line_2" id="warehouse_edit_address_2_{{ $warehouse->id }}" value="{{ $warehouse->address_line_2 }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="City" name="city" id="warehouse_edit_city_{{ $warehouse->id }}" value="{{ $warehouse->city }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="State / Region" name="state_region" id="warehouse_edit_state_{{ $warehouse->id }}" value="{{ $warehouse->state_region }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="Postal Code" name="postal_code" id="warehouse_edit_postal_{{ $warehouse->id }}" value="{{ $warehouse->postal_code }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="Country" name="country" id="warehouse_edit_country_{{ $warehouse->id }}" value="{{ $warehouse->country }}"/>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <label class="form-check mb-0">
                                                <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($warehouse->is_active)>
                                                <span class="form-check-label">Active</span>
                                            </label>
                                            <button type="submit" class="btn btn-dark btn-sm">Save Warehouse</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No warehouses or yards yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Storage Locations</h2>
                    <p>Zones, bays, bins, racks, shelves, or yard sections inside a warehouse.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.locations.storage-locations.store') }}" class="location-setup-form">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="storage_warehouse_id" class="form-label">Warehouse / Yard</label>
                        <select id="storage_warehouse_id" name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                            <option value="">Select warehouse</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->branch?->name }} / {{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <x-input label="Location Name" name="name" id="storage_name" placeholder="Zone A" required/>
                    </div>
                    <div class="col-12 col-md-2">
                        <x-input label="Code" name="code" id="storage_code" placeholder="A-01"/>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="storage_type" class="form-label">Type</label>
                        <select id="storage_type" name="type" class="form-select">
                            <option value="zone">Zone</option>
                            <option value="bay">Bay</option>
                            <option value="bin">Bin</option>
                            <option value="rack">Rack</option>
                            <option value="shelf">Shelf</option>
                            <option value="yard_section">Yard Section</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-8">
                        <x-input label="Parent Area" name="parent_area" id="parent_area" placeholder="North Yard, Rack Row 2"/>
                    </div>
                    <div class="col-12 col-md-4">
                        <x-input label="Sort Order" type="number" name="sort_order" id="sort_order" value="0" min="0"/>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Storage location is active</span>
                        </label>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-dark" @disabled($warehouses->isEmpty())>
                        <x-lucide-plus class="w-4 h-4 me-1"/>
                        Add Storage Location
                    </button>
                </div>
            </form>

            <div class="table-responsive mt-4">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Storage Location</th>
                        <th>Warehouse</th>
                        <th>Type</th>
                        <th>Parent Area</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($storageLocations as $location)
                        <tr>
                            <td>
                                <strong>{{ $location->name }}</strong>
                                <div class="text-muted text-xs">{{ $location->code ?? 'No code' }}</div>
                            </td>
                            <td>{{ $location->warehouse?->branch?->name }} / {{ $location->warehouse?->name }}</td>
                            <td>{{ str($location->type)->headline() }}</td>
                            <td>{{ $location->parent_area ?? 'None' }}</td>
                            <td>
                                <span class="badge {{ $location->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#storage-edit-{{ $location->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('settings.locations.storage-locations.destroy', $location) }}" class="d-inline" onsubmit="return confirm('Delete this storage location?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="storage-edit-{{ $location->id }}">
                            <td colspan="6">
                                <form method="POST" action="{{ route('settings.locations.storage-locations.update', $location) }}" class="inline-edit-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label for="storage_edit_warehouse_{{ $location->id }}" class="form-label">Warehouse / Yard</label>
                                            <select id="storage_edit_warehouse_{{ $location->id }}" name="warehouse_id" class="form-select" required>
                                                @foreach($warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}" @selected($location->warehouse_id === $warehouse->id)>{{ $warehouse->branch?->name }} / {{ $warehouse->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <x-input label="Location Name" name="name" id="storage_edit_name_{{ $location->id }}" value="{{ $location->name }}" required/>
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <x-input label="Code" name="code" id="storage_edit_code_{{ $location->id }}" value="{{ $location->code }}"/>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label for="storage_edit_type_{{ $location->id }}" class="form-label">Type</label>
                                            <select id="storage_edit_type_{{ $location->id }}" name="type" class="form-select">
                                                @foreach(['zone' => 'Zone', 'bay' => 'Bay', 'bin' => 'Bin', 'rack' => 'Rack', 'shelf' => 'Shelf', 'yard_section' => 'Yard Section'] as $value => $label)
                                                    <option value="{{ $value }}" @selected($location->type === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-8">
                                            <x-input label="Parent Area" name="parent_area" id="storage_edit_parent_{{ $location->id }}" value="{{ $location->parent_area }}"/>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <x-input label="Sort Order" type="number" name="sort_order" id="storage_edit_sort_{{ $location->id }}" value="{{ $location->sort_order }}" min="0"/>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <label class="form-check mb-0">
                                                <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($location->is_active)>
                                                <span class="form-check-label">Active</span>
                                            </label>
                                            <button type="submit" class="btn btn-dark btn-sm">Save Storage Location</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No storage locations yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
