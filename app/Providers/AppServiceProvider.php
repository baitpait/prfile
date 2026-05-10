<?php

namespace App\Providers;

use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Policies\ClientPaymentPolicy;
use App\Policies\InvoicePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(ClientPayment::class, ClientPaymentPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }
}
