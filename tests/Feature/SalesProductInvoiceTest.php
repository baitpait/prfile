<?php

use App\Models\Product;
use App\Models\User;

test('قائمة منتجات المبيعات تظهر للمستخدم النشط', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'viewer']);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('المنتجات');
});

test('صفحة إنشاء فاتورة تعرض حقل بحث الأصناف', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);

    Product::factory()->withIlsPricing()->create(['name' => 'خدمة تصميم']);

    $this->actingAs($user)
        ->get(route('invoices.create'))
        ->assertOk()
        ->assertSee('اكتب للبحث');
});
