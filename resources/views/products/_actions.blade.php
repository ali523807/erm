<a href="{{ route('products.show', $product) }}" data-toggle="tooltip" data-original-title="View"
   class="btn btn-outline-dark btn-sm">View</a>
<a href="{{ route('products.edit', $product) }}" data-toggle="tooltip" data-original-title="Edit"
   class="edit btn btn-primary btn-sm">Edit</a>
<a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $product->id }}" data-original-title="Delete"
   class="btn btn-danger btn-sm deleteProduct">Delete</a>
