<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // مسح الملاحظات التقنية المنقولة من النظام القديم مثل "CreditLimitAmount=0 CreditLimitPeriod=0"
        DB::table('clients')
            ->where('notes', 'like', '%CreditLimit%')
            ->update(['notes' => null]);
    }

    public function down(): void {}
};
