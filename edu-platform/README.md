# مشروع وهمي: منصة تعليمية إلكترونية

مشروع تدريبي كامل باستخدام:
- HTML
- CSS
- JavaScript
- PHP

## الأقسام الموجودة
- الصفحة الرئيسية `index.html`
- صفحة الدورات `courses.html`
- صفحة من نحن `about.html`
- صفحة التواصل `contact.html`
- API للتواصل `api/contact.php`
- API للتسجيل في دورة `api/enroll.php`

## التشغيل داخل VS Code فقط
1. افتح المجلد `edu-platform` داخل VS Code.
2. من الطرفية (Terminal) نفّذ:
   ```bash
   php -S localhost:8000
   ```
3. افتح المتصفح على:
   `http://localhost:8000/index.html`

## ملاحظات
- يتم حفظ رسائل التواصل في `data/messages.json`.
- يتم حفظ التسجيلات في `data/enrollments.json`.
- المشروع "وهمي" (Demo) بدون قاعدة بيانات حقيقية.
