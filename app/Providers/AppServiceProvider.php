<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\LegacyCatalogProduct;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Policies\ClientPaymentPolicy;
use App\Policies\ClientPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LegacyCatalogProductPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SupplierPaymentPolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');

        Gate::policy(ClientPayment::class, ClientPaymentPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(LegacyCatalogProduct::class, LegacyCatalogProductPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(SupplierPayment::class, SupplierPaymentPolicy::class);

        Gate::define('view-client-receivables-aging', fn (User $user): bool => (bool) $user->is_active);
        Gate::define('export-client-receivables-aging-csv', fn (User $user): bool => $user->isAccountant());
    }
}
