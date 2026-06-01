@extends('layouts.app')

@section('title', $agreement->agreement_number)

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Rental agreement</span>
                <h1>{{ $agreement->agreement_number }}</h1>
                <p>{{ $agreement->rental?->customer?->company_name }} - RTN-{{ $agreement->rental_id }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('rentals.show', $agreement->rental)" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Rental</span>
                </x-button>
                <x-button :link="route('agreements.print', $agreement)" color="outline-secondary">
                    <x-lucide-printer class="w-4 h-4"/>
                    <span>Print</span>
                </x-button>
                <x-button :link="route('agreements.download', $agreement)" color="dark">
                    <x-lucide-download class="w-4 h-4"/>
                    <span>PDF</span>
                </x-button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3">
            <div class="col-xl-8">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Agreement Items</h2>
                            <p>Equipment covered by this contract and handover record.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Period</th>
                                <th>Duration</th>
                                <th>Rate</th>
                                <th>Deposit</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($agreement->rental->rentalItems as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product?->name }}</strong>
                                        <div class="text-muted text-xs">{{ $item->product?->equipment_code ?: 'Equipment item' }}</div>
                                    </td>
                                    <td>{{ $item->start_date }} - {{ $item->end_date }}</td>
                                    <td>{{ number_format((float) $item->no_of_duration, 2) }} {{ $item->duration_type }}</td>
                                    <td>{{ number_format((float) $item->rate, 2) }}</td>
                                    <td>{{ number_format((float) $item->deposit_amount, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-xl-4">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Status</h2>
                            <p>Current contract and handover state.</p>
                        </div>
                    </div>

                    <dl class="detail-grid">
                        <div>
                            <dt>Status</dt>
                            <dd>{{ str($agreement->status)->headline() }}</dd>
                        </div>
                        <div>
                            <dt>Agreement Date</dt>
                            <dd>{{ $agreement->agreement_date?->format('Y-m-d') ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Valid Until</dt>
                            <dd>{{ $agreement->valid_until?->format('Y-m-d') ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Signed By</dt>
                            <dd>{{ $agreement->signed_by_customer ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Checkout</dt>
                            <dd>{{ $agreement->checked_out_at?->format('Y-m-d H:i') ?: 'Pending' }}</dd>
                        </div>
                        <div>
                            <dt>Return</dt>
                            <dd>{{ $agreement->returned_at?->format('Y-m-d H:i') ?: 'Pending' }}</dd>
                        </div>
                        <div>
                            <dt>Damage Charge</dt>
                            <dd>{{ number_format((float) $agreement->damage_amount, 2) }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Check-Out Sign-Off</h2>
                            <p>Capture the customer handover acknowledgement before equipment leaves.</p>
                        </div>
                    </div>

                    @if($agreement->checked_out_at)
                        <dl class="detail-grid">
                            <div>
                                <dt>Representative</dt>
                                <dd>{{ $agreement->checkout_representative }}</dd>
                            </div>
                            <div>
                                <dt>ID / License</dt>
                                <dd>{{ $agreement->checkout_id_number ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt>Condition</dt>
                                <dd>{{ $agreement->checkout_condition }}</dd>
                            </div>
                            <div>
                                <dt>Accessories</dt>
                                <dd>{{ $agreement->checkout_accessories ?: '-' }}</dd>
                            </div>
                        </dl>
                    @else
                        <form method="POST" action="{{ route('agreements.checkout', $agreement) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="checkout_representative" class="form-label">Customer Representative</label>
                                    <input id="checkout_representative" name="checkout_representative" class="form-control" value="{{ old('checkout_representative', $agreement->signed_by_customer) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="checkout_id_number" class="form-label">ID / License Number</label>
                                    <input id="checkout_id_number" name="checkout_id_number" class="form-control" value="{{ old('checkout_id_number') }}">
                                </div>
                                <div class="col-12">
                                    <label for="checkout_condition" class="form-label">Condition Before Dispatch</label>
                                    <textarea id="checkout_condition" name="checkout_condition" rows="3" class="form-control" required>{{ old('checkout_condition', 'Equipment inspected and handed over in working condition.') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label for="checkout_accessories" class="form-label">Accessories Handed Over</label>
                                    <textarea id="checkout_accessories" name="checkout_accessories" rows="2" class="form-control">{{ old('checkout_accessories') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label for="checkout_notes" class="form-label">Notes</label>
                                    <textarea id="checkout_notes" name="checkout_notes" rows="2" class="form-control">{{ old('checkout_notes') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-check">
                                        <input type="checkbox" name="customer_accepted_checkout" value="1" class="form-check-input" required>
                                        <span class="form-check-label">Customer confirms receipt and accepts responsibility during rental.</span>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark mt-3">Complete Check-Out</button>
                        </form>
                    @endif
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Return Sign-Off</h2>
                            <p>Capture return condition, missing accessories, damages, and customer acknowledgement.</p>
                        </div>
                    </div>

                    @if($agreement->returned_at)
                        <dl class="detail-grid">
                            <div>
                                <dt>Representative</dt>
                                <dd>{{ $agreement->return_representative }}</dd>
                            </div>
                            <div>
                                <dt>Condition</dt>
                                <dd>{{ $agreement->return_condition }}</dd>
                            </div>
                            <div>
                                <dt>Missing Accessories</dt>
                                <dd>{{ $agreement->return_missing_accessories ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt>Damage Notes</dt>
                                <dd>{{ $agreement->return_damage_notes ?: '-' }}</dd>
                            </div>
                        </dl>
                    @else
                        <form method="POST" action="{{ route('agreements.return', $agreement) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="return_representative" class="form-label">Customer Representative</label>
                                    <input id="return_representative" name="return_representative" class="form-control" value="{{ old('return_representative', $agreement->checkout_representative) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="damage_amount" class="form-label">Damage Charge</label>
                                    <input id="damage_amount" name="damage_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('damage_amount', 0) }}">
                                </div>
                                <div class="col-12">
                                    <label for="return_condition" class="form-label">Condition After Return</label>
                                    <textarea id="return_condition" name="return_condition" rows="3" class="form-control" required>{{ old('return_condition', 'Equipment returned and inspected.') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label for="return_missing_accessories" class="form-label">Missing Accessories</label>
                                    <textarea id="return_missing_accessories" name="return_missing_accessories" rows="2" class="form-control">{{ old('return_missing_accessories') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label for="return_damage_notes" class="form-label">Damage Notes</label>
                                    <textarea id="return_damage_notes" name="return_damage_notes" rows="2" class="form-control">{{ old('return_damage_notes') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-check">
                                        <input type="checkbox" name="customer_accepted_return" value="1" class="form-check-input" required>
                                        <span class="form-check-label">Customer acknowledges return condition and any listed charges.</span>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark mt-3">Complete Return</button>
                        </form>
                    @endif
                </section>
            </div>

            <div class="col-12">
                <section class="panel">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Terms</h2>
                            <p>Contract terms included in the agreement PDF.</p>
                        </div>
                    </div>
                    <p class="text-muted mb-0">{{ $agreement->terms }}</p>
                </section>
            </div>
        </div>
    </div>
@endsection
