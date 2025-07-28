@extends('layouts.app')

@section('title', 'Rental Items')

@section('content')

    <div class="px-3">
        <div class="d-flex align-items-center justify-content-between">
            <h3>Rental Items</h3>
        </div>

        <x-card class="mt-3" body-class="px-0 pt-0 pt-sm-3">
            <div class="table-responsive">
                <table id="rental-items-table" class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Rental ID</th>
                        <th>Equipment Name</th>
                        <th>Customer</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

@endsection

@push('js')
    <script type="module">
        $(function () {
            new DataTable('#rental-items-table').destroy();

            let table = $('#rental-items-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('rental-items.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'rental_id', name: 'rental_id'},
                    {data: 'product.name', name: 'product.name'},
                    {data: 'customer', name: 'customer'},
                    {data: 'start_date', name: 'start_date'},
                    {data: 'end_date', name: 'end_date'},
                    {data: 'duration', name: 'duration'},
                    {data: 'status', name: 'status'},
                ],
                buttons: [],
            });

            $('body').on('click', '.toggleStatus', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var status = $(this).data('status');

                axios.post(route('rental-items.toggleStatus', {item: id}), {status}).then((response) => {
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
