<div class="table-actions">
    <a href="{{ route('products.show', $product) }}" data-toggle="tooltip" data-original-title="View"
       class="btn btn-outline-secondary btn-sm">
        <x-lucide-eye class="w-4 h-4"/>
        View
    </a>
    <a href="{{ route('products.edit', $product) }}" data-toggle="tooltip" data-original-title="Edit"
       class="edit btn btn-primary btn-sm">
        <x-lucide-pencil class="w-4 h-4"/>
        Edit
    </a>
    <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $product->id }}" data-original-title="Delete"
       class="btn btn-danger btn-sm deleteProduct">
        <x-lucide-trash-2 class="w-4 h-4"/>
        Delete
    </a>
</div>
