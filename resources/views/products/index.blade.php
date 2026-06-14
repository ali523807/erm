@extends('layouts.app')

@section('title', 'Equipments')

@section('content')
    @php
        $canManageLocations = auth()->user()->hasCurrentCompanyPermission('locations.manage');
    @endphp

    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Equipment register</span>
                <h1>Manage Equipment</h1>
                <p>Track every rentable asset, from heavy machinery and vehicles to tools, kits, furniture, and custom items.</p>
            </div>

            <x-button :link="route('products.create')" color="dark">
                <x-lucide-plus class="w-4 h-4"/>
                <span class="d-none d-sm-inline-block">Add Equipment</span>
            </x-button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <x-card class="mt-3" body-class="px-0 pt-0 pt-sm-3">
            <div class="table-responsive">
                <table id="products-table" class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Asset Status</th>
                        @if($canManageLocations)
                            <th>Location</th>
                        @endif
                        <th>Status</th>
                        <th>Actions</th>
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
            new DataTable('#products-table').destroy();

            const columns = [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'equipment_code', name: 'equipment_code'},
                {data: 'name', name: 'name'},
                {data: 'category_name', name: 'category_name', orderable: false},
                {data: 'asset_status', name: 'asset_status'},
                @if($canManageLocations)
                    {data: 'location_name', name: 'location_name', orderable: false},
                @endif
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ];

            let table = $('#products-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('products.index') }}",
                columns,
                order: [[2, 'asc']],
                buttons: [],
            });

            $('body').on('click', '.deleteProduct', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                $.easyDelete({
                    url: route('products.delete', {product: id}),
                    confirmationMessage: 'Do you really want to delete this product?',
                    onComplete: () => {
                        table.draw(false);
                    }
                })
            });

            $('body').on('click', '.toggleStatus', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var status = $(this).data('status');

                axios.post(route('products.toggleStatus', {product: id}), {status}).then((response) => {
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
