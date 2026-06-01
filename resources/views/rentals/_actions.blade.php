<div class="table-actions">
    <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $rental->id }}" data-original-title="Edit"
       class="edit btn btn-primary btn-sm editRental">
        <x-lucide-pencil class="w-4 h-4"/>
        Edit
    </a>
    <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $rental->id }}" data-original-title="Delete"
       class="btn btn-danger btn-sm deleteRental">
        <x-lucide-trash-2 class="w-4 h-4"/>
        Delete
    </a>
</div>
