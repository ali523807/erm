@extends('settings.layout')

@section('title', 'Company setup')

@section('settings.content')
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Global Company Setup</h2>
                <p>Configure country, currency, timezone, tax, and address defaults for this tenant.</p>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('settings.company.update') }}" class="global-setup-form">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12">
                    <x-input label="Company Name" name="name" id="name" value="{{ old('name', $company->name) }}" required/>
                </div>

                <div class="col-12 col-md-6">
                    <x-input label="Billing Email" type="email" name="email" id="email" value="{{ old('email', $company->email) }}"/>
                </div>

                <div class="col-12 col-md-6">
                    <x-input label="Phone" name="phone" id="phone" value="{{ old('phone', $company->phone) }}"/>
                </div>

                <div class="col-12 col-md-6">
                    <label for="country" class="form-label">Country</label>
                    <select id="country" name="country" class="form-select @error('country') is-invalid @enderror">
                        @foreach($countries as $code => $name)
                            <option value="{{ $code }}" @selected(old('country', $company->country) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="currency" class="form-label">Currency</label>
                    <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                        @foreach($currencies as $code => $name)
                            <option value="{{ $code }}" @selected(old('currency', $company->currency) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="timezone" class="form-label">Timezone</label>
                    <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror">
                        @foreach($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $company->timezone) === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                    @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="locale" class="form-label">Locale</label>
                    <select id="locale" name="locale" class="form-select @error('locale') is-invalid @enderror">
                        @foreach($locales as $code => $name)
                            <option value="{{ $code }}" @selected(old('locale', $company->locale) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('locale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="date_format" class="form-label">Date Format</label>
                    <select id="date_format" name="date_format" class="form-select @error('date_format') is-invalid @enderror">
                        @foreach($dateFormats as $format => $example)
                            <option value="{{ $format }}" @selected(old('date_format', $company->date_format) === $format)>{{ $example }}</option>
                        @endforeach
                    </select>
                    @error('date_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="measurement_system" class="form-label">Measurement System</label>
                    <select id="measurement_system" name="measurement_system" class="form-select @error('measurement_system') is-invalid @enderror">
                        @foreach(['metric' => 'Metric', 'imperial' => 'Imperial', 'mixed' => 'Mixed'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('measurement_system', $company->measurement_system) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('measurement_system')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <hr>
                    <h3 class="section-subtitle">Tax Profile</h3>
                </div>

                <div class="col-12 col-md-4">
                    <x-input label="Tax Name" name="tax_name" id="tax_name" placeholder="VAT, GST, Sales Tax"
                             value="{{ old('tax_name', $company->tax_name) }}"/>
                </div>

                <div class="col-12 col-md-4">
                    <x-input label="Tax Number" name="tax_number" id="tax_number"
                             value="{{ old('tax_number', $company->tax_number) }}"/>
                </div>

                <div class="col-12 col-md-4">
                    <x-input label="Default Tax Rate (%)" type="number" name="default_tax_rate" id="default_tax_rate" step="0.0001" min="0" max="100"
                             value="{{ old('default_tax_rate', $company->default_tax_rate ?? 0) }}"/>
                </div>

                <div class="col-12">
                    <label class="form-check">
                        <input type="checkbox" name="tax_inclusive" value="1" class="form-check-input" @checked(old('tax_inclusive', $company->tax_inclusive))>
                        <span class="form-check-label">Prices include tax by default</span>
                    </label>
                </div>

                <div class="col-12">
                    <hr>
                    <h3 class="section-subtitle">Address</h3>
                </div>

                <div class="col-12">
                    <x-input label="Address Line 1" name="address_line_1" id="address_line_1"
                             value="{{ old('address_line_1', $company->address_line_1) }}"/>
                </div>

                <div class="col-12">
                    <x-input label="Address Line 2" name="address_line_2" id="address_line_2"
                             value="{{ old('address_line_2', $company->address_line_2) }}"/>
                </div>

                <div class="col-12 col-md-4">
                    <x-input label="City" name="city" id="city" value="{{ old('city', $company->city) }}"/>
                </div>

                <div class="col-12 col-md-4">
                    <x-input label="State / Region" name="state_region" id="state_region"
                             value="{{ old('state_region', $company->state_region) }}"/>
                </div>

                <div class="col-12 col-md-4">
                    <x-input label="Postal Code" name="postal_code" id="postal_code"
                             value="{{ old('postal_code', $company->postal_code) }}"/>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-dark">
                    <x-lucide-save class="w-4 h-4 me-1"/>
                    Save Company Setup
                </button>
            </div>
        </form>
    </section>
@endsection
