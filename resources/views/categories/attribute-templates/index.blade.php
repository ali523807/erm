@extends('layouts.app')

@section('title', 'Category Attribute Templates')

@php
    $optionText = fn ($template): string => collect($template->options ?? [])->join("\n");
@endphp

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Category setup</span>
                <h1>{{ $category->name }} Attributes</h1>
                <p>Define reusable equipment fields for this category so every asset captures the right specifications without making the main equipment form rigid.</p>
            </div>

            <x-button :link="route('categories.index')" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Back</span>
            </x-button>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Add Attribute Template</h2>
                    <p>Use templates for repeatable category-specific details like capacity, color, fuel type, dimensions, voltage, lens mount, plate number, or included accessories.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('categories.attribute-templates.store', $category) }}">
                @csrf

                <div class="row g-3">
                    <div class="col-lg-4">
                        <label for="name" class="form-label">Field Label <span class="text-danger">*</span></label>
                        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Fuel Type" required>
                        <div class="form-text">Human-friendly label shown on the equipment form.</div>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="key" class="form-label">Field Key</label>
                        <input id="key" name="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key') }}" placeholder="fuel_type">
                        <div class="form-text">Optional stable system key. Leave blank to generate it from the label.</div>
                        @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="type" class="form-label">Field Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach($fieldTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', 'text') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Choose select when users must pick from a fixed list.</div>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="unit" class="form-label">Unit</label>
                        <input id="unit" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit') }}" placeholder="kVA, kg, mm, ft">
                        <div class="form-text">Optional measurement unit for numeric or dimensional fields.</div>
                        @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="default_value" class="form-label">Default Value</label>
                        <input id="default_value" name="default_value" class="form-control @error('default_value') is-invalid @enderror" value="{{ old('default_value') }}" placeholder="Diesel">
                        <div class="form-text">Pre-filled value for new equipment in this category.</div>
                        @error('default_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="placeholder" class="form-label">Placeholder</label>
                        <input id="placeholder" name="placeholder" class="form-control @error('placeholder') is-invalid @enderror" value="{{ old('placeholder') }}" placeholder="Example: Diesel">
                        <div class="form-text">Short example shown before the user enters a value.</div>
                        @error('placeholder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}">
                        <div class="form-text">Lower numbers appear first on the equipment form.</div>
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="options_text" class="form-label">Options</label>
                        <textarea id="options_text" name="options_text" rows="4" class="form-control @error('options_text') is-invalid @enderror" placeholder="Diesel&#10;Petrol&#10;Electric">{{ old('options_text') }}</textarea>
                        <div class="form-text">For select fields only. Enter one option per line.</div>
                        @error('options_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="help_text" class="form-label">Help Text</label>
                        <textarea id="help_text" name="help_text" rows="4" class="form-control @error('help_text') is-invalid @enderror" placeholder="Tell staff what this field means and how to enter it.">{{ old('help_text') }}</textarea>
                        <div class="form-text">Clear guidance reduces wrong data across branches and countries.</div>
                        @error('help_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <label class="form-check mb-0">
                            <input type="checkbox" name="is_required" value="1" class="form-check-input" @checked(old('is_required'))>
                            <span class="form-check-label">Require this field when equipment is created or edited</span>
                        </label>

                        <button type="submit" class="btn btn-dark">
                            <x-lucide-plus class="w-4 h-4 me-1"/>
                            Add Template
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <section class="panel mt-3">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Configured Templates</h2>
                    <p>These templates become suggested specification rows when equipment is created for {{ $category->name }}.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Help</th>
                        <th>Required</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>
                                <strong>{{ $template->name }}</strong>
                                <div class="text-muted text-xs">{{ $template->key }}{{ $template->unit ? ' · '.$template->unit : '' }}</div>
                            </td>
                            <td>{{ $fieldTypes[$template->type] ?? str($template->type)->headline() }}</td>
                            <td>
                                <span>{{ $template->help_text ?: 'No help text' }}</span>
                                @if($template->options)
                                    <div class="text-muted text-xs">Options: {{ collect($template->options)->join(', ') }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $template->is_required ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                    {{ $template->is_required ? 'Required' : 'Optional' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#template-edit-{{ $template->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('categories.attribute-templates.destroy', [$category, $template]) }}" class="d-inline" onsubmit="return confirm('Delete this attribute template? Existing equipment values will stay, but this template will no longer be suggested.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="template-edit-{{ $template->id }}">
                            <td colspan="5">
                                <form method="POST" action="{{ route('categories.attribute-templates.update', [$category, $template]) }}" class="inline-edit-form">
                                    @csrf
                                    @method('PUT')

                                    <div class="row g-3">
                                        <div class="col-lg-4">
                                            <label for="name_{{ $template->id }}" class="form-label">Field Label</label>
                                            <input id="name_{{ $template->id }}" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
                                            <div class="form-text">Display label shown on equipment forms.</div>
                                        </div>
                                        <div class="col-lg-4">
                                            <label for="key_{{ $template->id }}" class="form-label">Field Key</label>
                                            <input id="key_{{ $template->id }}" name="key" class="form-control" value="{{ old('key', $template->key) }}" required>
                                            <div class="form-text">Keep this stable once equipment values exist.</div>
                                        </div>
                                        <div class="col-lg-4">
                                            <label for="type_{{ $template->id }}" class="form-label">Field Type</label>
                                            <select id="type_{{ $template->id }}" name="type" class="form-select" required>
                                                @foreach($fieldTypes as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('type', $template->type) === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Changing type only affects future editing behavior.</div>
                                        </div>
                                        <div class="col-lg-3">
                                            <label for="unit_{{ $template->id }}" class="form-label">Unit</label>
                                            <input id="unit_{{ $template->id }}" name="unit" class="form-control" value="{{ old('unit', $template->unit) }}">
                                        </div>
                                        <div class="col-lg-3">
                                            <label for="default_value_{{ $template->id }}" class="form-label">Default Value</label>
                                            <input id="default_value_{{ $template->id }}" name="default_value" class="form-control" value="{{ old('default_value', $template->default_value) }}">
                                        </div>
                                        <div class="col-lg-3">
                                            <label for="placeholder_{{ $template->id }}" class="form-label">Placeholder</label>
                                            <input id="placeholder_{{ $template->id }}" name="placeholder" class="form-control" value="{{ old('placeholder', $template->placeholder) }}">
                                        </div>
                                        <div class="col-lg-3">
                                            <label for="sort_order_{{ $template->id }}" class="form-label">Sort Order</label>
                                            <input id="sort_order_{{ $template->id }}" name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $template->sort_order) }}">
                                        </div>
                                        <div class="col-lg-6">
                                            <label for="options_text_{{ $template->id }}" class="form-label">Options</label>
                                            <textarea id="options_text_{{ $template->id }}" name="options_text" rows="4" class="form-control">{{ old('options_text', $optionText($template)) }}</textarea>
                                            <div class="form-text">One option per line for select fields.</div>
                                        </div>
                                        <div class="col-lg-6">
                                            <label for="help_text_{{ $template->id }}" class="form-label">Help Text</label>
                                            <textarea id="help_text_{{ $template->id }}" name="help_text" rows="4" class="form-control">{{ old('help_text', $template->help_text) }}</textarea>
                                            <div class="form-text">Explain exactly what your team should enter.</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <label class="form-check mb-0">
                                                <input type="checkbox" name="is_required" value="1" class="form-check-input" @checked(old('is_required', $template->is_required))>
                                                <span class="form-check-label">Required field</span>
                                            </label>
                                            <button type="submit" class="btn btn-dark btn-sm">Save Template</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No attribute templates yet. Add the first field above for this category.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
