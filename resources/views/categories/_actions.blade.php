<a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $category->id }}" data-original-title="Edit"
   class="edit btn btn-dark btn-sm editCategory">Edit</a>
<a href="{{ route('categories.attribute-templates.index', $category) }}" data-toggle="tooltip" data-original-title="Attribute Templates"
   class="btn btn-outline-primary btn-sm">Templates</a>
<a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $category->id }}" data-original-title="Delete"
   class="btn btn-danger btn-sm deleteCategory">Delete</a>
