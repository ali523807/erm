<div class="table-actions">
    <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $category->id }}" data-original-title="Edit"
       class="edit btn btn-primary btn-sm editCategory">
        <x-lucide-pencil class="w-4 h-4"/>
        Edit
    </a>
    <a href="{{ route('categories.attribute-templates.index', $category) }}" data-toggle="tooltip" data-original-title="Attribute Templates"
       class="btn btn-outline-primary btn-sm">
        <x-lucide-list-plus class="w-4 h-4"/>
        Templates
    </a>
    <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $category->id }}" data-original-title="Delete"
       class="btn btn-danger btn-sm deleteCategory">
        <x-lucide-trash-2 class="w-4 h-4"/>
        Delete
    </a>
</div>
