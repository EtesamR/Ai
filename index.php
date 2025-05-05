<?php

// نیاز به کلید API DeepAI دارید
// !!! هشدار: کلید API خود را در کد منبع عمومی قرار ندهید!
// از متغیرهای محیطی یا فایل‌های پیکربندی امن استفاده کنید در محیط تولید.
// لطفاً مطمئن شوید که این کلید برای DeepAI API معتبر است و نه DeepAI API.
$openai_api_key = 'Yaa6a3f47-f8d1-46f7-a9b4-3be1a5a75116'; // کلید خود را اینجا قرار دهید!

// URL وبسایتی که می‌خواهید محتوا را از آن استخراج کنید
$url_to_process = "https://etesamr.github.io/pachatbotpro1/";

// متغیری برای نگهداری محتوای استخراج شده
$context_text = null;

/**
 * Sends a prompt to the DeepAI Chat Completion API.
 *
 * @param string $prompt The prompt including context and user question.
 * @return string|false The AI's response text, or false on failure.
 */
function askDeepAI($prompt) {
    global $openai_api_key;

    if (empty($openai_api_key) || $openai_api_key === 'YOUR_OPENAI_API_KEY') {
        return "خطا: کلید DeepAI API تنظیم نشده است یا پیش فرض است.";
    }

    // با توجه به Endpoint 'https://api.openai.com/v1/chat/completions'، این کد برای DeepAI API است.
    // اگر قصد استفاده از DeepAI API را دارید، Endpoint و ساختار درخواست متفاوت خواهد بود.
    $url = 'https://api.openai.com/v1/chat/completions'; // Endpoint برای DeepAI Chat API

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_api_key,
    ];

    // ساخت پیام‌ها برای API Chat Completion
    $messages = [
        [
            'role' => 'system',
            'content' => 'شما یک دستیار هوش مصنوعی هستید که به سوالات بر اساس متن ارائه شده پاسخ می‌دهید. اگر پاسخ در متن موجود نیست، بگویید که اطلاعات کافی در متن ارائه شده وجود ندارد.'
        ],
        [
            'role' => 'user',
            'content' => $prompt // محتوای استخراج شده + سوال کاربر در اینجا قرار می‌گیرند
        ]
    ];

    $data = [
        'model' => 'gpt-3.5-turbo', // می‌توانید از مدل‌های دیگر مانند gpt-4 استفاده کنید (ممکن است گران‌تر باشد)
        'messages' => $messages,
        'temperature' => 0.7, // خلاقیت مدل (۰ تا ۲)
        'max_tokens' => 500, // حداکثر طول پاسخ
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // *** اضافه کردن خطوط زیر برای غیرفعال کردن اعتبارسنجی SSL برای ارتباط با DeepAI API ***
    // !!! هشدار: این کار امنیت را کاهش می‌دهد و در محیط تولید توصیه نمی‌شود.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // ***********************************************************************************

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        return "خطا در ارسال درخواست به API هوش مصنوعی (cURL error): " . $err;
    }

    if ($http_code !== 200) {
         // نمایش پاسخ خطا از API
        return "خطا از API هوش مصنوعی (HTTP status code " . $http_code . "): " . $response;
    }

    $responseData = json_decode($response, true);

    // بررسی اینکه آیا پاسخ معتبر است و حاوی پیام است
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    } else {
        $error_message = "خطا: پاسخ نامعتبر از API هوش مصنوعی.";
         if ($response) {
             $error_message .= "\nپاسخ خام API: " . $response;
         }
        return $error_message;
    }
}

/**
 * Fetches the content of a URL and extracts structured text.
 *
 * @param string $url The URL to fetch.
 * @return string|false The extracted structured text, or false on failure.
 */
function fetchStructuredText($url) {
    // چک کردن اعتبار URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return "خطا: URL نامعتبر است.";
    }

    // استفاده از cURL برای دریافت محتوا
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    // *** راه حل موقت برای مشکل SSL certificate problem در محیط توسعه ***
    // !!! هشدار: این کار امنیت را کاهش می‌دهد و در محیط تولید توصیه نمی‌شود.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // *******************************************************************

    $html = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        return "خطا در دریافت محتوا با cURL: " . $err;
    }

    if ($http_code >= 400) {
        return "خطا در دریافت محتوا: کد وضعیت HTTP " . $http_code . " برای " . $url;
    }

    if (empty($html)) {
         return ""; // بازگشت رشته خالی به جای false
    }

    // تبدیل محتوا به DOMDocument
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    // اضافه کردن encoding برای کمک به DOMDocument در پردازش UTF-8
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $structured_text = "";

    // بهبود XPath Query برای انتخاب بهتر عناصر متنی و حذف عناصر ناخواسته
    $elements = $xpath->query('//body//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::p or self::li or self::span or self::div]
                                [not(ancestor::script) and not(ancestor::style) and not(ancestor::nav) and not(ancestor::footer) and not(ancestor::header) and not(ancestor::aside) and not(ancestor::form) and not(ancestor::button) and not(ancestor::img) and not(ancestor::svg)]');


    if ($elements->length === 0) {
         return ""; // بازگشت رشته خالی
    }

    foreach ($elements as $element) {
        $tag_name = strtolower($element->tagName);
        $text = trim($element->textContent);
        // حذف تگ‌های HTML باقی‌مانده احتمالی و چندین فضای خالی
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text); // دوباره trim برای حذف فضای خالی اضافی در ابتدا/انتهای متن

        if (!empty($text)) {
             switch ($tag_name) {
                case 'h1': $structured_text .= "## H1: " . $text . "\n\n"; break;
                case 'h2': $structured_text .= "### H2: " . $text . "\n\n"; break;
                case 'h3': $structured_text .= "#### H3: " . $text . "\n\n"; break;
                case 'h4': $structured_text .= "##### H4: " . $text . "\n\n"; break;
                case 'h5': $structured_text .= "###### H5: " . $text . "\n\n"; break;
                case 'h6': $structured_text .= "####### H6: " . $text . "\n\n"; break;
                case 'p': $structured_text .= "P: " . $text . "\n\n"; break;
                case 'li': $structured_text .= "- " . $text . "\n"; break; // لیست‌ها را با خط تیره مشخص می‌کنیم
                case 'span':
                case 'div':
                    $structured_text .= $text . "\n\n";
                    break;
                default:
                    break;
            }
        }
    }

    $structured_text = trim($structured_text);

    return $structured_text;
}

// --- منطق اصلی برنامه ---

// بررسی اینکه آیا محتوا قبلاً استخراج شده است یا خیر
if ($context_text === null) {
    $context_text = fetchStructuredText($url_to_process);
    if (is_string($context_text) && strpos($context_text, "خطا") === 0) {
        // اگر fetchStructuredText یک پیام خطا برگرداند
        $fetch_error = $context_text;
        $context_text = "هیچ اطلاعاتی از وبسایت استخراج نشد به دلیل خطا: " . $fetch_error;
        $fetch_success = false;
    } elseif (empty($context_text)) {
         $context_text = "هیچ اطلاعاتی از وبسایت استخراج نشد.";
         $fetch_success = true; // استخراج موفق بود اما محتوایی نبود
    } else {
        $fetch_success = true;
    }
}

$user_question = '';
$ai_response = '';

// بررسی اینکه آیا فرم ارسال شده است یا خیر (متد POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // دریافت سوال کاربر از فرم
    if (isset($_POST['user_question'])) {
        $user_question = trim($_POST['user_question']);

        if (!empty($user_question)) {
            // ساخت Prompt کامل برای هوش مصنوعی
            $full_prompt = "بر اساس متن زیر، به سوال پاسخ دهید. اگر پاسخ در متن ارائه شده نیست، بگویید که اطلاعات کافی در متن وجود ندارد:\n\n"
                         . "متن:\n" . $context_text . "\n\n"
                         . "سوال: " . $user_question . "\n"
                         . "پاسخ:";

            // ارسال Prompt به DeepAI API و دریافت پاسخ
            $ai_response = askDeepAI($full_prompt);

        } else {
            $ai_response = "لطفاً سوال خود را وارد کنید.";
        }
    } else {
         $ai_response = "خطا در دریافت سوال کاربر.";
    }
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چت با هوش مصنوعی بر اساس محتوای وبسایت</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
            color: #555;
        }
        .context-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e9e9e9;
            border-left: 4px solid #5cb85c;
            direction: rtl;
            text-align: right;
        }
         .context-info.error {
            border-left-color: #d9534f;
            background-color: #f2dede;
            color: #a94442;
         }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            direction: rtl;
            text-align: right;
        }
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding and border in element's total width and height */
            direction: rtl;
            text-align: right;
        }
        button {
            background-color: #5cb85c;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #4cae4c;
        }
        .ai-response {
            margin-top: 20px;
            padding: 15px;
            background-color: #d9edf7;
            border-left: 4px solid #3498db;
            border-radius: 4px;
            white-space: pre-wrap; /* Preserve whitespace and line breaks */
            direction: rtl;
            text-align: right;
        }
         .ai-response.error {
            border-left-color: #d9534f;
            background-color: #f2dede;
            color: #a94442;
         }
    </style>
</head>
<body>

<div class="container">
    <h1>چت با هوش مصنوعی</h1>
    <p style="text-align: center;">بر اساس محتوای وبسایت: <a href="<?php echo htmlspecialchars($url_to_process); ?>" target="_blank"><?php echo htmlspecialchars($url_to_process); ?></a></p>

    <div class="context-info <?php echo (isset($fetch_success) && !$fetch_success) ? 'error' : ''; ?>">
        <?php
        if (isset($fetch_success)) {
            if ($fetch_success) {
                echo "وضعیت استخراج محتوا: موفق (حدود " . strlen($context_text) . " کاراکتر).";
                 if (empty($context_text) || $context_text === "هیچ اطلاعاتی از وبسایت استخراج نشد.") {
                      echo " هشدار: محتوای قابل توجهی برای استفاده یافت نشد.";
                 }
            } else {
                echo "وضعیت استخراج محتوا: با خطا مواجه شد. " . $context_text; // نمایش پیام خطا
            }
        } else {
             echo "در حال تلاش برای استخراج محتوا..."; // این نباید نمایش داده شود اگر کد اجرا شده باشد
        }
        ?>
    </div>

    <form method="post" action="">
        <label for="user_question">سوال خود را بپرسید:</label>
        <textarea id="user_question" name="user_question" rows="4" required><?php echo htmlspecialchars($user_question); ?></textarea>
        <button type="submit">ارسال سوال</button>
    </form>

    <?php if (!empty($ai_response)): ?>
        <div class="ai-response <?php echo (strpos($ai_response, "خطا") === 0 || strpos($ai_response, "پاسخ نامعتبر") === 0) ? 'error' : ''; ?>">
            <h2>پاسخ هوش مصنوعی:</h2>
            <p><?php echo htmlspecialchars($ai_response); ?></p>
        </div>
    <?php endif; ?>

</div>

</body>
</html>