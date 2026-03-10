<?php
/**
 * ===================================================
 *  ملف معالجة نموذج التواصل - محمود | مطور ويب
 * ===================================================
 *  هذا الملف يستقبل بيانات النموذج ويرسل بريد إلكتروني
 *  ويخزن البيانات في ملف JSON للرجوع إليها
 * ===================================================
 */

// إعدادات الأمان
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'طريقة الطلب غير مسموحة'
    ]);
    exit;
}

// ===== إعدادات البريد الإلكتروني =====
$admin_email = 'mahmoud@example.com'; // غيّر هذا لبريدك الحقيقي
$site_name   = 'محمود - مطور ويب';

// ===== دوال مساعدة =====

/**
 * تنظيف المدخلات من الأكواد الضارة
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من صحة رقم الهاتف
 */
function isValidPhone($phone) {
    return preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $phone);
}

/**
 * حماية من هجمات حقن البريد
 */
function isInjected($str) {
    $injections = array(
        '(\n+)',
        '(\r+)',
        '(\t+)',
        '(%0A+)',
        '(%0D+)',
        '(%08+)',
        '(%09+)'
    );
    $inject = join('|', $injections);
    return preg_match("/$inject/i", $str);
}

/**
 * حماية CSRF بسيطة - التحقق من المرجع
 */
function checkReferer() {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $host = $_SERVER['HTTP_HOST'];
        return ($referer === $host);
    }
    return true; // السماح بالطلبات بدون Referer
}

/**
 * حماية من الإرسال المتكرر (Rate Limiting)
 */
function checkRateLimit($ip) {
    $rate_file = __DIR__ . '/rate_limit.json';
    $max_requests = 5; // أقصى عدد طلبات
    $time_window = 3600; // خلال ساعة واحدة (بالثواني)

    $data = [];
    if (file_exists($rate_file)) {
        $data = json_decode(file_get_contents($rate_file), true) ?: [];
    }

    $now = time();

    // تنظيف البيانات القديمة
    foreach ($data as $key => $entries) {
        $data[$key] = array_filter($entries, function($timestamp) use ($now, $time_window) {
            return ($now - $timestamp) < $time_window;
        });
        if (empty($data[$key])) unset($data[$key]);
    }

    // التحقق من عدد الطلبات
    if (isset($data[$ip]) && count($data[$ip]) >= $max_requests) {
        return false;
    }

    // تسجيل الطلب الجديد
    $data[$ip][] = $now;
    file_put_contents($rate_file, json_encode($data));

    return true;
}

/**
 * حفظ الرسالة في ملف JSON
 */
function saveMessage($messageData) {
    $file = __DIR__ . '/messages.json';
    $messages = [];

    if (file_exists($file)) {
        $messages = json_decode(file_get_contents($file), true) ?: [];
    }

    $messages[] = $messageData;
    file_put_contents($file, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * كتابة سجل الأنشطة
 */
function writeLog($message) {
    $log_file = __DIR__ . '/contact_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $log_entry = "[{$timestamp}] [{$ip}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// ===== المعالجة الرئيسية =====

try {
    // التحقق من Rate Limiting
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!checkRateLimit($client_ip)) {
        writeLog("Rate limit exceeded");
        echo json_encode([
            'status' => 'error',
            'message' => 'لقد تجاوزت الحد المسموح. يرجى المحاولة لاحقاً.'
        ]);
        exit;
    }

    // استقبال وتنظيف البيانات
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $service = sanitize($_POST['service'] ?? '');
    $budget  = sanitize($_POST['budget'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    // التحقق من الحقول المطلوبة
    $errors = [];

    if (empty($name)) {
        $errors[] = 'الاسم مطلوب';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'الاسم يجب أن يكون بين 2 و 100 حرف';
    }

    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'البريد الإلكتروني غير صالح';
    } elseif (isInjected($email)) {
        $errors[] = 'البريد الإلكتروني يحتوي على محتوى غير مسموح';
    }

    if (!empty($phone) && !isValidPhone($phone)) {
        $errors[] = 'رقم الهاتف غير صالح';
    }

    if (empty($message)) {
        $errors[] = 'الرسالة مطلوبة';
    } elseif (strlen($message) < 10) {
        $errors[] = 'الرسالة يجب أن تكون 10 أحرف على الأقل';
    } elseif (strlen($message) > 5000) {
        $errors[] = 'الرسالة طويلة جداً';
    }

    // التحقق من حقل الخدمة
    $valid_services = ['', 'website', 'webapp', 'ecommerce', 'seo', 'other'];
    if (!in_array($service, $valid_services)) {
        $errors[] = 'نوع الخدمة غير صالح';
    }

    // التحقق من حقل الميزانية
    $valid_budgets = ['', 'low', 'mid', 'high', 'enterprise'];
    if (!in_array($budget, $valid_budgets)) {
        $errors[] = 'الميزانية غير صالحة';
    }

    // إذا كانت هناك أخطاء
    if (!empty($errors)) {
        writeLog("Validation errors: " . implode(', ', $errors));
        echo json_encode([
            'status' => 'error',
            'message' => implode('. ', $errors)
        ]);
        exit;
    }

    // تحويل قيم الخدمة والميزانية للعربية
    $service_names = [
        'website'   => 'تصميم موقع ويب',
        'webapp'    => 'تطبيق ويب',
        'ecommerce' => 'متجر إلكتروني',
        'seo'       => 'تحسين SEO',
        'other'     => 'أخرى',
        ''          => 'غير محدد'
    ];

    $budget_names = [
        'low'        => 'أقل من $500',
        'mid'        => '$500 - $1000',
        'high'       => '$1000 - $3000',
        'enterprise' => 'أكثر من $3000',
        ''           => 'غير محدد'
    ];

    $service_text = $service_names[$service] ?? 'غير محدد';
    $budget_text  = $budget_names[$budget] ?? 'غير محدد';

    // إنشاء محتوى البريد الإلكتروني (HTML)
    $email_body = "
    <!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
            .email-header { background: linear-gradient(135deg, #e63946, #b71c1c); padding: 30px; text-align: center; color: #fff; }
            .email-header h1 { margin: 0; font-size: 24px; }
            .email-header p { margin: 5px 0 0; opacity: 0.8; }
            .email-body { padding: 30px; }
            .info-row { display: flex; border-bottom: 1px solid #eee; padding: 15px 0; }
            .info-label { font-weight: bold; color: #333; min-width: 140px; }
            .info-value { color: #666; }
            .message-box { background: #f8f9fa; border-right: 4px solid #e63946; padding: 20px; margin-top: 20px; border-radius: 8px; }
            .message-box h3 { color: #e63946; margin-top: 0; }
            .message-box p { color: #555; line-height: 1.8; }
            .email-footer { background: #1a1a1a; color: #aaa; padding: 20px; text-align: center; font-size: 13px; }
            .email-footer span { color: #e63946; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='email-header'>
                <h1>📧 رسالة جديدة</h1>
                <p>من نموذج التواصل على الموقع</p>
            </div>
            <div class='email-body'>
                <div class='info-row'>
                    <span class='info-label'>👤 الاسم:</span>
                    <span class='info-value'>{$name}</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>📧 البريد:</span>
                    <span class='info-value'>{$email}</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>📱 الهاتف:</span>
                    <span class='info-value'>" . ($phone ?: 'غير مذكور') . "</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>🛠️ الخدمة:</span>
                    <span class='info-value'>{$service_text}</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>💰 الميزانية:</span>
                    <span class='info-value'>{$budget_text}</span>
                </div>
                <div class='message-box'>
                    <h3>💬 تفاصيل المشروع:</h3>
                    <p>" . nl2br($message) . "</p>
                </div>
            </div>
            <div class='email-footer'>
                <p>تم الإرسال بتاريخ: " . date('Y/m/d - h:i A') . "</p>
                <p>IP: {$client_ip}</p>
                <p>© " . date('Y') . " <span>{$site_name}</span></p>
            </div>
        </div>
    </body>
    </html>
    ";

    // إعداد رؤوس البريد
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$site_name} <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Reply-To: {$name} <{$email}>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $subject = "📧 رسالة جديدة من {$name} - {$site_name}";

    // إرسال البريد
    $mail_sent = @mail($admin_email, $subject, $email_body, $headers);

    // حفظ الرسالة في قاعدة بيانات JSON
    $messageData = [
        'id'        => uniqid('msg_'),
        'name'      => $name,
        'email'     => $email,
        'phone'     => $phone,
        'service'   => $service_text,
        'budget'    => $budget_text,
        'message'   => $message,
        'ip'        => $client_ip,
        'date'      => date('Y-m-d H:i:s'),
        'read'      => false,
        'replied'   => false
    ];

    saveMessage($messageData);

    // تسجيل النشاط
    writeLog("New message from: {$name} ({$email}) - Mail sent: " . ($mail_sent ? 'Yes' : 'No'));

    // إرسال رد تلقائي للعميل
    $auto_reply_body = "
    <!DOCTYPE html>
    <html dir='rtl'>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: #fff; border-radius: 15px; overflow: hidden;'>
            <div style='background: linear-gradient(135deg, #e63946, #b71c1c); padding: 30px; text-align: center; color: #fff;'>
                <h1>شكراً لتواصلك معنا! 🎉</h1>
            </div>
            <div style='padding: 30px;'>
                <p style='font-size: 16px; color: #333;'>مرحباً <strong>{$name}</strong>،</p>
                <p style='color: #666; line-height: 2;'>
                    شكراً لتواصلك معنا. تم استلام رسالتك بنجاح وسنقوم بالرد عليك في أقرب وقت ممكن خلال 24 ساعة كحد أقصى.
                </p>
                <p style='color: #666; line-height: 2;'>
                    إذا كان لديك أي استفسار عاجل، يمكنك التواصل معنا مباشرة عبر واتساب.
                </p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://wa.me/201000000000' style='background: linear-gradient(135deg, #e63946, #b71c1c); color: #fff; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: bold;'>
                        تواصل عبر واتساب
                    </a>
                </div>
            </div>
            <div style='background: #1a1a1a; color: #aaa; padding: 20px; text-align: center; font-size: 13px;'>
                <p>© " . date('Y') . " <span style='color: #e63946;'>{$site_name}</span> - جميع الحقوق محفوظة</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $auto_headers  = "MIME-Version: 1.0\r\n";
    $auto_headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $auto_headers .= "From: {$site_name} <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";

    @mail($email, "شكراً لتواصلك - {$site_name}", $auto_reply_body, $auto_headers);

    // الرد النهائي
    echo json_encode([
        'status'  => 'success',
        'message' => '✅ تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.'
    ]);

} catch (Exception $e) {
    writeLog("Error: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.'
    ]);
}
?>
