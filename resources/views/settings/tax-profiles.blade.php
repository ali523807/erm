@extends('settings.layout')

@section('title', 'Tax profiles')

@section('settings.content')
    <section class="panel mb-3">
        <div class="panel-header align-items-start">
            <div>
                <h2>Tax Profiles</h2>
                <p>Define VAT, GST, Sales Tax, or regional tax rules used when creating invoices.</p>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('settings.tax-profiles.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="name" class="form-label">Profile Name</label>
                    <input id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="VAT 5%" required>
                </div>
                <div class="col-md-2">
                    <label for="code" class="form-label">Code</label>
                    <input id="code" name="code" class="form-control" value="{{ old('code') }}" placeholder="VAT">
                </div>
                <div class="col-md-2">
                    <label for="country" class="form-label">Country</label>
                    <select id="country" name="country" class="form-select">
                        <option value="">Global</option>
                        @foreach($countries as $code => $name)
                            <option value="{{ $code }}" @selected(old('country', $company->country) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="rate" class="form-label">Rate (%)</label>
                    <input id="rate" name="rate" type="number" step="0.0001" min="0" max="100" class="form-control" value="{{ old('rate', $company->default_tax_rate ?? 0) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="description" class="form-label">Description</label>
                    <input id="description" name="description" class="form-control" value="{{ old('description') }}" placeholder="Applied to domestic rentals">
                </div>
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="checkbox" name="is_default" value="1" class="form-check-input" @checked(old('is_default', true))>
                        <span class="form-check-label">Use as default tax profile for new invoices</span>
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                        <span class="form-check-label">Active</span>
                    </label>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-plus class="w-4 h-4"/>
                        Add Tax Profile
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header align-items-start">
            <div>
                <h2>Configured Profiles</h2>
                <p>Keep old profiles inactive when tax rules change so existing invoices remain traceable.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table modern-table align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Country</th>
                    <th>Rate</th>
                    <th>Status</th>
                    <th>Description</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($taxProfiles as $profile)
                    <tr>
                        <form method="POST" action="{{ route('settings.tax-profiles.update', $profile) }}">
                            @csrf
                            @method('PUT')
                            <td>
                                <input name="name" class="form-control form-control-sm mb-1" value="{{ old('name', $profile->name) }}" required>
                                <input name="code" class="form-control form-control-sm" value="{{ old('code', $profile->code) }}" placeholder="Code">
                            </td>
                            <td>
                                <select name="country" class="form-select form-select-sm">
                                    <option value="">Global</option>
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}" @selected(old('country', $profile->country) === $code)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input name="rate" type="number" step="0.0001" min="0" max="100" class="form-control form-control-sm" value="{{ old('rate', $profile->rate) }}" required>
                            </td>
                            <td>
                                <label class="form-check mb-1">
                                    <input type="checkbox" name="is_default" value="1" class="form-check-input" @checked($profile->is_default)>
                                    <span class="form-check-label">Default</span>
                                </label>
                                <label class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($profile->is_active)>
                                    <span class="form-check-label">Active</span>
                                </label>
                            </td>
                            <td>
                                <input name="description" class="form-control form-control-sm" value="{{ old('description', $profile->description) }}">
                            </td>
                            <td>
                                <div class="table-actions justify-content-end">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <x-lucide-save class="w-4 h-4"/>
                                        Save
                                    </button>
                                </div>
                            </td>
                        </form>
                        <td class="d-none">
                            <form method="POST" action="{{ route('settings.tax-profiles.destroy', $profile) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No tax profiles configured yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
