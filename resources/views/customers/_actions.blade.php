<div class="table-actions">
    <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary">
        <x-lucide-eye class="w-4 h-4"/>
        View
    </a>
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">
        <x-lucide-pencil class="w-4 h-4"/>
        Edit
    </a>
</div>
