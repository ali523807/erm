<x-modal id="customerModal" title="Create Customer">
    <x-form id="customerForm">
        <x-modal.body class="space-y-3">
            <input type="hidden" name="id" />
            <x-input name="company_name" label="Company Name" placeholder="Enter company name" required="true"/>
            <x-input name="name" label="Contact Person Name" placeholder="Enter contact person name" required="true"/>
            <x-input name="phone" label="Phone No." placeholder="Enter phone number eg:(+971 55xxxxxx)" required="true"/>
            <x-input name="email" label="Email" placeholder="Enter email eg:(erm@xyz.com)" required="true"/>
            <x-textarea name="address" label="Address" placeholder="Enter company address" required="true"/>
            <x-input name="vat_number" label="Tax Registration No." placeholder="Enter TRN number eg:(112233xxxxxx)"/>
            <x-textarea name="notes" label="Note" placeholder="Enter note"/>
        </x-modal.body>

        <x-modal.footer>
            <x-button color="secondary" data-bs-dismiss="modal">Cancel</x-button>
            <x-button color="dark" type="submit">Submit</x-button>
        </x-modal.footer>
    </x-form>
</x-modal>
