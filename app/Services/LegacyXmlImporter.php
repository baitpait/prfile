<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use SimpleXMLElement;
use Throwable;

final class LegacyXmlImporter
{
    private string $dir;

    /** @var array<int, int> */
    private array $staffIdToUserId = [];

    /** @var array<string, int> */
    private array $clientNumberToId = [];

    /** @var array<string, int> key = ClientNumber."\x1f".normalizedBusinessName */
    private array $clientKeyToId = [];

    /** @var array<string, int> */
    private array $supplierNumberToId = [];

    /** @var array<string, list<SimpleXMLElement>> */
    private array $invoiceLinesByNo = [];

    /** @var array<string, list<SimpleXMLElement>> */
    private array $purchaseOrderLinesByNo = [];

    /** @var array<string, int> مفتاح: اسم الجهة مُطبّع — يُطابق حقل Vendor في Incomes.xml مع أسماء العملاء */
    private array $normalizedVendorToClientId = [];

    private ?int $legacyIncomePoolClientId = null;

    /** @var callable(string): void */
    private $log;

    public function __construct(?callable $log = null)
    {
        $this->log = $log ?? static function (string $m): void {};
    }

    /**
     * @return array<string, int>
     */
    public function import(string $absoluteDirectoryPath, bool $fresh): array
    {
        $real = realpath($absoluteDirectoryPath);
        if ($real === false || ! is_dir($real)) {
            throw new \InvalidArgumentException('مسار مجلد XML غير صالح أو غير موجود.');
        }
        $this->dir = $real;

        if ($fresh) {
            $this->truncateBusinessTables();
        } elseif ($this->hasXmlImportMarkers()) {
            throw new \RuntimeException('يوجد استيراد XML سابق. أعد التشغيل مع --fresh أو امسح البيانات يدوياً.');
        }

        $this->invoiceLinesByNo = $this->groupItemsByKey('Invoice_items.xml', 'InvoiceItem', 'no');
        $this->purchaseOrderLinesByNo = $this->groupItemsByKey('Purchase_order_items.xml', 'PurchaseOrderItem', 'no');

        $counts = [
            'users' => 0,
            'clients' => 0,
            'client_contacts' => 0,
            'suppliers' => 0,
            'supplier_contacts' => 0,
            'invoices' => 0,
            'invoice_lines' => 0,
            'client_payments' => 0,
            'purchase_orders' => 0,
            'purchase_order_lines' => 0,
            'supplier_payments' => 0,
            'expenses' => 0,
            'legacy_income_xml_as_client_payments' => 0,
            'legacy_catalog_products' => 0,
            'legacy_catalog_projects' => 0,
            'suppliers_auto_created' => 0,
        ];

        DB::transaction(function () use (&$counts): void {
            $this->importStaffs($counts);
            $this->importClients($counts);
            $this->importClientContacts($counts);
            $this->importSuppliers($counts);
            $this->importSupplierContacts($counts);
            $this->importInvoicesAndLinesAndPayments($counts);
            $this->importPurchaseOrdersAndLinesAndPayments($counts);
            $this->importExpenses($counts);
            $this->importIncomesXmlAsClientPayments($counts);
            $this->importLegacyCatalog($counts);
            $this->recordImportAudit();
        });

        return $counts;
    }

    private function hasXmlImportMarkers(): bool
    {
        return DB::table('clients')->where('legacy_match_key', 'like', 'xml:c:%')->exists();
    }

    private function truncateBusinessTables(): void
    {
        ($this->log)('تفريغ الجداول ذات الصلة...');

        Schema::disableForeignKeyConstraints();
        try {
            foreach ([
                'import_audit',
                'legacy_catalog_projects',
                'legacy_catalog_products',
                'income_entries',
                'expenses',
                'supplier_payments',
                'client_payments',
                'purchase_order_lines',
                'purchase_orders',
                'invoice_lines',
                'invoices',
                'supplier_contacts',
                'suppliers',
                'client_contacts',
                'clients',
            ] as $table) {
                DB::table($table)->delete();
            }
            DB::table('users')->where('email', 'like', 'staff%@profilemedia.local')->delete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function recordImportAudit(): void
    {
        DB::table('import_audit')->insert([
            'batch_name' => 'legacy_xml',
            'source' => 'xml_directory',
            'notes' => 'استيراد من مجلد: '.$this->dir,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, list<SimpleXMLElement>>
     */
    private function groupItemsByKey(string $file, string $elementName, string $noTag): array
    {
        $xml = $this->loadXml($file);
        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }
        $map = [];
        foreach ($xml->{$elementName} as $row) {
            $key = trim((string) $row->{$noTag});
            if ($key === '') {
                continue;
            }
            $map[$key][] = $row;
        }

        return $map;
    }

    private function loadXml(string $filename): ?SimpleXMLElement
    {
        $path = $this->dir.DIRECTORY_SEPARATOR.$filename;
        if (! is_readable($path)) {
            return null;
        }
        libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_file($path);
        } catch (Throwable) {
            $xml = false;
        }
        if ($xml === false) {
            ($this->log)("تحذير: تعذّر قراءة {$filename}");

            return null;
        }

        return $xml;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importStaffs(array &$counts): void
    {
        $xml = $this->loadXml('Staffs.xml');
        if (! $xml instanceof SimpleXMLElement) {
            ($this->log)('تخطّي Staffs.xml (غير موجود).');

            return;
        }

        foreach ($xml->Staff as $s) {
            $sid = (int) $s->ID;
            $email = trim((string) $s->Email);
            if ($email === '') {
                $email = 'staff'.$sid.'@profilemedia.local';
            }

            $existing = DB::table('users')->where('email', $email)->value('id');
            if ($existing) {
                $this->staffIdToUserId[$sid] = (int) $existing;

                continue;
            }

            $uid = DB::table('users')->insertGetId([
                'full_name' => trim((string) $s->FullName) ?: 'موظف '.$sid,
                'email' => $email,
                'password' => Hash::make('change-me-'.$sid),
                'role' => 'viewer',
                'is_active' => (bool) (int) $s->Active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->staffIdToUserId[$sid] = $uid;
            $counts['users']++;
        }
        ($this->log)("موظفون (مستخدمون): {$counts['users']}");
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importClients(array &$counts): void
    {
        $xml = $this->loadXml('Clients.xml');
        if (! $xml instanceof SimpleXMLElement) {
            throw new \RuntimeException('ملف Clients.xml مطلوب.');
        }

        $ordinal = 0;
        foreach ($xml->Client as $c) {
            $ordinal++;
            $num = trim((string) $c->ClientNumber);
            if ($num === '') {
                continue;
            }
            $matchKey = 'xml:c:'.$num.':'.$ordinal;

            $assigned = (int) $c->StaffID;
            $assignedUserId = $assigned > 0 ? ($this->staffIdToUserId[$assigned] ?? null) : null;

            $id = DB::table('clients')->insertGetId([
                'legacy_number' => $num,
                'legacy_match_key' => $matchKey,
                'business_name' => $this->blankToNull((string) $c->BusinessName),
                'first_name' => $this->blankToNull((string) $c->FirstName),
                'last_name' => $this->blankToNull((string) $c->LastName),
                'email' => $this->blankToNull((string) $c->Email),
                'phone_primary' => $this->blankToNull((string) $c->Phone1),
                'phone_secondary' => $this->blankToNull((string) $c->Phone2),
                'address_line1' => $this->blankToNull((string) $c->Address1),
                'address_line2' => $this->blankToNull((string) $c->Address2),
                'city' => $this->blankToNull((string) $c->City),
                'state_region' => $this->blankToNull((string) $c->State),
                'postal_code' => $this->blankToNull((string) $c->PostalCode),
                'country_code' => $this->blankToNull((string) $c->CountryCode),
                'notes' => $this->buildClientNotes($c),
                'assigned_user_id' => $assignedUserId,
                'source_row_json' => $this->elementToJson($c),
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $bizKey = $this->clientBusinessKey($num, (string) $c->BusinessName);
            $this->clientKeyToId[$bizKey] = $id;
            if (! isset($this->clientNumberToId[$num])) {
                $this->clientNumberToId[$num] = $id;
            }
            $counts['clients']++;
        }
        ($this->log)("عملاء: {$counts['clients']}");
    }

    private function buildClientNotes(SimpleXMLElement $c): ?string
    {
        $parts = [];
        foreach (['FollowUpStatus', 'Category', 'BusinessNumber1', 'BusinessNumber2', 'NationalID'] as $f) {
            $v = trim((string) $c->{$f});
            if ($v !== '' && $v !== '0000-00-00') {
                $parts[] = $f.': '.$v;
            }
        }
        $credit = trim((string) $c->CreditLimitAmount);
        if ($credit !== '' && $credit !== '0') {
            $parts[] = 'CreditLimitAmount: '.$credit;
        }

        return $parts === [] ? null : implode("\n", $parts);
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importClientContacts(array &$counts): void
    {
        $xml = $this->loadXml('Client_contacts.xml');
        if (! $xml instanceof SimpleXMLElement) {
            return;
        }

        foreach ($xml->ClientContact as $row) {
            $num = trim((string) $row->ClientNumber);
            $bizKey = $this->clientBusinessKey($num, (string) $row->BusinessName);
            $clientId = $this->clientKeyToId[$bizKey] ?? $this->clientNumberToId[$num] ?? null;
            if (! $clientId) {
                continue;
            }

            DB::table('client_contacts')->insert([
                'client_id' => $clientId,
                'label' => null,
                'first_name' => $this->blankToNull((string) $row->FirstName),
                'last_name' => $this->blankToNull((string) $row->LastName),
                'email' => $this->blankToNull((string) $row->Email),
                'phone_home' => $this->blankToNull((string) $row->HomePhone),
                'phone_mobile' => $this->blankToNull((string) $row->mobile),
                'notes' => $this->blankToNull((string) $row->BusinessName),
                'source_row_json' => $this->elementToJson($row),
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counts['client_contacts']++;
        }
        ($this->log)("جهات اتصال عملاء: {$counts['client_contacts']}");
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importSuppliers(array &$counts): void
    {
        $xml = $this->loadXml('Suppliers.xml');
        if (! $xml instanceof SimpleXMLElement) {
            throw new \RuntimeException('ملف Suppliers.xml مطلوب.');
        }

        foreach ($xml->Supplier as $s) {
            $num = trim((string) $s->SupplierNumber);
            if ($num === '') {
                continue;
            }

            $assigned = (int) $s->StaffID;
            $assignedUserId = $assigned > 0 ? ($this->staffIdToUserId[$assigned] ?? null) : null;

            $bnNotes = [];
            foreach (['bn1_label', 'bn1', 'bn2_label', 'bn2'] as $f) {
                $v = trim((string) $s->{$f});
                if ($v !== '') {
                    $bnNotes[] = $f.': '.$v;
                }
            }

            $id = DB::table('suppliers')->insertGetId([
                'legacy_number' => $num,
                'business_name' => $this->blankToNull((string) $s->BusinessName),
                'first_name' => $this->blankToNull((string) $s->FirstName),
                'last_name' => $this->blankToNull((string) $s->LastName),
                'email' => $this->blankToNull((string) $s->Email),
                'phone_primary' => $this->blankToNull((string) $s->Phone1),
                'phone_secondary' => $this->blankToNull((string) $s->Phone2),
                'address_line1' => $this->blankToNull((string) $s->Address1),
                'address_line2' => $this->blankToNull((string) $s->Address2),
                'city' => null,
                'state_region' => $this->blankToNull((string) $s->State),
                'postal_code' => $this->blankToNull((string) $s->PostalCode),
                'country_code' => $this->blankToNull((string) $s->CountryCode),
                'notes' => $bnNotes === [] ? null : implode("\n", $bnNotes),
                'assigned_user_id' => $assignedUserId,
                'source_row_json' => $this->elementToJson($s),
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->supplierNumberToId[$num] = $id;
            $counts['suppliers']++;
        }
        ($this->log)("موردون: {$counts['suppliers']}");
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importSupplierContacts(array &$counts): void
    {
        $xml = $this->loadXml('Supplier_contacts.xml');
        if (! $xml instanceof SimpleXMLElement) {
            return;
        }

        foreach ($xml->SupplierContact as $row) {
            $num = trim((string) $row->supplier_number);
            $sid = $this->supplierNumberToId[$num] ?? null;
            if (! $sid) {
                continue;
            }

            DB::table('supplier_contacts')->insert([
                'supplier_id' => $sid,
                'label' => null,
                'first_name' => $this->blankToNull((string) $row->first_name),
                'last_name' => $this->blankToNull((string) $row->last_name),
                'email' => $this->blankToNull((string) $row->email),
                'phone_home' => $this->blankToNull((string) $row->home_phone),
                'phone_mobile' => $this->blankToNull((string) $row->mobile),
                'notes' => $this->blankToNull((string) $row->business_name),
                'source_row_json' => $this->elementToJson($row),
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counts['supplier_contacts']++;
        }
        ($this->log)("جهات اتصال موردين: {$counts['supplier_contacts']}");
    }

    /**
     * عند الإشارة إلى مورد غير موجود في Suppliers.xml نُنشئ سجلاً minimalاً من لقطة أمر الشراء.
     *
     * @param  array<string, int>  $counts
     */
    private function createSupplierFromPurchaseOrderRow(string $supNo, SimpleXMLElement $po, array &$counts): ?int
    {
        $existing = DB::table('suppliers')->where('legacy_number', $supNo)->whereNull('deleted_at')->value('id');
        if ($existing) {
            $this->supplierNumberToId[$supNo] = (int) $existing;

            return (int) $existing;
        }

        $biz = $this->blankToNull((string) $po->SupplierBusinessName);
        $fn = $this->blankToNull((string) $po->SupplierFirstName);
        $ln = $this->blankToNull((string) $po->SupplierLastName);

        try {
            $id = DB::table('suppliers')->insertGetId([
                'legacy_number' => $supNo,
                'business_name' => $biz ?? ('مورد (مرجع XML '.$supNo.')'),
                'first_name' => $fn,
                'last_name' => $ln,
                'email' => $this->blankToNull((string) $po->SupplierEmail),
                'phone_primary' => null,
                'phone_secondary' => null,
                'address_line1' => $this->blankToNull((string) $po->SupplierAddress1),
                'address_line2' => $this->blankToNull((string) $po->SupplierAddress2),
                'city' => $this->blankToNull((string) $po->SupplierCity),
                'state_region' => $this->blankToNull((string) $po->SupplierState),
                'postal_code' => $this->blankToNull((string) $po->SupplierPostalCode),
                'country_code' => $this->blankToNull((string) $po->SupplierCountryCode),
                'notes' => 'أُنشئ تلقائياً أثناء استيراد XML لأن المورد غير موجود في Suppliers.xml.',
                'assigned_user_id' => null,
                'source_row_json' => $this->elementToJson($po),
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable) {
            $retry = DB::table('suppliers')->where('legacy_number', $supNo)->whereNull('deleted_at')->value('id');

            return $retry ? (int) $retry : null;
        }

        $this->supplierNumberToId[$supNo] = $id;
        $counts['suppliers_auto_created']++;
        ($this->log)("تم إنشاء مورد تلقائياً (غير موجود في Suppliers.xml): رقم {$supNo}");

        return $id;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importInvoicesAndLinesAndPayments(array &$counts): void
    {
        $xml = $this->loadXml('Invoices.xml');
        if (! $xml instanceof SimpleXMLElement) {
            throw new \RuntimeException('ملف Invoices.xml مطلوب.');
        }

        $lineBuffer = [];
        foreach ($xml->Invoice as $inv) {
            $xmlId = (int) $inv->ID;
            $clientNo = trim((string) $inv->ClientNo);
            $clientId = $this->clientNumberToId[$clientNo] ?? null;
            if (! $clientId) {
                ($this->log)("تحذير: تخطّي فاتورة ID={$xmlId} — عميل غير معروف ClientNo={$clientNo}");

                continue;
            }

            $documentDate = $this->parseDate((string) $inv->Date);
            $issueDate = $this->parseDate((string) $inv->IssueDate) ?? $documentDate;
            $dueAfter = (int) $inv->DueAfter;
            $dueDate = $this->addDays($issueDate ?? $documentDate, $dueAfter);

            $legacyNo = trim((string) $inv->InvoiceNo);
            if ($legacyNo === '') {
                $legacyNo = 'xml-inv-'.$xmlId;
            }

            $staffId = (int) $inv->StaffID;
            $recordedBy = $staffId > 0 ? ($this->staffIdToUserId[$staffId] ?? null) : null;

            $discount = $this->decimalString($inv->SummaryDiscount, '0');
            $total = $this->decimalString($inv->SummaryTotal, '0');

            $notes = [];
            $n = trim((string) $inv->Notes);
            if ($n !== '') {
                $notes[] = $n;
            }
            $notes[] = 'حالة الدفع (XML): '.trim((string) $inv->PaymentStatus);

            DB::table('invoices')->insert([
                'id' => $xmlId,
                'client_id' => $clientId,
                'legacy_invoice_no' => $legacyNo,
                'document_date' => $documentDate ?? now()->toDateString(),
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'currency_code' => strtoupper(trim((string) $inv->CurrencyCode) ?: 'ILS'),
                'discount_amount' => $discount,
                'total_amount' => $total,
                'notes' => implode("\n", $notes),
                'status' => 'issued',
                'recorded_by_user_id' => $recordedBy,
                'source_row_json' => $this->elementToJson($inv),
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counts['invoices']++;

            $invNoKey = trim((string) $inv->InvoiceNo);
            $lines = $this->invoiceLinesByNo[$invNoKey] ?? [];
            $order = 0;
            foreach ($lines as $item) {
                $lineBuffer[] = [
                    'invoice_id' => $xmlId,
                    'product_id' => null,
                    'line_order' => $order++,
                    'title' => mb_substr(trim((string) $item->item), 0, 255) ?: 'بند',
                    'description' => $this->blankToNull((string) $item->description),
                    'unit_price' => $this->decimalString($item->unit_price, '0'),
                    'quantity' => $this->decimalString($item->quantity, '1'),
                    'line_total' => $this->decimalString($item->subtotal, '0'),
                    'source_row_json' => $this->elementToJson($item),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $counts['invoice_lines']++;
            }

            $paid = $this->decimalString($inv->SummaryPaid, '0');
            if (bccomp($paid, '0', 4) === 1) {
                $paidAt = $this->parseDateTime((string) $inv->LastSent, $issueDate ?? $documentDate);
                DB::table('client_payments')->insert([
                    'client_id' => $clientId,
                    'amount' => $paid,
                    'currency_code' => strtoupper(trim((string) $inv->CurrencyCode) ?: 'ILS'),
                    'paid_at' => $paidAt,
                    'method' => null,
                    'bank_reference' => 'xml-invoice:'.$xmlId,
                    'notes' => 'مُشتق من SummaryPaid في النسخة الاحتياطية XML',
                    'recorded_by_user_id' => $recordedBy,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $counts['client_payments']++;
            }

            if (count($lineBuffer) >= 200) {
                DB::table('invoice_lines')->insert($lineBuffer);
                $lineBuffer = [];
            }
        }
        if ($lineBuffer !== []) {
            DB::table('invoice_lines')->insert($lineBuffer);
        }

        ($this->log)("فواتير: {$counts['invoices']} — بنود: {$counts['invoice_lines']} — دفعات عملاء: {$counts['client_payments']}");
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importPurchaseOrdersAndLinesAndPayments(array &$counts): void
    {
        $xml = $this->loadXml('Purchase_orders.xml');
        if (! $xml instanceof SimpleXMLElement) {
            ($this->log)('تخطّي Purchase_orders.xml (غير موجود).');

            return;
        }

        $lineBuffer = [];
        foreach ($xml->PurchaseOrder as $po) {
            $xmlId = (int) $po->ID;
            $supNo = trim((string) $po->SupplierNo);
            if ($supNo === '') {
                ($this->log)("تحذير: تخطّي أمر شراء ID={$xmlId} — رقم مورد فارغ");

                continue;
            }

            $supplierId = $this->supplierNumberToId[$supNo] ?? null;
            if (! $supplierId) {
                $supplierId = $this->createSupplierFromPurchaseOrderRow($supNo, $po, $counts);
            }
            if (! $supplierId) {
                ($this->log)("تحذير: تعذّر إنشاء مورد لأمر شراء ID={$xmlId} — SupplierNo={$supNo}");

                continue;
            }

            $documentDate = $this->parseDate((string) $po->Date);
            $dueAfter = (int) $po->DueAfter;
            $dueDate = $this->addDays($documentDate, $dueAfter);

            $legacyNo = trim((string) $po->PurchaseOrderNo);
            if ($legacyNo === '') {
                $legacyNo = 'xml-po-'.$xmlId;
            }

            $staffId = (int) $po->StaffID;
            $recordedBy = $staffId > 0 ? ($this->staffIdToUserId[$staffId] ?? null) : null;

            $discount = $this->decimalString($po->SummaryDiscount, '0');
            $total = $this->decimalString($po->SummaryTotal, '0');

            $notes = [];
            $n = trim((string) $po->Notes);
            if ($n !== '') {
                $notes[] = $n;
            }
            $notes[] = 'حالة الدفع (XML): '.trim((string) $po->PaymentStatus);

            DB::table('purchase_orders')->insert([
                'id' => $xmlId,
                'supplier_id' => $supplierId,
                'legacy_po_no' => $legacyNo,
                'document_date' => $documentDate ?? now()->toDateString(),
                'due_date' => $dueDate,
                'currency_code' => strtoupper(trim((string) $po->CurrencyCode) ?: 'ILS'),
                'discount_amount' => $discount,
                'total_amount' => $total,
                'notes' => implode("\n", $notes),
                'status' => 'issued',
                'recorded_by_user_id' => $recordedBy,
                'source_row_json' => $this->elementToJson($po),
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counts['purchase_orders']++;

            $poNoKey = trim((string) $po->PurchaseOrderNo);
            $lines = $this->purchaseOrderLinesByNo[$poNoKey] ?? [];
            $order = 0;
            foreach ($lines as $item) {
                $lineBuffer[] = [
                    'purchase_order_id' => $xmlId,
                    'line_order' => $order++,
                    'title' => mb_substr(trim((string) $item->item), 0, 255) ?: 'بند',
                    'description' => $this->blankToNull((string) $item->description),
                    'unit_price' => $this->decimalString($item->unit_price, '0'),
                    'quantity' => $this->decimalString($item->quantity, '1'),
                    'line_total' => $this->decimalString($item->subtotal, '0'),
                    'source_row_json' => $this->elementToJson($item),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $counts['purchase_order_lines']++;
            }

            $paid = $this->decimalString($po->SummaryPaid, '0');
            if (bccomp($paid, '0', 4) === 1) {
                $paidAt = $this->parseDateTime((string) $po->LastSent, $documentDate);
                DB::table('supplier_payments')->insert([
                    'supplier_id' => $supplierId,
                    'amount' => $paid,
                    'currency_code' => strtoupper(trim((string) $po->CurrencyCode) ?: 'ILS'),
                    'paid_at' => $paidAt,
                    'method' => null,
                    'bank_reference' => 'xml-po:'.$xmlId,
                    'notes' => 'مُشتق من SummaryPaid في النسخة الاحتياطية XML',
                    'recorded_by_user_id' => $recordedBy,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $counts['supplier_payments']++;
            }

            if (count($lineBuffer) >= 200) {
                DB::table('purchase_order_lines')->insert($lineBuffer);
                $lineBuffer = [];
            }
        }
        if ($lineBuffer !== []) {
            DB::table('purchase_order_lines')->insert($lineBuffer);
        }

        ($this->log)("أوامر شراء: {$counts['purchase_orders']} — بنود: {$counts['purchase_order_lines']} — دفعات موردين: {$counts['supplier_payments']} — موردون أُنشئوا تلقائياً: {$counts['suppliers_auto_created']}");
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importExpenses(array &$counts): void
    {
        $xml = $this->loadXml('Expenses.xml');
        if (! $xml instanceof SimpleXMLElement) {
            return;
        }

        foreach ($xml->Expense as $row) {
            $vendor = trim((string) $row->Vendor);
            $note = trim((string) $row->Note);
            $desc = $vendor !== '' ? $vendor : 'مصروف';
            if ($note !== '') {
                $desc .= ' — '.$note;
            }
            $cat = trim((string) $row->Category);
            if ($cat !== '') {
                $desc .= ' ['.$cat.']';
            }

            $staffId = (int) $row->StaffID;
            $recordedBy = $staffId > 0 ? ($this->staffIdToUserId[$staffId] ?? null) : null;

            DB::table('expenses')->insert([
                'description' => mb_substr($desc, 0, 255),
                'amount' => $this->decimalString($row->Amount, '0'),
                'currency_code' => strtoupper(trim((string) $row->CurrencyCode) ?: 'ILS'),
                'expense_date' => $this->parseDate((string) $row->Date) ?? now()->toDateString(),
                'notes' => $note !== '' ? $note : null,
                'recorded_by_user_id' => $recordedBy,
                'source_row_json' => $this->elementToJson($row),
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counts['expenses']++;
        }
        ($this->log)("مصروفات: {$counts['expenses']}");
    }

    /**
     * Incomes.xml في النسخة الاحتياطية = تسجيلات نقد واردة؛ تُوحَّد مع دفعات العملاء (جدول client_payments).
     * يُطابق حقل Vendor مع اسم النشاط التجاري أو الاسم الكامل للعميل؛ إن تعذّر الربط تُسجَّل تحت عميل تجميعي.
     *
     * @param  array<string, int>  $counts
     */
    private function importIncomesXmlAsClientPayments(array &$counts): void
    {
        $xml = $this->loadXml('Incomes.xml');
        if (! $xml instanceof SimpleXMLElement) {
            return;
        }

        $this->rebuildIncomeVendorClientIndex();
        $seq = 0;

        foreach ($xml->Income as $row) {
            $amount = $this->decimalString($row->Amount, '0');
            if (bccomp($amount, '0', 4) !== 1) {
                continue;
            }

            $vendor = trim((string) $row->Vendor);
            $note = trim((string) $row->Note);
            $norm = $this->normalizeVendorString($vendor);
            $clientId = ($norm !== '' && isset($this->normalizedVendorToClientId[$norm]))
                ? $this->normalizedVendorToClientId[$norm]
                : $this->ensureLegacyIncomePoolClientId($counts);

            $staffId = (int) $row->StaffID;
            $recordedBy = $staffId > 0 ? ($this->staffIdToUserId[$staffId] ?? null) : null;

            $incomeDate = $this->parseDate((string) $row->Date) ?? now()->toDateString();
            $paidAt = $this->parseDateTime('', $incomeDate);

            $noteLines = ['استيراد من Incomes.xml (مدمج مع دفعات العملاء).'];
            if ($vendor !== '') {
                $noteLines[] = 'الجهة في XML (Vendor): '.$vendor;
            }
            if ($note !== '') {
                $noteLines[] = 'ملاحظة: '.$note;
            }
            $cat = trim((string) $row->Category);
            if ($cat !== '') {
                $noteLines[] = 'تصنيف: '.$cat;
            }

            $seq++;
            $rowJson = $this->elementToJson($row);
            $bankRef = 'xml-income:'.$seq.':'.substr(sha1(($rowJson ?? '').':'.$amount.':'.$incomeDate), 0, 12);

            DB::table('client_payments')->insert([
                'client_id' => $clientId,
                'amount' => $amount,
                'currency_code' => strtoupper(trim((string) $row->CurrencyCode) ?: 'ILS'),
                'paid_at' => $paidAt,
                'method' => null,
                'bank_reference' => $bankRef,
                'notes' => implode("\n", $noteLines),
                'recorded_by_user_id' => $recordedBy,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $counts['client_payments']++;
            $counts['legacy_income_xml_as_client_payments']++;
        }

        ($this->log)("دفعات من Incomes.xml (مدمجة): {$counts['legacy_income_xml_as_client_payments']}");
    }

    private function rebuildIncomeVendorClientIndex(): void
    {
        $this->normalizedVendorToClientId = [];
        $rows = DB::table('clients')->whereNull('deleted_at')->get(['id', 'business_name', 'first_name', 'last_name']);
        foreach ($rows as $row) {
            foreach ($this->clientVendorAliasKeys($row) as $norm) {
                if ($norm === '') {
                    continue;
                }
                if (! isset($this->normalizedVendorToClientId[$norm])) {
                    $this->normalizedVendorToClientId[$norm] = (int) $row->id;
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function clientVendorAliasKeys(object $row): array
    {
        $keys = [];
        $bn = trim((string) ($row->business_name ?? ''));
        if ($bn !== '') {
            $keys[] = $this->normalizeVendorString($bn);
        }
        $fn = trim((string) ($row->first_name ?? ''));
        $ln = trim((string) ($row->last_name ?? ''));
        $full = trim($fn.' '.$ln);
        if ($full !== '') {
            $keys[] = $this->normalizeVendorString($full);
        }

        return array_values(array_unique($keys));
    }

    private function normalizeVendorString(string $s): string
    {
        $t = preg_replace('/\s+/u', ' ', trim($s)) ?? '';

        return mb_strtolower($t, 'UTF-8');
    }

    /**
     * عميل تجميعي لصفوف Incomes.xml التي لا تُطابق عميلاً بالاسم.
     *
     * @param  array<string, int>  $counts
     */
    private function ensureLegacyIncomePoolClientId(array &$counts): int
    {
        if ($this->legacyIncomePoolClientId !== null) {
            return $this->legacyIncomePoolClientId;
        }

        $existing = DB::table('clients')->where('legacy_match_key', 'xml:income:pool')->whereNull('deleted_at')->value('id');
        if ($existing) {
            $this->legacyIncomePoolClientId = (int) $existing;

            return $this->legacyIncomePoolClientId;
        }

        $id = DB::table('clients')->insertGetId([
            'legacy_number' => 'xml-pool-income',
            'legacy_match_key' => 'xml:income:pool',
            'business_name' => 'دفعات نقدية عامة (استيراد Incomes.xml)',
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone_primary' => null,
            'phone_secondary' => null,
            'address_line1' => null,
            'address_line2' => null,
            'city' => null,
            'state_region' => null,
            'postal_code' => null,
            'country_code' => null,
            'notes' => 'يُنشأ تلقائياً عند الاستيراد لتجميع صفوف Incomes.xml التي لم يُعرف عميلها من حقل Vendor.',
            'assigned_user_id' => null,
            'source_row_json' => null,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->legacyIncomePoolClientId = $id;
        $counts['clients']++;

        return $this->legacyIncomePoolClientId;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function importLegacyCatalog(array &$counts): void
    {
        $px = $this->loadXml('Products.xml');
        if ($px instanceof SimpleXMLElement) {
            foreach ($px->Product as $p) {
                $pid = (int) $p->id;
                if ($pid <= 0) {
                    continue;
                }
                if (DB::table('legacy_catalog_products')->where('id', $pid)->exists()) {
                    continue;
                }
                DB::table('legacy_catalog_products')->insert([
                    'id' => $pid,
                    'payload_json' => $this->elementToJson($p) ?? '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $counts['legacy_catalog_products']++;
            }
        }

        $jx = $this->loadXml('Projects.xml');
        if ($jx instanceof SimpleXMLElement) {
            foreach ($jx->Project as $p) {
                $pid = (int) $p->ID;
                if ($pid <= 0) {
                    continue;
                }
                if (DB::table('legacy_catalog_projects')->where('id', $pid)->exists()) {
                    continue;
                }
                DB::table('legacy_catalog_projects')->insert([
                    'id' => $pid,
                    'payload_json' => $this->elementToJson($p) ?? '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $counts['legacy_catalog_projects']++;
            }
        }

        ($this->log)("كتالوج قديم: منتجات {$counts['legacy_catalog_products']}، مشاريع {$counts['legacy_catalog_projects']}");
    }

    private function blankToNull(string $s): ?string
    {
        $t = trim($s);

        return $t === '' || $t === '0000-00-00' ? null : $t;
    }

    private function clientBusinessKey(string $clientNumber, string $businessName): string
    {
        $n = trim($clientNumber);
        $b = trim($businessName);
        $norm = $b === '' ? '' : mb_strtolower(preg_replace('/\s+/u', ' ', $b));

        return $n."\x1f".$norm;
    }

    private function elementToJson(SimpleXMLElement $el): ?string
    {
        try {
            $arr = json_decode(json_encode($el), true, 512, JSON_THROW_ON_ERROR);

            return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
    }

    private function parseDate(string $s): ?string
    {
        $t = trim($s);
        if ($t === '' || str_starts_with($t, '0000-00-00')) {
            return null;
        }
        try {
            return Carbon::parse($t)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function parseDateTime(string $lastSent, ?string $fallbackDate): string
    {
        $t = trim($lastSent);
        if ($t !== '') {
            try {
                return Carbon::parse($t)->toDateTimeString();
            } catch (Throwable) {
                // fall through
            }
        }
        $d = $fallbackDate ?? now()->toDateString();
        try {
            return Carbon::parse($d)->startOfDay()->toDateTimeString();
        } catch (Throwable) {
            return now()->toDateTimeString();
        }
    }

    private function addDays(?string $date, int $days): ?string
    {
        if ($date === null || $days <= 0) {
            return $date;
        }
        try {
            return Carbon::parse($date)->addDays($days)->toDateString();
        } catch (Throwable) {
            return $date;
        }
    }

    private function decimalString(SimpleXMLElement|string|null $v, string $default): string
    {
        $s = trim((string) $v);
        if ($s === '') {
            return $default;
        }
        if (! is_numeric($s)) {
            return $default;
        }

        return number_format((float) $s, 4, '.', '');
    }
}
