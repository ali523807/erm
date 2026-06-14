<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\Categories\CategoryAttributeTemplateController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerPortal\Auth\CustomerPortalLoginController;
use App\Http\Controllers\CustomerPortal\CustomerPortalController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\CustomerStatementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\DispatchReturnsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentDeliveryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\InvoicePaymentLinkController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Platform\Auth\PlatformLoginController;
use App\Http\Controllers\Platform\CompaniesController as PlatformCompaniesController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Products\ProductDocumentController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\Products\ProductStatusToggleController;
use App\Http\Controllers\QuotesController;
use App\Http\Controllers\RentalAgreementController;
use App\Http\Controllers\Rentals\RentalItemsController;
use App\Http\Controllers\Rentals\RentalItemStatusToggleController;
use App\Http\Controllers\Rentals\RentalsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Settings\CompanySettingsController;
use App\Http\Controllers\Settings\LocationSetupController;
use App\Http\Controllers\Settings\PaymentGatewayController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\TaxProfileController;
use App\Http\Controllers\Settings\TeamController;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Route;

include '_utilities.php';

Route::get('/', function () {
    return view('landing', [
        'plans' => SubscriptionPlan::where('is_active', true)
            ->orderBy('monthly_price')
            ->get(),
    ]);
})->name('landing');

Route::get('platform/login', [PlatformLoginController::class, 'create'])->name('platform.login');
Route::post('platform/login', [PlatformLoginController::class, 'store'])->name('platform.login.store');

Route::get('pay/{token}', [InvoicePaymentLinkController::class, 'show'])->name('payment-links.show');
Route::post('pay/{token}', [InvoicePaymentLinkController::class, 'pay'])->name('payment-links.pay');
Route::get('pay/{token}/receipt', [InvoicePaymentLinkController::class, 'receipt'])->name('payment-links.receipt');

Route::prefix('portal')->as('customer-portal.')->group(function () {
    Route::get('login', [CustomerPortalLoginController::class, 'create'])->name('login');
    Route::post('login', [CustomerPortalLoginController::class, 'store'])->name('login.store');

    Route::middleware(['auth:customer', 'customer.portal'])->group(function () {
        Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('quotes', [CustomerPortalController::class, 'quotes'])->name('quotes');
        Route::patch('quotes/{quote}/status', [CustomerPortalController::class, 'updateQuoteStatus'])->name('quotes.status');
        Route::get('rentals', [CustomerPortalController::class, 'rentals'])->name('rentals');
        Route::get('invoices', [CustomerPortalController::class, 'invoices'])->name('invoices');
        Route::post('invoices/{invoice}/pay', [InvoicePaymentLinkController::class, 'portalPay'])->name('invoices.pay');
        Route::get('documents', [CustomerPortalController::class, 'documents'])->name('documents');
        Route::post('documents', [CustomerPortalController::class, 'storeDocument'])->name('documents.store');
        Route::get('documents/{document}/download', [CustomerPortalController::class, 'downloadDocument'])->name('documents.download');
        Route::post('logout', [CustomerPortalLoginController::class, 'destroy'])->name('logout');
    });
});

Route::group(['middleware' => ['auth:web', 'company.selected']], function () {

    Route::get('/home', DashboardController::class)->middleware('company.permission:dashboard.view')->name('home');

    Route::prefix('lookups')->name('lookups.')->group(function () {
        Route::get('customers', [LookupController::class, 'customers'])
            ->middleware('company.permission:customers.manage,quotes.manage,rentals.manage,payments.manage')
            ->name('customers');
        Route::get('products', [LookupController::class, 'products'])
            ->middleware('company.permission:equipment.manage,quotes.manage,rentals.manage,maintenance.manage,payments.manage')
            ->name('products');
        Route::get('rentals', [LookupController::class, 'rentals'])
            ->middleware('company.permission:rentals.manage,payments.manage')
            ->name('rentals');
        Route::get('team-members', [LookupController::class, 'teamMembers'])
            ->middleware('company.permission:team.manage,maintenance.manage')
            ->name('team-members');
    });

    Route::middleware('company.permission:categories.manage')->group(function () {
        Route::get('categories', [CategoriesController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoriesController::class, 'storeOrUpdate'])->name('categories.storeOrUpdate');
        Route::get('categories/{category}', [CategoriesController::class, 'edit'])->name('categories.edit');
        Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.delete');
        Route::get('categories/{category}/attribute-templates', [CategoryAttributeTemplateController::class, 'index'])->name('categories.attribute-templates.index');
        Route::post('categories/{category}/attribute-templates', [CategoryAttributeTemplateController::class, 'store'])->name('categories.attribute-templates.store');
        Route::put('categories/{category}/attribute-templates/{attributeTemplate}', [CategoryAttributeTemplateController::class, 'update'])->name('categories.attribute-templates.update');
        Route::delete('categories/{category}/attribute-templates/{attributeTemplate}', [CategoryAttributeTemplateController::class, 'destroy'])->name('categories.attribute-templates.destroy');
    });

    Route::middleware('company.permission:equipment.manage')->group(function () {
        Route::get('products', [ProductsController::class, 'index'])->name('products.index');
        Route::get('products/create', [ProductsController::class, 'create'])->name('products.create');
        Route::post('products', [ProductsController::class, 'store'])->name('products.store');
        Route::get('products/{product}', [ProductsController::class, 'show'])->name('products.show');
        Route::get('products/{product}/edit', [ProductsController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [ProductsController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ProductsController::class, 'destroy'])->name('products.delete');
        Route::post('products/{product}/documents', [ProductDocumentController::class, 'store'])->name('products.documents.store');
        Route::get('products/{product}/documents/{document}/download', [ProductDocumentController::class, 'download'])->name('products.documents.download');
        Route::delete('products/{product}/documents/{document}', [ProductDocumentController::class, 'destroy'])->name('products.documents.destroy');
        Route::post('products/{product}/toggleStatus', ProductStatusToggleController::class)->name('products.toggleStatus');
    });

    Route::middleware('company.permission:customers.manage')->group(function () {
        Route::get('customers', [CustomersController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [CustomersController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomersController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}/statement', [CustomerStatementController::class, 'show'])->name('customers.statement.show');
        Route::get('customers/{customer}/statement/print', [CustomerStatementController::class, 'print'])->name('customers.statement.print');
        Route::get('customers/{customer}/statement/download', [CustomerStatementController::class, 'download'])->name('customers.statement.download');
        Route::post('customers/{customer}/statement/send', [DocumentDeliveryController::class, 'statement'])->name('customers.statement.send');
        Route::get('customers/{customer}', [CustomersController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [CustomersController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomersController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomersController::class, 'destroy'])->name('customers.delete');
    });

    Route::middleware('company.permission:rentals.manage')->group(function () {
        Route::get('rentals', [RentalsController::class, 'index'])->name('rentals.index');
        Route::get('rentals/create', [RentalsController::class, 'create'])->name('rentals.create');
        Route::post('rentals', [RentalsController::class, 'store'])->name('rentals.store');
        Route::get('rentals/{rental}', [RentalsController::class, 'show'])->name('rentals.show');
        Route::get('rentals/{rental}/edit', [RentalsController::class, 'edit'])->name('rentals.edit');
        Route::put('rentals/{rental}', [RentalsController::class, 'update'])->name('rentals.update');
        Route::patch('rentals/{rental}/status', [RentalsController::class, 'updateStatus'])->name('rentals.status.update');
        Route::delete('rentals/{rental}', [RentalsController::class, 'destroy'])->name('rentals.delete');
        Route::post('rentals/{rental}/invoice', [InvoiceController::class, 'storeFromRental'])->name('rentals.invoices.store');
        Route::post('rentals/{rental}/agreement', [RentalAgreementController::class, 'storeFromRental'])->name('rentals.agreements.store');
        Route::get('agreements/{agreement}', [RentalAgreementController::class, 'show'])->name('agreements.show');
        Route::get('agreements/{agreement}/print', [RentalAgreementController::class, 'print'])->name('agreements.print');
        Route::get('agreements/{agreement}/download', [RentalAgreementController::class, 'download'])->name('agreements.download');
        Route::post('agreements/{agreement}/checkout', [RentalAgreementController::class, 'checkout'])->name('agreements.checkout');
        Route::post('agreements/{agreement}/return', [RentalAgreementController::class, 'return'])->name('agreements.return');
    });

    Route::middleware('company.permission:invoices.manage')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
        Route::post('invoices/{invoice}/send', [DocumentDeliveryController::class, 'invoice'])->name('invoices.send');
        Route::post('invoices/{invoice}/payment-links', [InvoicePaymentLinkController::class, 'store'])->name('invoices.payment-links.store');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('invoices/{invoice}/credit-notes', [CreditNoteController::class, 'store'])->name('invoices.credit-notes.store');
    });

    Route::middleware('company.permission:payments.manage')->group(function () {
        Route::post('invoices/{invoice}/payments', [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
        Route::get('payments', [InvoicePaymentController::class, 'index'])->name('payments.index');
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::post('expenses/{expense}/add-to-invoice', [ExpenseController::class, 'addToInvoice'])->name('expenses.add-to-invoice');
        Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('deposits', [DepositController::class, 'index'])->name('deposits.index');
        Route::post('rentals/{rental}/deposits/collect', [DepositController::class, 'collect'])->name('rentals.deposits.collect');
        Route::post('rentals/{rental}/deposits/refund', [DepositController::class, 'refund'])->name('rentals.deposits.refund');
        Route::post('rentals/{rental}/deposits/apply', [DepositController::class, 'apply'])->name('rentals.deposits.apply');
        Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('credit-notes.index');
        Route::get('credit-notes/{creditNote}/edit', [CreditNoteController::class, 'edit'])->name('credit-notes.edit');
        Route::put('credit-notes/{creditNote}', [CreditNoteController::class, 'update'])->name('credit-notes.update');
        Route::patch('credit-notes/{creditNote}/void', [CreditNoteController::class, 'void'])->name('credit-notes.void');
        Route::get('credit-notes/{creditNote}', [CreditNoteController::class, 'show'])->name('credit-notes.show');
        Route::get('credit-notes/{creditNote}/print', [CreditNoteController::class, 'print'])->name('credit-notes.print');
        Route::get('credit-notes/{creditNote}/download', [CreditNoteController::class, 'download'])->name('credit-notes.download');
        Route::post('credit-notes/{creditNote}/send', [DocumentDeliveryController::class, 'creditNote'])->name('credit-notes.send');
        Route::get('payments/{payment}/receipt/print', [InvoicePaymentController::class, 'print'])->name('payments.receipt.print');
        Route::get('payments/{payment}/receipt/download', [InvoicePaymentController::class, 'download'])->name('payments.receipt.download');
        Route::post('payments/{payment}/receipt/send', [DocumentDeliveryController::class, 'receipt'])->name('payments.receipt.send');
        Route::patch('payment-links/{paymentLink}/cancel', [InvoicePaymentLinkController::class, 'cancel'])->name('payment-links.cancel');
    });

    Route::middleware('company.permission:dispatch.manage')->group(function () {
        Route::get('rental-items', [RentalItemsController::class, 'index'])->name('rental-items.index');
        Route::post('rental-items/{item}/toggleStatus', RentalItemStatusToggleController::class)->name('rental-items.toggleStatus');
        Route::get('dispatch-returns', DispatchReturnsController::class)->name('dispatch-returns.index');
        Route::patch('dispatch-returns/{rental}/movement-status', [DispatchReturnsController::class, 'updateMovementStatus'])->name('dispatch-returns.status.update');
    });

    Route::get('availability', AvailabilityController::class)->middleware('company.permission:availability.view')->name('availability.index');

    Route::middleware('company.permission:quotes.manage')->group(function () {
        Route::get('quotes', [QuotesController::class, 'index'])->name('quotes.index');
        Route::get('quotes/create', [QuotesController::class, 'create'])->name('quotes.create');
        Route::post('quotes', [QuotesController::class, 'store'])->name('quotes.store');
        Route::get('quotes/{quote}', [QuotesController::class, 'show'])->name('quotes.show');
        Route::get('quotes/{quote}/print', [QuotesController::class, 'print'])->name('quotes.print');
        Route::get('quotes/{quote}/download', [QuotesController::class, 'download'])->name('quotes.download');
        Route::get('quotes/{quote}/edit', [QuotesController::class, 'edit'])->name('quotes.edit');
        Route::put('quotes/{quote}', [QuotesController::class, 'update'])->name('quotes.update');
        Route::patch('quotes/{quote}/status', [QuotesController::class, 'updateStatus'])->name('quotes.status.update');
        Route::post('quotes/{quote}/convert', [QuotesController::class, 'convert'])->name('quotes.convert');
        Route::post('quotes/{quote}/send', [DocumentDeliveryController::class, 'quote'])->name('quotes.send');
        Route::delete('quotes/{quote}', [QuotesController::class, 'destroy'])->name('quotes.destroy');
    });

    Route::middleware('company.permission:maintenance.manage')->group(function () {
        Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::post('maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::put('maintenance/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');
        Route::delete('maintenance/{maintenance}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');
    });

    Route::get('reports', [ReportsController::class, 'index'])->middleware('company.permission:reports.view')->name('reports.index');
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('company.permission:roles.manage')->name('activity-logs.index');

    Route::middleware('company.permission:notifications.manage')->group(function () {
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/generate', [NotificationController::class, 'generate'])->name('notifications.generate');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    });

    Route::middleware('company.permission:documents.manage')->group(function () {
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('document-deliveries', [DocumentDeliveryController::class, 'index'])->name('document-deliveries.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::middleware('company.permission:company.manage')->group(function () {
            Route::get('company', [CompanySettingsController::class, 'edit'])->name('settings.company');
            Route::put('company', [CompanySettingsController::class, 'update'])->name('settings.company.update');
            Route::get('tax-profiles', [TaxProfileController::class, 'index'])->name('settings.tax-profiles');
            Route::post('tax-profiles', [TaxProfileController::class, 'store'])->name('settings.tax-profiles.store');
            Route::put('tax-profiles/{taxProfile}', [TaxProfileController::class, 'update'])->name('settings.tax-profiles.update');
            Route::delete('tax-profiles/{taxProfile}', [TaxProfileController::class, 'destroy'])->name('settings.tax-profiles.destroy');
            Route::get('payment-gateways', [PaymentGatewayController::class, 'index'])->name('settings.payment-gateways');
            Route::put('payment-gateways/{provider}', [PaymentGatewayController::class, 'update'])->name('settings.payment-gateways.update');
        });
        Route::middleware('company.permission:team.manage')->group(function () {
            Route::get('team', [TeamController::class, 'index'])->name('settings.team');
            Route::post('team', [TeamController::class, 'store'])->name('settings.team.store');
            Route::get('team/{user}/edit', [TeamController::class, 'edit'])->name('settings.team.edit');
            Route::patch('team/{user}', [TeamController::class, 'update'])->name('settings.team.update');
            Route::put('team/{user}', [TeamController::class, 'update'])->name('settings.team.details.update');
            Route::delete('team/{user}', [TeamController::class, 'destroy'])->name('settings.team.destroy');
        });
        Route::middleware('company.permission:roles.manage')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('settings.roles');
            Route::post('roles', [RoleController::class, 'store'])->name('settings.roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('settings.roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('settings.roles.destroy');
        });
        Route::middleware('company.permission:locations.manage')->group(function () {
            Route::get('locations', [LocationSetupController::class, 'index'])->name('settings.locations');
            Route::post('locations/branches', [LocationSetupController::class, 'storeBranch'])->name('settings.locations.branches.store');
            Route::put('locations/branches/{branch}', [LocationSetupController::class, 'updateBranch'])->name('settings.locations.branches.update');
            Route::delete('locations/branches/{branch}', [LocationSetupController::class, 'destroyBranch'])->name('settings.locations.branches.destroy');
            Route::post('locations/warehouses', [LocationSetupController::class, 'storeWarehouse'])->name('settings.locations.warehouses.store');
            Route::put('locations/warehouses/{warehouse}', [LocationSetupController::class, 'updateWarehouse'])->name('settings.locations.warehouses.update');
            Route::delete('locations/warehouses/{warehouse}', [LocationSetupController::class, 'destroyWarehouse'])->name('settings.locations.warehouses.destroy');
            Route::post('locations/storage-locations', [LocationSetupController::class, 'storeStorageLocation'])->name('settings.locations.storage-locations.store');
            Route::put('locations/storage-locations/{storageLocation}', [LocationSetupController::class, 'updateStorageLocation'])->name('settings.locations.storage-locations.update');
            Route::delete('locations/storage-locations/{storageLocation}', [LocationSetupController::class, 'destroyStorageLocation'])->name('settings.locations.storage-locations.destroy');
        });
        Route::get('profile', [ProfileController::class, 'profile'])->name('settings.profile');
        Route::post('profile/delete', [ProfileController::class, 'destroy'])->name('settings.profile.delete');
        Route::get('profile/password-update', [ProfileController::class, 'passwordUpdate'])->name('settings.profile.password-update');
        Route::get('profile/appearance', [ProfileController::class, 'appearance'])->name('settings.profile.appearance');
    });

});

Route::group([
    'prefix' => 'platform',
    'as' => 'platform.',
    'middleware' => ['auth:platform', 'platform.admin'],
], function () {
    Route::get('/', PlatformDashboardController::class)->name('dashboard');
    Route::get('companies', [PlatformCompaniesController::class, 'index'])->name('companies.index');
    Route::get('companies/{company}', [PlatformCompaniesController::class, 'show'])->name('companies.show');
    Route::patch('companies/{company}/subscription', [PlatformCompaniesController::class, 'updateSubscription'])->name('companies.subscription.update');
    Route::post('logout', [PlatformLoginController::class, 'destroy'])->name('logout');
});
