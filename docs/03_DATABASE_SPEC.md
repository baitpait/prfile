# مواصفة قاعدة البيانات المفاهيمية — بروفايل ميدا

> **ملاحظة:** هذا مخطط **منطقي** مستقل عن محرك SQL/NoSQL. عند اختيار التقنية، تُترجم الجداول إلى migrations مع فهارس وقيود.

## 1) كيانات أساسية

### `users` — مستخدمو النظام
| الحقل | النوع المنطقي | الوصف |
|--------|----------------|--------|
| id | UUID أو BIGINT | مفتاح. |
| email | نص | فريد؛ تسجيل الدخول. |
| password_hash | نص | بترميز آمن (لا يُخزّن نصاً صريحاً). |
| full_name | نص | |
| role | نص/مرجع | ربط بدور صلاحيات. |
| is_active | منطقي | |
| created_at / updated_at | UTC | |

### `staffs` أو دمج مع `users`
- يمكن دمج الموظف مع المستخدم أو إبقاء جدول موظفين للبيانات الوظيفية الإضافية.

### `clients` — العملاء
| الحقل | الوصف |
|--------|--------|
| legacy_number | اختياري؛ للربط ببيانات قديمة. |
| legacy_match_key | فريد عند وجود تكرار أرقام قديمة + هوية منشأة. |
| business_name, names, email, phones, address, country_code | |
| notes | نص حر. |
| assigned_user_id | اختياري. |
| is_deleted | soft delete. |
| source_row_json | اختياري؛ أرشيف ترحيل. |

### `client_contacts`
- جهات اتصال إضافية؛ `client_id` FK.

### `suppliers` / `supplier_contacts`
- مطابق لمنطق العملاء.

---

## 2) المستندات التجارية (نص حر)

### `invoices`
| الحقل | الوصف |
|--------|--------|
| client_id | FK |
| legacy_invoice_no | فريد عند الترحيل. |
| document_date, issue_date, due_date | |
| currency_code | عملة المستند. |
| discount_amount, total_amount | نص عشري أو DECIMAL حسب المحرك. |
| status | draft / issued / void |
| recorded_by_user_id | |
| source_row_json | أرشيف XML/قديم. |

### `invoice_lines`
| الحقل | الوصف |
|--------|--------|
| invoice_id | |
| line_order | ترتيب. |
| title | **نص حر** (لا كتالوج إجباري). |
| description | اختياري. |
| unit_price, quantity, line_total | |
| source_row_json | اختياري. |

### `purchase_orders` / `purchase_order_lines`
- نفس المنطق مع `supplier_id`.

---

## 3) الدفعات (على الطرف — ليس على الفاتورة)

### `client_payments`
| الحقل | الوصف |
|--------|--------|
| client_id | |
| amount | |
| currency_code | قد تختلف عن فاتورة معيّنة. |
| paid_at | UTC. |
| method, bank_reference, notes | |
| recorded_by_user_id | |
| is_deleted | |

### `supplier_payments`
- مطابق للمورد.

> **كشف الحساب:** يُحسب **لكل عملة** على حدة: مجموع المستندات − مجموع الدفعات (نفس العملة)، مع سياسة داخلية للحالات الحدية (دفعة بعملة مختلفة عن المستند).

---

## 4) اليومية النصية

### `expenses`
- `description` إلزامي (نص مركزي)، `amount`, `currency_code`, `expense_date`, `notes`, `recorded_by`.

### `income_entries`
- إيرادات عامة لا تصنّف كدفعة عميل.

---

## 5) أرشيف كتالوج قديم (اختياري)

### `legacy_catalog_products` / `legacy_catalog_projects`
- للحفاظ على بيانات XML قديمة دون إجبار المنطق الجديد على كتالوج.

---

## 6) فهارس مقترحة
- `(client_id, currency_code, document_date)` على الفواتير.
- `(client_id, paid_at)` على الدفعات.
- `(is_deleted)` حيث يُستخدم بكثرة في التصفية.

---

## 7) ارتباط بمشروع الترحيل الحالي
- يوجد مرجع عملي مُرحَّل في مسار آخر: `database/business_v1.sqlite` + سكربتات الترحيل — يمكن اعتباره **مرجعاً** عند بناء migrations للمشروع النهائي.
