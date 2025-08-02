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
                const $option = $(this);
                const id = $option.data('id');
                const newStatus = $option.data('status');
                const badgeClass = $option.data('class');
                axios.post(route('rental-items.toggleStatus', {item: id}), {newStatus}).then((response) => {
                    const $button = $('#statusDropdown' + id);
                    $button.text(newStatus);
                    $button.removeClass(function (index, className) {
                            return (className.match(/text-bg-\S+/g) || []).join(' ');
                        })
                        .addClass(badgeClass)
                        .data('status', newStatus);

                            toastr.success("Status updated!");
                        })
                    .catch((error) => {
                        toastr.error("Failed to update status.");
                    });
            });

        });


    </script>
@endpush
