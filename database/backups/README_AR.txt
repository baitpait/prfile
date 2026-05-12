نسخة ERP القديمة (MariaDB / baitpait_profileMedia)
==============================================

الملف: legacy_erp_baitpait_profileMedia_2026-05-12.sql
هو dump كامل من النظام القديم (ليس مخطط Laravel).

استيراد الملف إلى MySQL / MariaDB (على السيرفر أو محلياً)
--------------------------------------------------------
السطران الأولان في الملف قد يفشلان الاستيراد (تحذير mysqldump + تعليق sandbox).
استخدم أحد الخيارين:

  sed '1,2d' legacy_erp_baitpait_profileMedia_2026-05-12.sql | mysql -u USER -p baitpait_profileMedia

أو احذف السطرين يدوياً في محرر نصوص ثم استورد من phpMyAdmin.

بعدها اضبط في .env اتصال LEGACY_ERP_* نحو قاعدة baitpait_profileMedia وشغّل:
  php artisan legacy-erp:import

ملف جاهز لـ Laravel على السيرفر (INSERT فقط)
----------------------------------------------
يُنشأ على سطح المكتب بعد الترحيل:
  profile_media_laravel_for_server_mysql.sql

يُستورد في قاعدة Laravel على السيرفر بعد php artisan migrate --force
(قاعدة جداول فارغة من الصفوف).

ملاحظة: ملفات *.sql في هذا المجلد مستثناة من Git (حجم + خصوصية).
