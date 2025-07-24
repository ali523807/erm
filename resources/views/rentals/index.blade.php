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
    <script type="module">
        $(function () {
            new DataTable('#rentals-table').destroy();

            let table = $('#rentals-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('rentals.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
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
                $('#rentalModal .model-title').html("Create New Rental");
                $('.form-select').trigger('change');
                $('#rentalModal').modal('show');
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
                axios.get(route('rentals.edit', {rental: id})).then((response) => {
                    $('#modelHeading').html("Edit Customer");
                    $('#rentalModal').modal('show');

                    var form = $('#rentalForm'); // Adjust the form ID as needed

                    $.each(response.data, function (key, value) {
                        var inputField = form.find('[name="' + key + '"]'); // Scope to form

                        if (inputField.length) {
                            inputField.val(value);
                            $(inputField).trigger('change')
                        }
                    });

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
        });


    </script>

@endpush
