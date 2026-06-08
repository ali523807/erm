@php
    $value = fn (string $field, mixed $default = null): mixed => old($field, $customer?->{$field} ?? $default);
@endphp

<div class="row g-3">
    <div class="col-lg-6">
        <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
        <input id="company_name" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ $value('company_name') }}" placeholder="Northstar Construction LLC" required>
        <div class="form-text">Use the legal or trading name your team will recognize on quotes, rentals, invoices, and statements.</div>
        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-lg-6">
        <label for="contact_person" class="form-label">Primary Contact</label>
        <input id="contact_person" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ $value('contact_person') }}" placeholder="Avery Stone">
        <div class="form-text">Main person for rental coordination, statements, and portal access later.</div>
        @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-lg-6">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ $value('email') }}" placeholder="contact@example.com">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-lg-6">
        <label for="phone" class="form-label">Phone</label>
        <input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ $value('phone') }}" placeholder="+1 555 0122">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-lg-6">
        <label for="trade_license_number" class="form-label">Trade License / Registration</label>
        <input id="trade_license_number" name="trade_license_number" class="form-control @error('trade_license_number') is-invalid @enderror" value="{{ $value('trade_license_number') }}" placeholder="License or registration number">
        @error('trade_license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-lg-6">
        <label for="vat_number" class="form-label">VAT / Tax Number</label>
        <input id="vat_number" name="vat_number" class="form-control @error('vat_number') is-invalid @enderror" value="{{ $value('vat_number') }}" placeholder="Tax ID">
        @error('vat_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label">Billing / Site Address</label>
        <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror" placeholder="Billing address, site address, or common delivery instructions">{{ $value('address') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Internal Notes</label>
        <textarea id="notes" name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Payment preferences, delivery timing, insurance requirements, or relationship notes">{{ $value('notes') }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
