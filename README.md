# بروفايل ميدا — Profile Media

منصة تقارير وواجهات شاملة لإدارة الأعمال الإعلامية (عملاء، موردون، فواتير، دفعات، مصروفات).

---

## المكدس

| الطبقة | التقنية |
|--------|---------|
| Backend | Laravel 12 / PHP 8.2 |
| Frontend | Blade + Livewire 3 + Alpine.js + Tailwind CSS v4 |
| قاعدة البيانات | SQLite (تطوير) — MySQL/PostgreSQL (إنتاج) |
| PDF | barryvdh/laravel-dompdf |
| اختبارات | Pest v3 |

---

## التشغيل المحلي

### المتطلبات
- PHP 8.2+
- Composer 2+
- Node.js 18+

### الخطوات

```bash
# 1. نسخ ملف البيئة
cp .env.example .env

# 2. توليد مفتاح التطبيق
php artisan key:generate

# 3. تثبيت تبعيات PHP
composer install

# 4. تثبيت تبعيات Node
npm install

# 5. تشغيل المايغريشن (SQLite افتراضياً)
php artisan migrate

# 6. بناء الأصول
npm run build

# 7. تشغيل الخادم
php artisan serve
```

الموقع: http://localhost:8000

---

## الاختبارات

```bash
php vendor/bin/pest
```

---

## هيكل المجلدات الرئيسية

```
app/
├── Http/Controllers/       ← Controllers
├── Livewire/               ← مكونات Livewire
├── Models/                 ← Eloquent Models (SoftDeletes)
├── Policies/               ← سياسات الصلاحيات
└── Services/               ← منطق الأعمال (ClientStatementService)

database/
├── migrations/             ← مايغريشن كاملة من المواصفة
├── factories/              ← Factories للاختبارات
├── business_v1.sqlite      ← بيانات مرجعية من النظام القديم
└── reference_sqlite_v1_schema.sql

resources/views/
├── layouts/app.blade.php   ← التخطيط الرئيسي (RTL + عربي)
├── auth/                   ← صفحات المصادقة
├── livewire/               ← قوالب Livewire
│   └── client-statement.blade.php
└── pdf/                    ← قوالب PDF
    └── client-statement.blade.php

docs/                       ← دستور المشروع والوثائق
branding/                   ← الشعار والهوية البصرية
```

---

## الأدوار والصلاحيات

| الدور | عرض | إنشاء/تعديل | حذف |
|-------|-----|------------|-----|
| viewer | ✅ | ❌ | ❌ |
| accountant | ✅ | ✅ | ❌ |
| manager | ✅ | ✅ | ✅ |

---

## السياسات الحرجة

- **العملات:** لا يُجمع ILS + USD في رقم واحد — كل عملة قسم مستقل في الكشف.
- **الدفعات:** مرتبطة بالعميل مباشرة، لا بفاتورة بعينها.
- **الحذف:** soft delete لجميع البيانات الحرجة.
- **التوقيت:** UTC في التخزين، تحويل محلي في طبقة العرض فقط.

---

## أسئلة مفتوحة (قبل Sprint 1)

- [ ] سياسة دفعة بعملة مختلفة عن فاتورة العميل (مسموح / يدوي فقط؟)
- [ ] بيئة الاستضافة المستهدفة (VPS / Cloud)
- [ ] تأكيد ألوان HEX الدقيقة من الشعار

---

## Git Workflow

```
main          ← الفرع الرئيسي (محمي)
develop       ← التطوير
feature/*     ← ميزات
fix/*         ← إصلاحات
```

**Conventional Commits:** `feat:`, `fix:`, `chore:`, `test:`, `docs:`
