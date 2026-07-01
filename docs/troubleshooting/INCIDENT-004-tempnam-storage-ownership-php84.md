# INCIDENT-004 — `tempnam(): file created in the system's temporary directory` (500)

**التاريخ:** 2026-07-01  
**البيئة:** `profile.baitpait.com` — Ubuntu 24.04، PHP 8.4.12، Webuzo  
**الأعراض:** HTTP 500 على `/invoices` وصفحات Livewire أخرى؛ Symfony Exception مع `ErrorException` في `Illuminate\Filesystem\Filesystem.php:222`.

---

## 1) الملخص

Laravel يجمّع قوالب Blade عبر `Filesystem::replace()` → `tempnam(dirname($path), …)`.  
إذا كان المجلد **غير قابل للكتابة** لمستخدم PHP الفعلي، يعود `tempnam()` إلى `/tmp` ويُصدِر **إشعاراً** (منذ PHP 7.1).  
Laravel يرفع الإشعار إلى `ErrorException` → **500**.

---

## 2) السبب الجذري

1. تشغيل `php artisan view:cache` / `config:cache` كـ **`root`** على السيرفر.
2. ملفات `storage/` و`bootstrap/cache` أصبحت مملوكة لـ `root` أو لـ **`webuzo`** (pool عام).
3. موقع `profile.baitpait.com` يعمل تحت مستخدم **`baitpait`** (مالك `/home/baitpait/`).
4. `webuzo` يمرّ `storage:doctor` من SSH، لكن **المتصفح** يفشل لأن `baitpait` لا يكتب في المجلد.

---

## 3) التشخيص

```bash
stat -c '%U:%G %a' storage/framework/views
# المتوقع بعد الإصلاح: baitpait:baitpait 775

su -s /bin/bash baitpait -c 'touch storage/framework/views/.write-test && echo OK'

php artisan storage:doctor
```

تحقق من `open_basedir` (كان فارغاً = لا مشكلة):

```bash
grep open_basedir /usr/local/emps/etc/php.ini
```

---

## 4) الحل

```bash
cd /home/baitpait/public_html/profile

chown -R baitpait:baitpait storage bootstrap/cache node_modules
chmod -R ug+rwx storage bootstrap/cache

php artisan optimize:clear
php artisan config:cache
php artisan route:cache

chown -R baitpait:baitpait storage bootstrap/cache
```

**لا تستخدم** `chown webuzo` لمواقع تحت `/home/baitpait/`.

---

## 5) إصلاحات الكود (وقاية)

| التغيير | الغرض |
|---------|--------|
| `config/view.php` — `compiled` بدون `realpath()` | يمنع `false` في كاش الإعداد عند غياب المجلد |
| `App\Filesystem\Filesystem` — `@tempnam` عند مجلد قابل للكتابة | يمنع تحويل إشعار PHP 8.4 إلى 500 |
| `php artisan storage:doctor` | تشخيص سريع للصلاحيات |
| `AppServiceProvider` — إنشاء مجلدات storage عند الإقلاع | يقلل احتمال مجلد مفقود |

Commit: `c298f37`, `76073fb`.

---

## 6) الوقاية

| ❌ تجنّب | ✅ افعل |
|---------|--------|
| `php artisan …` كـ root دون `chown` | `chown -R baitpait:baitpait storage bootstrap/cache` بعد كل نشر |
| افتراض أن `webuzo` = مستخدم الموقع | تحقق: `stat` على `storage/framework/views` |
| `config:cache` فقط بعد `git pull` | `route:cache` + `config:cache` + `optimize:clear` عند الحاجة |

---

## 7) حالة الحادث

**مُغلق** — بعد `chown baitpait:baitpait` + سحب `76073fb`، `/invoices` وPDF يعملان على الإنتاج.
