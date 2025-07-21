@extends('layouts.app')

@section('title', 'Customers')

@section('content')

    <div class="px-3">
        <div class="d-flex align-items-center justify-content-between">
            <h3>Manage Customers</h3>

            <x-button data-bs-toggle="#customerModal" id="add-customer-btn" color="dark">
                <x-lucide-plus class="w-4 h-4"/>
                <span class="d-none d-sm-inline-block">Add Customers</span>
            </x-button>
        </div>

        <x-card class="mt-3" body-class="px-0 pt-0 pt-sm-3">
            <div class="table-responsive">
                <table id="customers-table" class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </x-card>

       @include('customers._form')

    </div>

@endsection

@push('js')
    <script type="module">
        $(function () {

            var table = $('#customers-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('customers.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'company_name', name: 'company_name'},
                    {data: 'notes', name: 'notes'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
            });

            $('#add-customer-btn').click(function () {
                $('#id').val('');
                $('#customerForm').trigger("reset");
                $('#modelHeading').html("Create New Customer");
                $('#customerModal').modal('show');
            });

            $('#customerForm').on('submit', function (e) {
                e.preventDefault();

                var data = new FormData($('#customerForm')[0]);


                $.easyAjax({
                    url: "{{ route('customers.storeOrUpdate') }}",
                    container: '#customerForm',
                    type: "POST",
                    disableButton: true,
                    blockUI: true,
                    data: data,
                    onComplete: () => {
                        $('#customerModal').modal('hide');
                        $('#modelHeading').html("Create New Customer");
                        $('#customerForm').trigger("reset");
                        table.draw(false);
                    }
                })

            });

            $('body').on('click', '.editCustomer', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                axios.get(route('customers.edit', {customer: id})).then((response) => {
                    $('#modelHeading').html("Edit Customer");
                    $('#customerModal').modal('show');

                    var form = $('#customerForm'); // Adjust the form ID as needed

                    $.each(response.data, function (key, value) {
                        var inputField = form.find('[name="' + key + '"]'); // Scope to form

                        if (inputField.length) {
                            inputField.val(value);
                            $(inputField).trigger('change')
                        }
                    });

                });
            });

            $('body').on('click', '.deleteCustomer', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                $.easyDelete({
                    url: route('customers.delete', {customer: id}),
                    confirmationMessage: 'Do you really want to delete this customer?',
                    onComplete: () => {
                        table.draw(false);
                    }
                })
            });

        });
    </script>
@endpush
