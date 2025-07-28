<x-modal id="rentalModal" size="xl">
    <x-slot name="title">
        <span id="rentalModalTitle">Create Rental</span>
    </x-slot>
    <x-form id="rentalForm">

        <x-modal.body class="space-y-3">
            <input type="hidden" name="id" id="id">
            <div class="row">
                <div class="col-lg-6">
                    <x-select id="customer_id" name="customer_id" label="Select Customer" required
                              placeholder="Select Customer">
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->company_name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="col-lg-6">
                    <x-input name="delivery_location" id="delivery_location" label="Delivery Location" placeholder="Enter Delivery Location" required />
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <x-input name="rental_start_date" id="rental_start_date" label="Rental Start Date" type="date" required />
                </div>
                <div class="col-lg-6">
                    <x-input name="rental_end_date" id="rental_end_date" label="Rental End Date" type="date" required />
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <x-input name="delivery_date" id="delivery_date" label="Delivery Date" type="date" />
                </div>
                <div class="col-lg-6">
                    <x-input name="pickup_date" id="pickup_date" label="Pickup Date" type="date" />
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <x-textarea name="notes" id="notes" label="Notes" placeholder="Optional notes" />
                </div>
            </div>

            <!-- Rental Items Section -->
            <div id="rentalItemsWrapper" class="mb-4">
                <label class="form-label fw-bold mb-2">Rental Items</label>

                <div id="rentalItems">
                    <!-- Rental item rows will be dynamically added here -->
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mt-2 mb-2" id="addRentalItemBtn">
                    <i class="bi bi-plus-circle"></i> Add Item
                </button>
            </div>

        </x-modal.body>

        <x-modal.footer>
            <x-button color="secondary" data-bs-dismiss="modal">Cancel</x-button>
            <x-button color="dark" type="submit">Submit</x-button>
        </x-modal.footer>

    </x-form>
</x-modal>
