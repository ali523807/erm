<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\CategoryAttributeTemplate;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductDocument;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Rental;
use App\Models\RentalAgreement;
use App\Models\RentalItem;
use App\Models\StorageLocation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SampleCompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['slug' => 'global-demo-rentals'],
            [
                'name' => 'Global Demo Rentals',
                'email' => 'operations@globaldemo.test',
                'phone' => '+1 555 0188',
                'country' => 'US',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'locale' => 'en',
                'date_format' => 'Y-m-d',
                'measurement_system' => 'metric',
                'tax_name' => 'Sales Tax',
                'tax_number' => 'US-DEMO-1024',
                'default_tax_rate' => 8.2500,
                'tax_inclusive' => false,
                'address_line_1' => '1200 Harbor Industrial Road',
                'city' => 'Houston',
                'state_region' => 'Texas',
                'postal_code' => '77002',
                'status' => 'active',
            ],
        );

        $this->clearTenantData($company);

        $user = User::updateOrCreate(
            ['email' => 'demo@globalrentals.test'],
            [
                'name' => 'Demo Owner',
                'password' => Hash::make('Password123!'),
                'current_company_id' => $company->id,
            ],
        );

        $company->users()->syncWithoutDetaching([
            $user->id => [
                'role' => 'owner',
                'joined_at' => now(),
            ],
        ]);

        $plan = SubscriptionPlan::where('slug', 'business')->first()
            ?? SubscriptionPlan::orderByDesc('monthly_price')->first();

        if ($plan) {
            CompanySubscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'amount' => $plan->monthly_price,
                'currency' => 'USD',
                'current_period_starts_at' => now()->startOfMonth(),
                'current_period_ends_at' => now()->endOfMonth(),
                'next_billing_at' => now()->addMonth()->startOfMonth(),
                'notes' => 'Sample active subscription for platform testing.',
            ]);
        }

        [$houstonBranch, $dubaiBranch] = $this->createLocations($company);
        [$generatorCategory, $vehicleCategory, $eventCategory, $cameraCategory] = $this->createCategories($company);

        $equipment = $this->createEquipment(
            company: $company,
            houstonBranch: $houstonBranch,
            dubaiBranch: $dubaiBranch,
            generatorCategory: $generatorCategory,
            vehicleCategory: $vehicleCategory,
            eventCategory: $eventCategory,
            cameraCategory: $cameraCategory,
        );

        $this->createDocuments($company, $equipment);
        $this->createMaintenance($company, $equipment);

        $customers = $this->createCustomers($company);
        $rentals = $this->createRentals($company, $customers, $equipment);
        $this->createInvoices($company, $rentals);
        $this->createAgreements($company, $rentals);
        $this->createQuotes($company, $customers, $equipment);
    }

    private function clearTenantData(Company $company): void
    {
        InvoicePayment::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Invoice::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        RentalAgreement::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        QuoteItem::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Quote::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        RentalItem::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Rental::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ProductAttribute::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ProductDocument::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        MaintenanceLog::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Product::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        CategoryAttributeTemplate::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Category::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Customer::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        StorageLocation::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Warehouse::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Branch::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        CompanySubscription::where('company_id', $company->id)->delete();
    }

    /**
     * @param  array<string, Product>  $equipment
     */
    private function createMaintenance(Company $company, array $equipment): void
    {
        MaintenanceLog::create([
            'company_id' => $company->id,
            'product_id' => $equipment['generator']->id,
            'type' => 'inspection',
            'title' => 'Monthly load and safety inspection',
            'priority' => 'high',
            'scheduled_at' => now()->addDays(6)->format('Y-m-d'),
            'service_date' => null,
            'service_provider' => 'Houston Internal Workshop',
            'description' => 'Inspect cables, enclosure, grounding kit, oil level, coolant, and load test output.',
            'cost' => 0,
            'downtime_hours' => 2,
            'next_service_due' => now()->addMonth()->format('Y-m-d'),
            'status' => 'scheduled',
            'affects_availability' => true,
        ]);

        MaintenanceLog::create([
            'company_id' => $company->id,
            'product_id' => $equipment['camera']->id,
            'type' => 'repair',
            'title' => 'Camera body service and sensor check',
            'priority' => 'medium',
            'scheduled_at' => now()->subDays(1)->format('Y-m-d'),
            'service_date' => now()->format('Y-m-d'),
            'service_provider' => 'FrameTech Service Center',
            'description' => 'Inspect body mount, clean sensor, test monitor output, and update firmware.',
            'findings' => 'Sensor cleaning required before next dispatch.',
            'recommendations' => 'Keep camera in maintenance until cleaning is complete.',
            'part_used' => 'Sensor cleaning kit',
            'cost' => 185,
            'downtime_hours' => 6,
            'next_service_due' => now()->addMonths(3)->format('Y-m-d'),
            'status' => 'in_progress',
            'affects_availability' => true,
        ]);
    }

    /**
     * @param  array<string, Product>  $equipment
     */
    private function createDocuments(Company $company, array $equipment): void
    {
        $files = [
            [
                'product' => $equipment['generator'],
                'type' => 'certificate',
                'title' => 'Load Test Certificate',
                'original_name' => 'generator-load-test.pdf',
                'file_path' => "equipment/{$equipment['generator']->id}/generator-load-test.pdf",
                'content' => 'Sample load test certificate for demo generator.',
                'mime_type' => 'application/pdf',
                'expires_at' => now()->addMonths(6),
                'notes' => 'Demo certificate used for equipment profile testing.',
            ],
            [
                'product' => $equipment['camera'],
                'type' => 'manual',
                'title' => 'Camera Kit Manual',
                'original_name' => 'camera-kit-manual.pdf',
                'file_path' => "equipment/{$equipment['camera']->id}/camera-kit-manual.pdf",
                'content' => 'Sample camera kit manual for demo equipment.',
                'mime_type' => 'application/pdf',
                'expires_at' => null,
                'notes' => 'Keep with the camera kit dispatch checklist.',
            ],
        ];

        foreach ($files as $file) {
            Storage::disk('public')->put($file['file_path'], $file['content']);

            ProductDocument::create([
                'company_id' => $company->id,
                'product_id' => $file['product']->id,
                'type' => $file['type'],
                'title' => $file['title'],
                'original_name' => $file['original_name'],
                'file_path' => $file['file_path'],
                'disk' => 'public',
                'mime_type' => $file['mime_type'],
                'size' => strlen($file['content']),
                'expires_at' => $file['expires_at'],
                'notes' => $file['notes'],
            ]);
        }
    }

    /**
     * @return array{0: Branch, 1: Branch}
     */
    private function createLocations(Company $company): array
    {
        $houstonBranch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Houston Operations',
            'code' => 'HOU',
            'email' => 'houston@globaldemo.test',
            'phone' => '+1 555 0101',
            'timezone' => 'America/Chicago',
            'address_line_1' => '1200 Harbor Industrial Road',
            'city' => 'Houston',
            'state_region' => 'Texas',
            'postal_code' => '77002',
            'country' => 'US',
            'is_active' => true,
        ]);

        $dubaiBranch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Dubai Events Hub',
            'code' => 'DXB',
            'email' => 'dubai@globaldemo.test',
            'phone' => '+971 4 555 0199',
            'timezone' => 'Asia/Dubai',
            'address_line_1' => 'Warehouse 14, Al Quoz',
            'city' => 'Dubai',
            'state_region' => 'Dubai',
            'country' => 'AE',
            'is_active' => true,
        ]);

        $houstonYard = Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $houstonBranch->id,
            'name' => 'Main Equipment Yard',
            'code' => 'HOU-YARD',
            'type' => 'yard',
            'city' => 'Houston',
            'country' => 'US',
            'is_active' => true,
        ]);

        $houstonWarehouse = Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $houstonBranch->id,
            'name' => 'Climate Controlled Store',
            'code' => 'HOU-CC',
            'type' => 'warehouse',
            'city' => 'Houston',
            'country' => 'US',
            'is_active' => true,
        ]);

        $dubaiWarehouse = Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $dubaiBranch->id,
            'name' => 'Event Dispatch Warehouse',
            'code' => 'DXB-EVENT',
            'type' => 'warehouse',
            'city' => 'Dubai',
            'country' => 'AE',
            'is_active' => true,
        ]);

        foreach ([
            [$houstonYard, 'Zone A - Heavy Equipment', 'A', 'zone', 'North Yard', 1],
            [$houstonYard, 'Service Bay 1', 'SB1', 'bay', 'Workshop', 2],
            [$houstonWarehouse, 'Camera Shelf 3', 'CAM-3', 'shelf', 'Rack C', 3],
            [$dubaiWarehouse, 'Event Rack B', 'EVT-B', 'rack', 'Dispatch Floor', 1],
        ] as [$warehouse, $name, $code, $type, $parentArea, $sortOrder]) {
            StorageLocation::create([
                'company_id' => $company->id,
                'warehouse_id' => $warehouse->id,
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'parent_area' => $parentArea,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
        }

        return [$houstonBranch, $dubaiBranch];
    }

    /**
     * @return array{0: Category, 1: Category, 2: Category, 3: Category}
     */
    private function createCategories(Company $company): array
    {
        $categories = collect([
            ['Generators', 'Power equipment for construction, events, and backup supply.'],
            ['Vehicles', 'Rental vehicles and site transport assets.'],
            ['Event Furniture', 'Tables, chairs, staging, decor, and event setup items.'],
            ['Camera Gear', 'Cameras, lenses, lighting, and production kits.'],
        ])->map(fn (array $category): Category => Category::create([
            'company_id' => $company->id,
            'name' => $category[0],
            'description' => $category[1],
        ]));

        $templateMap = [
            'Generators' => [
                ['Fuel Type', 'select', null, 'Example: Diesel', 'Choose the primary fuel type for operations and delivery planning.', "Diesel\nPetrol\nElectric", 'Diesel', true, 1],
                ['Capacity', 'number', 'kVA', 'Example: 60', 'Rated generator capacity.', null, null, true, 2],
                ['Phase', 'select', null, 'Example: 3 Phase', 'Electrical phase required by the customer site.', "Single Phase\n3 Phase", '3 Phase', false, 3],
            ],
            'Vehicles' => [
                ['Plate Number', 'text', null, 'Example: TX-2048', 'Registration or fleet plate number.', null, null, true, 1],
                ['Seats', 'number', 'seats', 'Example: 5', 'Passenger seating capacity.', null, null, false, 2],
            ],
            'Event Furniture' => [
                ['Material', 'text', null, 'Example: Birch wood', 'Main material or finish customers care about.', null, null, false, 1],
                ['Color', 'text', null, 'Example: White', 'Visible color for event matching.', null, 'White', false, 2],
            ],
            'Camera Gear' => [
                ['Lens Mount', 'select', null, 'Example: RF', 'Lens mount compatibility for camera bodies and lenses.', "RF\nEF\nE\nPL", null, true, 1],
                ['Resolution', 'text', null, 'Example: 6K', 'Capture resolution or sensor capability.', null, null, false, 2],
            ],
        ];

        foreach ($categories as $category) {
            foreach ($templateMap[$category->name] as [$name, $type, $unit, $placeholder, $helpText, $options, $default, $required, $sort]) {
                CategoryAttributeTemplate::create([
                    'company_id' => $company->id,
                    'category_id' => $category->id,
                    'name' => $name,
                    'key' => Str::of($name)->lower()->slug('_'),
                    'type' => $type,
                    'unit' => $unit,
                    'placeholder' => $placeholder,
                    'help_text' => $helpText,
                    'options' => $options ? preg_split('/\r\n|\r|\n/', $options) : null,
                    'default_value' => $default,
                    'is_required' => $required,
                    'sort_order' => $sort,
                ]);
            }
        }

        return [
            $categories->firstWhere('name', 'Generators'),
            $categories->firstWhere('name', 'Vehicles'),
            $categories->firstWhere('name', 'Event Furniture'),
            $categories->firstWhere('name', 'Camera Gear'),
        ];
    }

    /**
     * @return array<string, Product>
     */
    private function createEquipment(Company $company, Branch $houstonBranch, Branch $dubaiBranch, Category $generatorCategory, Category $vehicleCategory, Category $eventCategory, Category $cameraCategory): array
    {
        $houstonYard = Warehouse::where('company_id', $company->id)->where('code', 'HOU-YARD')->firstOrFail();
        $houstonStore = Warehouse::where('company_id', $company->id)->where('code', 'HOU-CC')->firstOrFail();
        $dubaiStore = Warehouse::where('company_id', $company->id)->where('code', 'DXB-EVENT')->firstOrFail();

        $zoneA = StorageLocation::where('company_id', $company->id)->where('code', 'A')->firstOrFail();
        $cameraShelf = StorageLocation::where('company_id', $company->id)->where('code', 'CAM-3')->firstOrFail();
        $eventRack = StorageLocation::where('company_id', $company->id)->where('code', 'EVT-B')->firstOrFail();

        $products = [
            'generator' => Product::create([
                'company_id' => $company->id,
                'equipment_code' => 'GEN-060-001',
                'name' => '60 kVA Silent Generator',
                'description' => 'Towable diesel generator with sound enclosure, cables, and grounding kit.',
                'category_id' => $generatorCategory->id,
                'branch_id' => $houstonBranch->id,
                'warehouse_id' => $houstonYard->id,
                'storage_location_id' => $zoneA->id,
                'serial_number' => 'GEN60-102938',
                'brand' => 'PowerPro',
                'model' => 'Silent 60',
                'status' => 'available',
                'ownership_type' => 'owned',
                'acquisition_date' => now()->subMonths(14),
                'purchase_date' => now()->subMonths(14)->format('Y-m-d'),
                'warranty_expiry' => now()->addMonths(10)->format('Y-m-d'),
                'certificate_expires_at' => now()->addMonths(6),
                'acquisition_cost' => 18500,
                'replacement_value' => 24000,
                'unit_of_measure' => 'unit',
                'default_rate_type' => 'daily',
                'default_rate' => 375,
                'condition' => 'Good',
                'notes' => 'Include load test certificate before dispatch.',
            ]),
            'pickup' => Product::create([
                'company_id' => $company->id,
                'equipment_code' => 'VEH-PU-014',
                'name' => '4x4 Pickup Truck',
                'description' => 'Site-ready pickup truck with tow hitch and safety kit.',
                'category_id' => $vehicleCategory->id,
                'branch_id' => $houstonBranch->id,
                'warehouse_id' => $houstonYard->id,
                'serial_number' => 'VIN-DEMO-014',
                'brand' => 'Ford',
                'model' => 'Ranger',
                'status' => 'on_rent',
                'ownership_type' => 'leased',
                'acquisition_date' => now()->subMonths(8),
                'acquisition_cost' => 0,
                'replacement_value' => 42000,
                'unit_of_measure' => 'unit',
                'default_rate_type' => 'daily',
                'default_rate' => 95,
                'condition' => 'Excellent',
            ]),
            'tables' => Product::create([
                'company_id' => $company->id,
                'equipment_code' => 'EVT-TBL-120',
                'name' => 'Banquet Table Set',
                'description' => 'Set of ten six-foot folding banquet tables with protective covers.',
                'category_id' => $eventCategory->id,
                'branch_id' => $dubaiBranch->id,
                'warehouse_id' => $dubaiStore->id,
                'storage_location_id' => $eventRack->id,
                'status' => 'available',
                'ownership_type' => 'owned',
                'replacement_value' => 1800,
                'unit_of_measure' => 'set',
                'default_rate_type' => 'daily',
                'default_rate' => 120,
                'condition' => 'New',
            ]),
            'camera' => Product::create([
                'company_id' => $company->id,
                'equipment_code' => 'CAM-CINE-006',
                'name' => 'Cinema Camera Kit',
                'description' => 'Full-frame cinema camera body with monitor, cage, batteries, and media case.',
                'category_id' => $cameraCategory->id,
                'branch_id' => $houstonBranch->id,
                'warehouse_id' => $houstonStore->id,
                'storage_location_id' => $cameraShelf->id,
                'serial_number' => 'CINE-6001',
                'brand' => 'Canon',
                'model' => 'CineMax 6K',
                'status' => 'maintenance',
                'ownership_type' => 'owned',
                'acquisition_cost' => 9500,
                'replacement_value' => 12000,
                'unit_of_measure' => 'kit',
                'default_rate_type' => 'daily',
                'default_rate' => 450,
                'condition' => 'Service Due',
                'certificate_expires_at' => now()->addMonths(3),
            ]),
        ];

        $attributes = [
            'generator' => ['Fuel Type' => 'Diesel', 'Capacity' => '60 kVA', 'Phase' => '3 Phase'],
            'pickup' => ['Plate Number' => 'TX-2048', 'Seats' => '5'],
            'tables' => ['Material' => 'Laminate top', 'Color' => 'White'],
            'camera' => ['Lens Mount' => 'RF', 'Resolution' => '6K'],
        ];

        foreach ($attributes as $productKey => $pairs) {
            foreach ($pairs as $key => $value) {
                ProductAttribute::create([
                    'company_id' => $company->id,
                    'product_id' => $products[$productKey]->id,
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }

        return $products;
    }

    /**
     * @return array<int, Customer>
     */
    private function createCustomers(Company $company): array
    {
        return [
            Customer::create([
                'company_id' => $company->id,
                'company_name' => 'Northstar Construction LLC',
                'contact_person' => 'Avery Stone',
                'phone' => '+1 555 0122',
                'email' => 'avery@northstar.test',
                'address' => '4500 Bayport Road, Houston, TX',
                'trade_license_number' => 'TX-CON-7781',
                'vat_number' => 'US-TAX-7781',
                'notes' => 'Prefers morning delivery and consolidated invoices.',
            ]),
            Customer::create([
                'company_id' => $company->id,
                'company_name' => 'Blue Palm Events',
                'contact_person' => 'Mira Haddad',
                'phone' => '+971 50 555 0144',
                'email' => 'mira@bluepalm.test',
                'address' => 'Dubai Marina, Dubai',
                'trade_license_number' => 'DXB-EVT-4420',
                'vat_number' => 'AE-4420',
                'notes' => 'Event furniture and production gear customer.',
            ]),
            Customer::create([
                'company_id' => $company->id,
                'company_name' => 'FrameHouse Studios',
                'contact_person' => 'Leo Grant',
                'phone' => '+1 555 0167',
                'email' => 'leo@framehouse.test',
                'address' => '210 Studio Lane, Austin, TX',
                'notes' => 'Requires camera gear insurance certificate.',
            ]),
        ];
    }

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<string, Product>  $equipment
     * @return array<string, Rental>
     */
    private function createRentals(Company $company, array $customers, array $equipment): array
    {
        $constructionRental = Rental::create([
            'company_id' => $company->id,
            'customer_id' => $customers[0]->id,
            'rental_start_date' => now()->subDays(3)->format('Y-m-d'),
            'rental_end_date' => now()->addDays(4)->format('Y-m-d'),
            'delivery_location' => 'Northstar Project Site, Bayport Road',
            'delivery_date' => now()->subDays(3)->format('Y-m-d'),
            'pickup_date' => now()->addDays(4)->format('Y-m-d'),
            'status' => 'active',
            'notes' => 'Generator must return with cables and grounding kit.',
        ]);

        RentalItem::create([
            'company_id' => $company->id,
            'rental_id' => $constructionRental->id,
            'product_id' => $equipment['generator']->id,
            'start_date' => $constructionRental->rental_start_date,
            'end_date' => $constructionRental->rental_end_date,
            'duration_type' => 'days',
            'no_of_duration' => 7,
            'rate_type' => 'daily',
            'rate' => 375,
            'deposit_amount' => 1000,
            'total_days' => 7,
            'total_price' => 2625,
            'status' => 'on_rent',
        ]);

        RentalItem::create([
            'company_id' => $company->id,
            'rental_id' => $constructionRental->id,
            'product_id' => $equipment['pickup']->id,
            'start_date' => $constructionRental->rental_start_date,
            'end_date' => $constructionRental->rental_end_date,
            'duration_type' => 'days',
            'no_of_duration' => 7,
            'rate_type' => 'daily',
            'rate' => 95,
            'deposit_amount' => 500,
            'total_days' => 7,
            'total_price' => 665,
            'status' => 'on_rent',
        ]);

        $eventRental = Rental::create([
            'company_id' => $company->id,
            'customer_id' => $customers[1]->id,
            'rental_start_date' => now()->addDays(10)->format('Y-m-d'),
            'rental_end_date' => now()->addDays(12)->format('Y-m-d'),
            'delivery_location' => 'Dubai Marina Event Lawn',
            'delivery_date' => now()->addDays(9)->format('Y-m-d'),
            'pickup_date' => now()->addDays(13)->format('Y-m-d'),
            'status' => 'reserved',
            'notes' => 'White event furniture for 120-person corporate dinner.',
        ]);

        RentalItem::create([
            'company_id' => $company->id,
            'rental_id' => $eventRental->id,
            'product_id' => $equipment['tables']->id,
            'start_date' => $eventRental->rental_start_date,
            'end_date' => $eventRental->rental_end_date,
            'duration_type' => 'days',
            'no_of_duration' => 3,
            'rate_type' => 'daily',
            'rate' => 120,
            'deposit_amount' => 300,
            'total_days' => 3,
            'total_price' => 360,
            'status' => 'reserved',
        ]);

        return [
            'construction' => $constructionRental,
            'event' => $eventRental,
        ];
    }

    /**
     * @param  array<string, Rental>  $rentals
     */
    private function createInvoices(Company $company, array $rentals): void
    {
        $constructionInvoice = Invoice::create([
            'company_id' => $company->id,
            'rental_id' => $rentals['construction']->id,
            'customer_id' => $rentals['construction']->customer_id,
            'invoice_number' => 'INV-2026-0001',
            'invoice_date' => now()->subDay()->format('Y-m-d'),
            'due_date' => now()->addDays(13)->format('Y-m-d'),
            'status' => 'issued',
            'subtotal' => 0,
            'deposit_amount' => 0,
            'tax_amount' => 271.43,
            'discount_amount' => 0,
            'damage_amount' => 0,
            'late_fee_amount' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'balance_due' => 0,
            'notes' => 'Deposit collected before generator dispatch.',
        ]);

        InvoicePayment::create([
            'company_id' => $company->id,
            'invoice_id' => $constructionInvoice->id,
            'payment_date' => now()->subDay()->format('Y-m-d'),
            'amount' => 1500,
            'method' => 'bank_transfer',
            'reference' => 'ACH-DEMO-1001',
            'notes' => 'Advance deposit.',
        ]);

        $constructionInvoice->recalculateTotals();
    }

    /**
     * @param  array<string, Rental>  $rentals
     */
    private function createAgreements(Company $company, array $rentals): void
    {
        RentalAgreement::create([
            'company_id' => $company->id,
            'rental_id' => $rentals['construction']->id,
            'agreement_number' => 'AGR-2026-0001',
            'status' => 'checked_out',
            'agreement_date' => now()->subDays(4)->format('Y-m-d'),
            'valid_until' => $rentals['construction']->rental_start_date,
            'signed_by_customer' => 'Sam Carter',
            'signed_at' => now()->subDays(3),
            'terms' => 'Customer accepts responsibility for equipment custody, safe operation, loss, theft, late return, missing accessories, and damage beyond normal wear during the rental period.',
            'checkout_condition' => 'Generator and pickup inspected, fueled, clean, and handed over in working condition.',
            'checkout_accessories' => 'Generator cables, grounding rod, pickup key, registration copy, and safety checklist.',
            'checkout_notes' => 'Customer collected from Houston yard.',
            'checkout_representative' => 'Sam Carter',
            'checkout_id_number' => 'TX-DL-1024',
            'checked_out_at' => now()->subDays(3),
            'customer_accepted_checkout' => true,
            'customer_accepted_return' => false,
        ]);
    }

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<string, Product>  $equipment
     */
    private function createQuotes(Company $company, array $customers, array $equipment): void
    {
        $quote = Quote::create([
            'company_id' => $company->id,
            'customer_id' => $customers[1]->id,
            'quote_number' => 'QTE-2026-0001',
            'quote_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(14)->format('Y-m-d'),
            'rental_start_date' => now()->addDays(20)->format('Y-m-d'),
            'rental_end_date' => now()->addDays(22)->format('Y-m-d'),
            'delivery_location' => 'Blue Palm Events Warehouse, Dubai',
            'status' => 'sent',
            'subtotal' => 360,
            'discount_amount' => 0,
            'tax_amount' => 18,
            'total_amount' => 378,
            'terms' => '50% advance payment required. Customer is responsible for insurance and safe handling during rental.',
            'notes' => 'Sample quote for event equipment booking flow.',
        ]);

        QuoteItem::create([
            'company_id' => $company->id,
            'quote_id' => $quote->id,
            'product_id' => $equipment['tables']->id,
            'start_date' => now()->addDays(20)->format('Y-m-d'),
            'end_date' => now()->addDays(22)->format('Y-m-d'),
            'duration_type' => 'days',
            'quantity' => 1,
            'no_of_duration' => 3,
            'rate' => 120,
            'deposit_amount' => 300,
            'line_total' => 360,
            'notes' => 'Reserve the complete folding table package.',
        ]);
    }
}
