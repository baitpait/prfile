# دليل النشر والتشغيل — بروفايل ميدا

> هذا الملف يجمع كل ما يخص نشر التطبيق على الإنتاج (profile.baitpait.com)، الاستيراد من ERP القديم، النسخ الاحتياطي، استكشاف الأخطاء الشائعة، وإدارة Git على السيرفر.

---

## 1) معلومات الاستضافة والدومين

| البند | القيمة |
|-------|--------|
| الدومين | `https://profile.baitpait.com` |
| الخادم (IP) | `104.207.65.64` |
| مستخدم نظام التشغيل | `baitpait` |
| **جذر الويب (Document root)** | `/home/baitpait/public_html/profile/public` |
| **جذر Laravel (artisan + composer)** | `/home/baitpait/public_html/profile` |
| قاعدة بيانات Laravel | `baitpait_profile` (MySQL/MariaDB) |
| قاعدة ERP القديمة | `baitpait_profileMedia` (منفصلة، إن احتجت `legacy-erp:import`) |
| مستودع GitHub | [`baitpait/prfile`](https://github.com/baitpait/prfile) |

---

## 2) أول نشر (Initial Deployment)

```bash
cd /home/baitpait/public_html
# إن وُجد profile قديم محتفظ بنسخة:
# mv profile "profile_backup_$(date +%Y%m%d_%H%M)"

git clone https://github.com/baitpait/prfile.git profile
cd /home/baitpait/public_html/profile

# 1) تبعيات PHP (إنتاج)
composer install --no-dev --optimize-autoloader

# 2) ملف البيئة
cp .env.example .env
nano .env   # اضبط APP_URL, APP_ENV=production, APP_DEBUG=false, DB_*

# 3) مفتاح التطبيق
php artisan key:generate --force

# 4) الجداول
php artisan migrate --force

# 5) رابط التخزين
php artisan storage:link

# 6) أصول الواجهة (يحتاج Node على السيرفر)
npm ci
npm run build

# 7) كاش الإنتاج
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8) صلاحيات
chmod -R ug+rwx storage bootstrap/cache
```

---

## 3) تحديث لاحق من GitHub

```bash
cd /home/baitpait/public_html/profile

# 1) سحب التحديثات (اسم الـ remote قد يكون origin أو prfile — تحقق):
git remote -v
git pull origin main         # أو: git pull prfile main

# 2) لو تغيّر composer.json:
composer install --no-dev --optimize-autoloader

# 3) لو وُجدت migrations جديدة:
php artisan migrate --force

# 4) إن تغيّرت أصول Vite (CSS/JS):
npm ci && npm run build

# 5) دائماً بعد أي pull:
php artisan view:clear && php artisan view:cache
php artisan route:clear && php artisan route:cache
php artisan config:clear && php artisan config:cache
```

سطر مختصر للصق:

```bash
cd /home/baitpait/public_html/profile && git pull origin main && \
  php artisan view:clear && php artisan route:clear && php artisan config:clear && \
  php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 4) قاعدة البيانات على السيرفر

### 4.1 المايغريشن
- لأول نشر، أو إذا فشل `migrate` بسبب «Table already exists» (جداول من محاولة سابقة):
  ```bash
  php artisan migrate:fresh --force
  ```
  **تحذير:** يحذف كل الصفوف. تأكد أن القاعدة فارغة بياناتاً قبله.

### 4.2 استيراد بيانات (INSERT فقط)
- الملف يُولَّد محلياً:
  ```bash
  php artisan export:mysql-data --output=~/Desktop/profile_media_DATA_ONLY_inserts.sql
  ```
- يُرفع للسيرفر عبر **phpMyAdmin** (تبويب «استيراد») في قاعدة `baitpait_profile`.
- أو من سطر الأوامر:
  ```bash
  scp ~/Desktop/profile_media_DATA_ONLY_inserts.sql root@104.207.65.64:/tmp/
  mysql -u baitpait_profile -p baitpait_profile < /tmp/profile_media_DATA_ONLY_inserts.sql
  ```

### 4.3 الترحيل من ERP القديم
- استورد dump ERP في قاعدة **منفصلة** (`baitpait_profileMedia`):
  - استخدم الملف **`*_phpmyadmin_clean.sql`** (السطران الأولان حُذفا لتفادي خطأ phpMyAdmin).
- في `.env` (على السيرفر أو محلياً):
  ```env
  LEGACY_ERP_DRIVER=mysql
  LEGACY_ERP_HOST=127.0.0.1
  LEGACY_ERP_DATABASE=baitpait_profileMedia
  LEGACY_ERP_USERNAME=...
  LEGACY_ERP_PASSWORD=...
  ```
- ثم:
  ```bash
  php artisan legacy-erp:import --dry-run   # عرض الأعداد
  php artisan legacy-erp:import             # تنفيذ فعلي
  ```

---

## 5) النسخ الاحتياطي (انظر أيضاً `DATABASE_BACKUP_AND_RESTORE_AR.md`)

| المصدر | الأمر |
|--------|-------|
| SQLite محلي | `cp database/database.sqlite database/backups/laravel_local_$(date +%Y-%m-%d_%H%M%S).sqlite` |
| تصدير لـ MySQL | `php artisan export:mysql-data` (يكتب على سطح المكتب افتراضياً) |
| MySQL إنتاج | `mysqldump -u baitpait_profile -p baitpait_profile > backup_$(date +%Y%m%d).sql` |

من الواجهة (مدير): **الإدارة → نسخ احتياطي** — الملفات في `storage/app/database-backups/` (نسخ قديمة قد تبقى في `database/backups/`). **مستثناة من Git** — انسخها لقرص خارجي/سحابة.

---

## 6) حسابات الدخول الافتراضية بعد الترحيل

| البريد | الدور | ملاحظة |
|--------|------|--------|
| `admin@baitpait.com` | manager | غيّر كلمة المرور فور الدخول. كلمة المرور المؤقتة المضبوطة عند التصدير: `TempPass2026!` |
| `legacy-erp-import@localhost` | manager (تقني) | لا يُستخدم للدخول؛ موجود لربط `recorded_by_user_id` بسجلات ERP. |

لإعادة تعيين كلمة مرور من السيرفر:

```bash
php artisan tinker --execute='\App\Models\User::where("email","admin@baitpait.com")->update(["password"=>"كلمة_جديدة_قوية"]);'
```

(الـ cast يشفّر تلقائياً.)

---

## 7) ملف `.env` الإنتاج (مرجع)

> القالب الكامل: `.env.production` (محلي، مستثنى من Git).

أهم المفاتيح للإنتاج:

```env
APP_NAME="بروفايل ميدا"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://profile.baitpait.com
APP_LOCALE=ar

DB_CONNECTION=mysql
DB_HOST=127.0.0.1            # أو localhost حسب المضيف
DB_DATABASE=baitpait_profile
DB_USERNAME=baitpait_profile
DB_PASSWORD="..."

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_DOMAIN=profile.baitpait.com
SESSION_SECURE_COOKIE=true

MAIL_MAILER=log              # log أثناء التشغيل التجريبي، smtp لاحقاً
```

**تحذير:** لا تشارك `.env` ولا ترفعه إلى Git. غيّر كلمة مرور قاعدة البيانات من لوحة الاستضافة بعد أول نشر ناجح.

---

## 8) استكشاف الأخطاء الشائعة

| الخطأ | السبب | الحل |
|-------|------|------|
| `HTTP 500` على الصفحة الرئيسية | غالباً صلاحيات أو cache قديم | `chmod -R ug+rwx storage bootstrap/cache` ثم `php artisan optimize:clear` ثم `config:cache` |
| `Vite manifest not found` | لم يُبنَ `public/build/manifest.json` | `npm ci && npm run build` على السيرفر (أو ارفع `public/build` من المحلي) |
| `Table 'users' already exists` عند `migrate` | جداول من محاولة سابقة و`migrations` فارغ | `php artisan migrate:fresh --force` (يحذف الكل) |
| `Identifier name ... is too long` (MySQL) | اسم فهرس تلقائي > 64 حرف (مثلاً `client_balance_adjustments_...`) | اسحب آخر `main` (commit `93f88b0+`) ثم `php artisan migrate --force`؛ الترحيل idempotent. إن بقي جدول بلا فهرس: `DROP TABLE client_balance_adjustments, supplier_balance_adjustments` ثم `migrate --force` |
| `Foreign key constraint is incorrectly formed` | استيراد dump ERP داخل قاعدة Laravel | افصل: ERP في `baitpait_profileMedia`، Laravel في `baitpait_profile` |
| `1064 ... '/usr/bin/mysqldump: Deprecated'` في phpMyAdmin | السطر الأول في dump رسالة تحذير وليس SQL | استخدم نسخة `*_phpmyadmin_clean.sql` (بعد `sed '1,2d'`) |
| `APP_NAME` لا يظهر | القالب كان يحوي نصاً ثابتاً | بعد commit `46eb03d` صار يقرأ `config('app.name')` — أعد `php artisan config:cache && view:cache` |
| `fatal: 'prfile' does not appear to be a git repository` | اسم الـ remote على السيرفر مختلف | تحقق `git remote -v`، عادة الاسم `origin` |
| `Permission denied` عند نسخ قاعدة البيانات من الواجهة | PHP لا يكتب في `database/` | اسحب آخر `main` (يحفظ في `storage/app/database-backups`) ثم `chmod -R ug+rwx storage` |
| الفلاتر/البحث في القوائم لا يتفاعل | Livewire/Vite غير محمّل أو تهيئة مزدوجة | `npm ci && npm run build` ثم `php artisan config:clear && php artisan config:cache` (يجب `inject_assets=false` في `config/livewire.php`)؛ F12 → Network → `/livewire/update` = 200 |
| البحث يعمل في صفحة ولا في أخرى (مثلاً عملاء ✓ / موردون ✗) | **UTF-8 BOM** في بداية `.blade.php` يكسر جذر `[wire:id]` | `xxd resources/views/livewire/<file>.blade.php \| head -1` — إن ظهر `efbb bf` أزل BOM واحفظ UTF-8 بدون BOM؛ `php artisan view:clear`. راجع `docs/troubleshooting/INCIDENT-001-supplier-list-utf8-bom-livewire.md` |
| `The selected طريقة الدفع is invalid` عند تعديل دفعة | `method` مخزّن بنص عربي أو `طريقة #N` من استيراد قديم | اسحب `50ceee1+` ثم `php artisan payments:normalize-methods`. راجع `docs/troubleshooting/INCIDENT-002-payment-method-invalid-on-edit.md` |
| `<select>` يظهر أبيض/فارغ على الإنتاج (البيانات موجودة في HTML) | Dark Mode + خلفية بيضاء للحقل | اسحب `2d18e7c+` ثم `npm run build && php artisan view:cache`. راجع `docs/troubleshooting/INCIDENT-003-select-white-text-dark-mode.md` |

---

## 9) Git على السيرفر — تذكير

- بيئة العمل المعتمدة: **محلياً** ندفع إلى `prfile/main`، السيرفر يسحب من نفس المستودع باسم `origin` أو `prfile`.
- لا تعديل مباشر على السيرفر إن أمكن؛ إن حصل (كما حدث في commit `93986f5` بتعديل «ميدا» → «ميديا»): اسحبه محلياً قبل أي push جديد:
  ```bash
  git pull --rebase prfile main
  ```

---

## 10) مراجع ذات صلة

- `README.md` — البداية السريعة، الأدوار، السياسات.
- `docs/00_CONSTITUTION_UI_AR.md` — قواعد الواجهة العربية.
- `docs/03_DATABASE_SPEC.md` — مواصفات الجداول.
- `docs/04_REPORTS_AND_UI_MATRIX.md` — التقارير والشاشات.
- `docs/07_SYSTEM_OVERVIEW_AR.md` — مخطط شامل (Mermaid ERD).
- `docs/DATABASE_BACKUP_AND_RESTORE_AR.md` — تفاصيل النسخ الاحتياطي.
- `docs/12_PAYMENT_METHODS_AND_LEGACY_NORMALIZATION_AR.md` — أكواد طرق الدفع وتطبيع البيانات القديمة.
- `docs/decisions/` — ADRs (قرارات معمارية).
- `docs/troubleshooting/INCIDENT-001-supplier-list-utf8-bom-livewire.md` — RCA: BOM + Livewire + بحث الأطراف.
- `docs/troubleshooting/INCIDENT-002-payment-method-invalid-on-edit.md` — RCA: طريقة دفع غير معيارية عند التعديل.
- `docs/troubleshooting/INCIDENT-003-select-white-text-dark-mode.md` — RCA: select أبيض في Dark Mode.
