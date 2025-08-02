@extends('layouts.app')

@section('title', 'Rentals')

@section('content')

    <div class="px-3">
        <div class="d-flex align-items-center justify-content-between">
            <h3>Manage Rentals</h3>

            <x-button data-bs-toggle="#rentalForm" id="add-rental-btn" color="dark">
                <x-lucide-plus class="w-4 h-4"/>
                <span class="d-none d-sm-inline-block">Add Rental</span>
            </x-button>
        </div>

        <x-card class="mt-3" body-class="px-0 pt-0 pt-sm-3">
            <div class="table-responsive">
                <table id="rentals-table" class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Rental ID</th>
                        <th>Customer</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </x-card>

     @include('rentals._form')

    </div>

@endsection

@push('js')
    <script>
        window.equipmentList = @json($equipments);
    </script>
    <script type="module">
        $(function () {
            new DataTable('#rentals-table').destroy();

            let table = $('#rentals-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('rentals.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'rental_id', name: 'rental_id'},
                    {data: 'customer.company_name', name: 'customer.company_name'},
                    {data: 'rental_start_date', name: 'rental_start_date'},
                    {data: 'rental_end_date', name: 'rental_end_date'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                buttons: [],
            });

            $('#add-rental-btn').click(function () {
                $('#id').val('');
                $('#rentalForm').trigger("reset");
                $('#rentalModalTitle').text('Create Rental');
                $('.form-select').trigger('change');
                $('#rentalModal').modal('show');
                rentalItemIndex = 0;
                document.querySelector('#rentalItems').innerHTML = '';
            });

            $('#rentalForm').on('submit', function (e) {
                e.preventDefault();

                var data = new FormData($('#rentalForm')[0]);


                $.easyAjax({
                    url: "{{ route('rentals.storeOrUpdate') }}",
                    container: '#rentalForm',
                    type: "POST",
                    disableButton: true,
                    blockUI: true,
                    data: data,
                    onComplete: () => {
                        $('#rentalModal').modal('hide');
                        $('#modelHeading').html("Create New Rental");
                        $('#rentalForm')[0].reset();

                        table.draw(false);
                    }
                })

            });

            $('body').on('click', '.editRental', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                rentalItemIndex = 0;
                document.querySelector('#rentalItems').innerHTML = '';
                axios.get(route('rentals.edit', { rental: id })).then((response) => {
                    $('#rentalModalTitle').text('Edit/Update Rental');
                    $('#rentalModal').modal('show');

                    const rental = response.data;
                    const form = $('#rentalForm');

                    // Fill main form fields
                    $.each(rental, function (key, value) {
                        if (key === 'rental_items' || key === 'rentalItems') return; // Skip items for now
                        var inputField = form.find('[name="' + key + '"]');
                        if (inputField.length) {
                            inputField.val(value).trigger('change');
                        }
                    });

                    // Clear existing rows
                    $('#rentalItems').empty();

                    // Loop through and append items
                    if (rental.rental_items || rental.rentalItems) {
                        const items = rental.rental_items || rental.rentalItems;

                        items.forEach((item, index) => {
                            console.log(item);
                            addRentalRow(item); // Make sure this adds row to #rentalItems
                            const row = $('#rentalItems').children().last(); // Get the last added item

                            row.find('[name="items[' + index + '][product_id]"]').val(item.product_id).trigger('change');
                            row.find('[name="items[' + index + '][start_date]"]').val(item.start_date);
                            row.find('[name="items[' + index + '][end_date]"]').val(item.end_date);
                            row.find('[name="items[' + index + '][duration_type]"]').val(item.duration_type);
                            row.find('[name="items[' + index + '][no_of_duration]"]').val(item.no_of_duration);
                        });
                    }

                });
            });


            $('body').on('click', '.deleteRental', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                $.easyDelete({
                    url: route('rentals.delete', {rental: id}),
                    confirmationMessage: 'Do you really want to delete this rental?',
                    onComplete: () => {
                        table.draw(false);
                    }
                })
            });

            $('body').on('click', '.toggleStatus', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var status = $(this).data('status');

                axios.post(route('rentals.toggleStatus', {rental: id}), {status}).then((response) => {
                    if (status) {
                        $(this).removeClass('text-bg-danger');
                        $(this).addClass('text-bg-success');
                        $(this).html('Active');

                    } else {
                        $(this).removeClass('text-bg-success');
                        $(this).addClass('text-bg-danger');
                        $(this).html('In Active');
                    }

                    $(this).data('status', !status);

                    toastr.success(response.data.message);
                });
            });


            let rentalIndex = 0;

            const wrapper = document.getElementById('rentalItemsWrapper');
            const addBtn = document.getElementById('addRentalItemBtn');

            let rentalItemIndex = 0; // Global index counter

            function addRentalRow(data = {}) {
                const row = document.createElement('div');
                row.classList.add('border', 'p-3', 'mb-3');

                row.innerHTML = `
        <span class="badge bg-dark">Item #<span class="serial-number fw-bold"></span></span>
        <div class="row align-items-end">
            <div class="col-md-3">
                <label>Equipment</label>
                <select class="form-control" name="items[${rentalItemIndex}][product_id]" required>
                    <option value="">Select</option>
                    ${window.equipmentList.map(e => `<option value="${e.id}" ${data.product_id == e.id ? 'selected' : ''}>${e.name}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-2">
                <label>Start Date</label>
                <input type="date" class="form-control start-date" name="items[${rentalItemIndex}][start_date]" value="${data.start_date || ''}" required>
            </div>
            <div class="col-md-2">
                <label>End Date</label>
                <input type="date" class="form-control end-date" name="items[${rentalItemIndex}][end_date]" value="${data.end_date || ''}" required>
            </div>
            <div class="col-md-2">
                <label>Duration Type</label>
                <select class="form-control duration-type" name="items[${rentalItemIndex}][duration_type]" required>
                    <option value="days" ${data.duration_type === 'days' ? 'selected' : ''}>Days</option>
                    <option value="weeks" ${data.duration_type === 'weeks' ? 'selected' : ''}>Weeks</option>
                    <option value="months" ${data.duration_type === 'months' ? 'selected' : ''}>Months</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>No. of Duration</label>
                <input type="number" class="form-control duration-count" name="items[${rentalItemIndex}][no_of_duration]" value="${data.no_of_duration || ''}" readonly>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm remove-item">X</button>
            </div>
        </div>
    `;

                row.querySelector('.remove-item').addEventListener('click', () => {
                    row.remove();
                    updateSerialNumbers();
                });

                document.querySelector('#rentalItems').appendChild(row);

                addDateListeners(row); // optional, if you calculate duration
                updateSerialNumbers();

                rentalItemIndex++; // Increment for next row
            }



            function addDateListeners(row) {
                const startInput = row.querySelector('.start-date');
                const endInput = row.querySelector('.end-date');
                const typeInput = row.querySelector('.duration-type');
                const output = row.querySelector('.duration-count');

                const calculate = () => {
                    const start = new Date(startInput.value);
                    const end = new Date(endInput.value);
                    const type = typeInput.value;

                    if (!isNaN(start) && !isNaN(end) && end > start) {
                        const ms = end - start;
                        const days = ms / (1000 * 60 * 60 * 24);

                        let duration;
                        if (type === 'days') {
                            duration = Math.ceil(days);
                        } else if (type === 'weeks') {
                            duration = Math.ceil(days / 7);
                        } else if (type === 'months') {
                            duration = Math.ceil(days / 30);
                        }
                        output.value = duration;
                    } else {
                        output.value = '';
                    }
                };

                startInput.addEventListener('change', calculate);
                endInput.addEventListener('change', calculate);
                typeInput.addEventListener('change', calculate);

                // Remove row button
                row.querySelector('.remove-item').addEventListener('click', () => {
                    row.remove();
                    updateSerialNumbers();
                });
            }

            function updateSerialNumbers() {
                const rows = wrapper.querySelectorAll('.border.p-3');
                rows.forEach((row, index) => {
                    // Update visible serial number
                    const serialSpan = row.querySelector('.serial-number');
                    if (serialSpan) {
                        serialSpan.textContent = index + 1;
                    }

                    // Update all input/select name attributes
                    row.querySelectorAll('[name]').forEach(input => {
                        input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`);
                    });
                });
            }

            addBtn.addEventListener('click', () => {
                addRentalRow(rentalIndex++);
            });

            // Add first row on page load
            addRentalRow(rentalIndex++);

        });


    </script>



@endpush
