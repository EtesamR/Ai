<?php

// نیاز به کلید API DeepAI دارید
// !!! هشدار: کلید API خود را در کد منبع عمومی قرار ندهید!
// از متغیرهای محیطی یا فایل‌های پیکربندی امن استفاده کنید در محیط تولید.
$openai_api_key = 'Yaa6a3f47-f8d1-46f7-a9b4-3be1a5a75116'; // کلید خود را اینجا قرار دهید!

/**
 * Sends a prompt to the DeepAI Chat Completion API.
 *
 * @param string $prompt The prompt including context and user question.
 * @return string|false The AI's response text, or false on failure.
 */
function askDeepAI($prompt) {
    global $openai_api_key;

    if (empty($openai_api_key) || $openai_api_key === 'YOUR_OPENAI_API_KEY') {
        echo "خطا: کلید DeepAI API تنظیم نشده است یا پیش فرض است.\n";
        return false;
    }

    // اطمینان از اینکه از DeepAI API استفاده می‌کنید، نه DeepAI API (بر اساس Endpoint در کد شما)
    // اگر قصد استفاده از DeepAI API را دارید، Endpoint و ساختار درخواست متفاوت خواهد بود.
    // با توجه به Endpoint 'https://api.openai.com/v1/chat/completions'، این کد برای DeepAI API است.
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
        echo "خطا در ارسال درخواست به API هوش مصنوعی (cURL error): " . $err . "\n";
        return false;
    }

    if ($http_code !== 200) {
        echo "خطا از API هوش مصنوعی (HTTP status code " . $http_code . "): " . $response . "\n"; // نمایش پاسخ خطا از API
        return false;
    }

    $responseData = json_decode($response, true);

    // بررسی اینکه آیا پاسخ معتبر است و حاوی پیام است
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    } else {
        echo "خطا: پاسخ نامعتبر از API هوش مصنوعی.\n";
        // Optional: Log the full response for debugging
        // error_log("Invalid AI API response: " . $response);
        // اگر پاسخ خطا حاوی اطلاعات مفیدی بود، آن را نمایش دهید
        if ($response) {
             echo "پاسخ خام API: " . $response . "\n";
        }
        return false;
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
        echo "خطا: URL نامعتبر است. \n";
        return false;
    }

    echo "در حال دریافت محتوای " . $url . "...\n";

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
        echo "خطا در دریافت محتوا با cURL: " . $err . "\n";
        return false;
    }

    if ($http_code >= 400) {
        echo "خطا در دریافت محتوا: کد وضعیت HTTP " . $http_code . " برای " . $url . "\n";
        return false;
    }

    if (empty($html)) {
         echo "هشدار: محتوای دریافتی از URL خالی است.\n";
         return ""; // بازگشت رشته خالی به جای false برای اینکه کد ادامه پیدا کند
    }

    echo "محتوا با موفقیت دریافت شد. در حال پردازش...\n";

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
         echo "هشدار: هیچ عنصر متنی اصلی در صفحه یافت نشد.\n";
         return ""; // بازگشت رشته خالی
    }

    echo "عناصر متنی یافت شدند. در حال استخراج و ساختاردهی...\n";

    foreach ($elements as $element) {
        $tag_name = strtolower($element->tagName);
        $text = trim($element->textContent);
        // حذف تگ‌های HTML باقی‌مانده احتمالی و چندین فضای خالی
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text); // دوباره trim برای حذف فضای خالی اضافی در ابتدا/انتهای متن

        if (!empty($text)) {
            // اضافه کردن منطق برای جلوگیری از تکرار متن والدین در فرزندان
            // (این بخش پیچیده است و نیاز به پیمایش DOM دارد. برای سادگی، فقط متن عناصر را اضافه می‌کنیم)

            switch ($tag_name) {
                case 'h1': $structured_text .= "## H1: " . $text . "\n\n"; break;
                case 'h2': $structured_text .= "### H2: " . $text . "\n\n"; break;
                case 'h3': $structured_text .= "#### H3: " . $text . "\n\n"; break;
                case 'h4': $structured_text .= "##### H4: " . $text . "\n\n"; break;
                case 'h5': $structured_text .= "###### H5: " . $text . "\n\n"; break;
                case 'h6': $structured_text .= "####### H6: " . $text . "\n\n"; break;
                case 'p': $structured_text .= "P: " . $text . "\n\n"; break;
                case 'li': $structured_text .= "- " . $text . "\n"; break; // لیست‌ها را با خط تیره مشخص می‌کنیم
                // می‌توانید تگ‌های دیگر مانند span یا div را نیز اضافه کنید، اما ممکن است نویز زیادی ایجاد کنند
                case 'span':
                case 'div':
                    // فقط متنی را از span/div اضافه می‌کنیم که قبلاً در عناصر دیگر (h, p, li) پوشش داده نشده باشد.
                    // پیاده‌سازی دقیق این کار دشوار است. برای شروع، فقط متن را اضافه می‌کنیم اگر به نظر مهم بیاید.
                    // ممکن است نیاز به فیلترینگ بیشتر داشته باشد.
                    $structured_text .= $text . "\n\n";
                    break;
                default:
                    // در صورت نیاز می‌توانید تگ‌های دیگر را نیز اضافه کنید
                    // $structured_text .= strtoupper($tag_name) . ": " . $text . "\n\n";
                    break;
            }
        }
    }

    $structured_text = trim($structured_text);

    echo "استخراج و ساختاردهی محتوا به پایان رسید.\n";
    return $structured_text;
}


// --- منطق اصلی برنامه ---

$url_to_process = "https://etesamr.github.io/pachatbotpro1/"; // جایگزین با آدرس مورد نظر خود

// مرحله 1: استخراج محتوا
$context_text = fetchStructuredText($url_to_process);

if ($context_text === false) {
    echo "عملیات استخراج محتوا با خطا مواجه شد.\n";
    exit; // پایان برنامه اگر استخراج موفقیت آمیز نبود
}

if (empty($context_text)) {
    echo "محتوایی برای استفاده به عنوان زمینه یافت نشد.\n";
    $context_text = "هیچ اطلاعاتی از وبسایت استخراج نشد."; // یک زمینه پیش فرض برای اطلاع رسانی به مدل
} else {
    echo "محتوای استخراج شده برای استفاده به عنوان زمینه آماده است (حدود " . strlen($context_text) . " کاراکتر).\n";
    // برای بررسی محتوای استخراج شده (اختیاری)
    // echo "\n--- محتوای استخراج شده ---\n";
    // echo $context_text;
    // echo "\n------------------------\n";
}


// مرحله 2: آماده سازی برای دریافت سوال کاربر
// در یک محیط واقعی، این بخش در یک فرم HTML و با ارسال POST انجام می‌شود.
// برای این مثال، ما یک سوال ثابت را استفاده می‌کنیم.
$user_question = "درباره سامانه رزرو بلیط توضیح بده."; // سوال کاربر را اینجا قرار دهید.

echo "\nسوال کاربر: " . $user_question . "\n";

// ساخت Prompt کامل برای هوش مصنوعی
// Prompt شامل دستورالعمل، زمینه و سوال کاربر است.
$full_prompt = "بر اساس متن زیر، به سوال پاسخ دهید. اگر پاسخ در متن ارائه شده نیست، بگویید که اطلاعات کافی در متن وجود ندارد:\n\n"
             . "متن:\n" . $context_text . "\n\n"
             . "سوال: " . $user_question . "\n"
             . "پاسخ:"; // می‌توانید این بخش را برای هدایت بهتر پاسخ تغییر دهید.

// مرحله 3: ارسال Prompt به DeepAI API و دریافت پاسخ
echo "در حال ارسال سوال به هوش مصنوعی...\n";
$ai_response = askDeepAI($full_prompt);

// مرحله 4: نمایش پاسخ هوش مصنوعی
if ($ai_response !== false) {
    echo "\n--- پاسخ هوش مصنوعی ---\n";
    echo $ai_response . "\n";
    echo "-----------------------\n";
} else {
    echo "\nدریافت پاسخ از هوش مصنوعی با خطا مواجه شد.\n";
}

?>