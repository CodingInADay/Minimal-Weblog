<?php
// نمایش خطاها برای دیباگ (بعد از تست خاموش کن)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();

// تابع تولید نام فایل
function generateFileName() {
    return date('YmdHis');
}

// تابع بازنویسی index و archive
function updateIndexAndArchive() {
    $posts = glob('posts/*.htm');
    rsort($posts);

    // خواندن تنظیمات
    $settings = file_exists('admin/settings.txt') ? json_decode(file_get_contents('admin/settings.txt'), true) : ['post_count' => 3, 'header_text_color' => '#000000', 'footer_text_color' => '#000000'];
    $postCount = $settings['post_count'] ?? 3;
    $headerTextColor = $settings['header_text_color'] ?? '#000000';
    $footerTextColor = $settings['footer_text_color'] ?? '#000000';

    // بازنویسی index.htm
    $headerContent = file_exists('header.htm') ? file_get_contents('header.htm') : "<div style='background-color: #D3D3D3; width: 100%; text-align: center; padding: 10px 0; margin: 0; color: $headerTextColor;'>سایت من</div>";
    $indexContent = "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><link href='https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap' rel='stylesheet'><style>body { font-family: 'Vazirmatn', sans-serif; margin: 0; padding: 0; min-height: 100vh; display: flex; flex-direction: column; } .container { max-width: 1200px; margin: 0 auto; padding: 0 10px; flex: 1; } .latest-post img { width: 50%; max-width: 600px; display: block; margin: 10px auto; } .mosaic-container { border: 1px solid #ccc; padding: 10px; margin: 20px 0; } .mosaic { display: grid; grid-template-columns: repeat($postCount, 1fr); gap: 10px; } .mosaic a, a { text-align: center; text-decoration: none; color: #333; } .mosaic img { width: 100%; max-width: 150px; } footer { background-color: #D3D3D3; color: $footerTextColor; text-align: center; padding: 10px 0; margin-top: auto; } @media (max-width: 600px) { .latest-post img { width: 100%; } .mosaic { grid-template-columns: 1fr; } }</style></head><body><div class='container'>$headerContent";
    if ($posts) {
        $latestPostContent = file_get_contents($posts[0]);
        // اصلاح مسیر عکس پست آخر به صورت دقیق و تمیز
        $postFileName = basename($posts[0], '.htm');
        $latestPostContent = preg_replace('/<img src=["\'].*?["\']/i', "<img src='./images/$postFileName.jpg'", $latestPostContent);
        $indexContent .= "<div class='latest-post'>" . $latestPostContent . "</div><div class='mosaic-container'><div class='mosaic'>";
        $count = 0;
        foreach (array_slice($posts, 1) as $post) {
            if ($count < $postCount) {
                $postContent = file_get_contents($post);
                preg_match('/<h1>(.*?)<\/h1>/', $postContent, $postTitle);
                $postFileName = basename($post, '.htm');
                $indexContent .= "<a href='./posts/" . basename($post) . "' target='_blank'><div>" . htmlspecialchars($postTitle[1] ?? 'بدون عنوان') . "</div><img src='./images/$postFileName.jpg'></a>";
                $count++;
            }
        }
        $indexContent .= "</div></div>";
    }
    $indexContent .= "</div><footer><object data='footer.htm' type='text/html'></object></footer></body></html>";
    if (file_put_contents('index.htm', $indexContent) === false) {
        die("خطا در نوشتن index.htm. دسترسی فایل رو چک کنید.");
    }

    // بازنویسی archive.htm
    $archiveContent = "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><link href='https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap' rel='stylesheet'><style>body { font-family: 'Vazirmatn', sans-serif; margin: 0; padding: 0; min-height: 100vh; display: flex; flex-direction: column; } .container { max-width: 1200px; margin: 0 auto; padding: 10px; flex: 1; } .archive { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; } .archive a, a { text-align: center; text-decoration: none; color: #333; } .archive img { width: 100%; max-width: 100px; } footer { background-color: #D3D3D3; color: $footerTextColor; text-align: center; padding: 10px 0; margin-top: auto; } @media (max-width: 600px) { .archive { grid-template-columns: 1fr; } }</style></head><body><div class='container'><div class='archive'>";
    foreach ($posts as $post) {
        $postContent = file_get_contents($post);
        preg_match('/<h1>(.*?)<\/h1>/', $postContent, $postTitle);
        $postFileName = basename($post, '.htm');
        $archiveContent .= "<a href='./posts/" . basename($post) . "' target='_blank'><img src='./images/$postFileName.jpg' loading='lazy'><div>" . htmlspecialchars($postTitle[1] ?? 'بدون عنوان') . "</div></a>";
    }
    $archiveContent .= "</div></div><footer><object data='footer.htm' type='text/html'></object></footer></body></html>";
    if (file_put_contents('archive.htm', $archiveContent) === false) {
        die("خطا در نوشتن archive.htm. دسترسی فایل رو چک کنید.");
    }

    // اصلاح ساختار پست‌ها برای نمایش درست (نگه داشتن ../images/ برای خود فایل‌های پست)
    foreach ($posts as $post) {
        $postContent = file_get_contents($post);
        preg_match('/<h1>(.*?)<\/h1>/', $postContent, $title);
        preg_match('/<p>تاریخ: (.*?)<\/p>/', $postContent, $date);
        preg_match('/<div>(.*?)<\/div>/s', $postContent, $text);
        $postFileName = basename($post, '.htm');
        $newPostContent = "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><link href='https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap' rel='stylesheet'><style>body { font-family: 'Vazirmatn', sans-serif; margin: 0; padding: 0; } .container { max-width: 1200px; margin: 0 auto; padding: 10px; } .container div { font-family: 'Vazirmatn', sans-serif; } img { max-width: 100%; height: auto; display: block; margin: 10px auto; } a { text-decoration: none; color: #333; }</style></head><body><div class='container'><h1>" . htmlspecialchars($title[1] ?? 'بدون عنوان') . "</h1><p>تاریخ: " . htmlspecialchars($date[1] ?? '') . "</p><img src='../images/$postFileName.jpg' alt='" . htmlspecialchars($title[1] ?? 'بدون عنوان') . "'><div>" . ($text[1] ?? '') . "</div></div></body></html>";
        file_put_contents($post, $newPostContent);
    }
}

// اطمینان از وجود پوشه‌ها
$dirs = ['admin', 'posts', 'images'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// چک کردن ورود کاربر
if (!isset($_SESSION['logged_in'])) {
    if (!file_exists('admin/admin.txt')) {
        if (isset($_POST['username']) && isset($_POST['password'])) {
            if ($_POST['username'] == 'admin' && $_POST['password'] == 'password') {
                if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
                    $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    if (file_put_contents('admin/admin.txt', $hashed) === false) {
                        die("خطا در نوشتن فایل admin.txt. دسترسی پوشه admin رو چک کنید یا مطمئن شوید که پوشه وجود داره.");
                    }
                    $_SESSION['logged_in'] = true;
                    header('Location: dashboard.php');
                    exit;
                } else {
                    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap" rel="stylesheet"><style>body { font-family: \'Vazirmatn\', sans-serif; padding: 20px; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f5f5f5; } form { max-width: 400px; width: 100%; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); } input { width: 100%; margin: 10px 0; padding: 10px; box-sizing: border-box; font-size: 16px; } input[type="submit"] { background: #007BFF; color: #fff; border: none; cursor: pointer; } input[type="submit"]:hover { background: #0056b3; } @media (max-width: 600px) { form { padding: 15px; } input { font-size: 14px; padding: 8px; } }</style></head><body>';
                    echo '<form method="post">
                        <input type="hidden" name="username" value="admin">
                        <input type="hidden" name="password" value="password">
                        رمز جدید: <input type="password" name="new_password" required><br>
                        <input type="submit" value="ذخیره">
                    </form>';
                    echo '</body></html>';
                    exit;
                }
            } else {
                echo "نام کاربری یا رمز اشتباه است!";
            }
        }
        echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap" rel="stylesheet"><style>body { font-family: \'Vazirmatn\', sans-serif; padding: 20px; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f5f5f5; } form { max-width: 400px; width: 100%; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); } input { width: 100%; margin: 10px 0; padding: 10px; box-sizing: border-box; font-size: 16px; } input[type="submit"] { background: #007BFF; color: #fff; border: none; cursor: pointer; } input[type="submit"]:hover { background: #0056b3; } @media (max-width: 600px) { form { padding: 15px; } input { font-size: 14px; padding: 8px; } }</style></head><body>';
        echo '<form method="post">
            نام کاربری: <input type="text" name="username" required><br>
            رمز: <input type="password" name="password" required><br>
            <input type="submit" value="ورود">
        </form>';
        echo '</body></html>';
        exit;
    } else {
        $hash = file_get_contents('admin/admin.txt');
        if ($hash === false) {
            die("خطا در خواندن admin.txt. فایل وجود داره؟");
        }
        if (isset($_POST['password']) && password_verify($_POST['password'], $hash)) {
            $_SESSION['logged_in'] = true;
            header('Location: dashboard.php');
            exit;
        } else {
            echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap" rel="stylesheet"><style>body { font-family: \'Vazirmatn\', sans-serif; padding: 20px; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f5f5f5; } form { max-width: 400px; width: 100%; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); } input { width: 100%; margin: 10px 0; padding: 10px; box-sizing: border-box; font-size: 16px; } input[type="submit"] { background: #007BFF; color: #fff; border: none; cursor: pointer; } input[type="submit"]:hover { background: #0056b3; } @media (max-width: 600px) { form { padding: 15px; } input { font-size: 14px; padding: 8px; } }</style></head><body>';
            if (isset($_POST['password'])) {
                echo "رمز اشتباه است!<br>";
            }
            echo '<form method="post">
                رمز: <input type="password" name="password" required><br>
                <input type="submit" value="ورود">
            </form>';
            echo '</body></html>';
            exit;
        }
    }
}

// داشبورد
$headerContent = file_exists('header.htm') ? file_get_contents('header.htm') : '<div style="background-color: #D3D3D3; width: 100%; text-align: center; padding: 10px 0; margin: 0;">سایت من</div>';
preg_match('/>(.*?)</', $headerContent, $siteNameMatch);
$siteName = $siteNameMatch[1] ?? 'سایت من';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت - <?php echo htmlspecialchars($siteName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; padding: 10px; margin: 0; }
        h1 { text-align: center; margin: 10px 0; }
        .menu { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        .menu a { padding: 10px; background: #eee; text-decoration: none; color: #333; border-radius: 5px; }
        form { max-width: 700px; margin: 0 auto; }
        input, textarea { width: 100%; margin: 5px 0; padding: 8px; box-sizing: border-box; }
        .mosaic { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .mosaic a { text-align: center; }
        .editor { border: 1px solid #ccc; padding: 10px; min-height: 200px; direction: rtl; }
        .toolbar { margin-bottom: 5px; }
        .toolbar button { padding: 5px; margin: 0 2px; }
        @media (max-width: 600px) {
            .menu a { width: 45%; text-align: center; }
            input, textarea, .editor { font-size: 16px; }
            .mosaic { grid-template-columns: 1fr; }
            .toolbar button { padding: 8px; }
        }
    </style>
    <script>
        function formatText(command, value = null) {
            document.execCommand(command, false, value);
            document.getElementById('content').focus();
        }
        function loadContent() {
            let textarea = document.querySelector('textarea[name="content"]');
            if (textarea) {
                document.getElementById('content').innerHTML = textarea.value;
            }
        }
        function saveContent() {
            let editor = document.getElementById('content');
            let textarea = document.querySelector('textarea[name="content"]');
            if (textarea) textarea.value = editor.innerHTML;
        }
    </script>
</head>
<body onload="loadContent()">
    <h1><?php echo htmlspecialchars($siteName); ?></h1>
    <div class="menu">
        <a href="?action=add">ورود اطلاعات</a>
        <a href="?action=edit">ویرایش اطلاعات</a>
        <a href="?action=delete">حذف اطلاعات</a>
        <a href="?action=settings">تنظیمات سایت</a>
        <a href="?action=logout">خروج</a>
    </div>

<?php
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. ورود اطلاعات
if ($action == 'add') {
    if (isset($_POST['submit'])) {
        $title = htmlspecialchars($_POST['title']);
        $content = $_POST['content'];
        $date = $_POST['date'];
        $filename = generateFileName();
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $imagePath = 'images/' . $filename . '.jpg';
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                echo "خطا در آپلود تصویر!";
                exit;
            }
        }

        $postContent = "<h1>$title</h1><p>تاریخ: $date</p><img src='../images/$filename.jpg' alt='$title'><div>$content</div>";
        if (file_put_contents("posts/$filename.htm", $postContent) === false) {
            echo "خطا در نوشتن فایل پست!";
            exit;
        }
        updateIndexAndArchive();
        echo "پست با موفقیت ذخیره شد!";
    } else {
        echo '<form method="post" enctype="multipart/form-data" onsubmit="saveContent()">
            عنوان: <input type="text" name="title" required><br>
            متن: <div class="toolbar">
                <button type="button" onclick="formatText(\'bold\')">بولد</button>
                <button type="button" onclick="formatText(\'italic\')">ایتالیک</button>
                <button type="button" onclick="formatText(\'foreColor\', \'red\')">قرمز</button>
                <button type="button" onclick="formatText(\'insertUnorderedList\')">لیست</button>
            </div>
            <div class="editor" id="content" contenteditable="true"></div>
            <textarea name="content" style="display:none;"></textarea><br>
            تصویر: <input type="file" name="image" accept="image/jpeg"><br>
            تاریخ: <input type="date" name="date" value="' . date('Y-m-d') . '" required><br>
            <input type="submit" name="submit" value="ذخیره">
        </form>';
    }
}

// 2. ویرایش اطلاعات
elseif ($action == 'edit') {
    if (isset($_GET['file']) && isset($_POST['submit'])) {
        $file = $_GET['file'];
        $title = htmlspecialchars($_POST['title']);
        $content = $_POST['content'];
        $date = $_POST['date'];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $imagePath = 'images/' . basename($file, '.htm') . '.jpg';
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        }

        $postContent = "<h1>$title</h1><p>تاریخ: $date</p><img src='../images/" . basename($file, '.htm') . ".jpg' alt='$title'><div>$content</div>";
        if (file_put_contents("posts/$file", $postContent) === false) {
            echo "خطا در نوشتن فایل پست!";
            exit;
        }
        updateIndexAndArchive();
        echo "پست ویرایش شد!";
    } elseif (isset($_GET['file'])) {
        $file = $_GET['file'];
        $content = file_get_contents("posts/$file");
        preg_match('/<h1>(.*?)<\/h1>/', $content, $title);
        preg_match('/<p>تاریخ: (.*?)<\/p>/', $content, $date);
        preg_match('/<div>(.*?)<\/div>/s', $content, $text);
        echo '<form method="post" enctype="multipart/form-data" onsubmit="saveContent()">
            عنوان: <input type="text" name="title" value="' . htmlspecialchars($title[1] ?? '') . '" required><br>
            متن: <div class="toolbar">
                <button type="button" onclick="formatText(\'bold\')">بولد</button>
                <button type="button" onclick="formatText(\'italic\')">ایتالیک</button>
                <button type="button" onclick="formatText(\'foreColor\', \'red\')">قرمز</button>
                <button type="button" onclick="formatText(\'insertUnorderedList\')">لیست</button>
            </div>
            <div class="editor" id="content" contenteditable="true">' . ($text[1] ?? '') . '</div>
            <textarea name="content" style="display:none;">' . ($text[1] ?? '') . '</textarea><br>
            تصویر جدید: <input type="file" name="image" accept="image/jpeg"><br>
            تاریخ: <input type="date" name="date" value="' . ($date[1] ?? '') . '" required><br>
            <input type="submit" name="submit" value="ویرایش">
        </form>';
    } else {
        $posts = glob('posts/*.htm');
        foreach ($posts as $post) {
            $content = file_get_contents($post);
            preg_match('/<h1>(.*?)<\/h1>/', $content, $title);
            echo "<a href='?action=edit&file=" . basename($post) . "'>" . htmlspecialchars($title[1] ?? 'بدون عنوان') . "</a><br>";
        }
    }
}

// 3. حذف اطلاعات
elseif ($action == 'delete') {
    if (isset($_GET['file']) && isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
        $file = $_GET['file'];
        unlink("posts/$file");
        unlink("images/" . basename($file, '.htm') . ".jpg");
        updateIndexAndArchive();
        echo "پست حذف شد!";
    } elseif (isset($_GET['file'])) {
        echo '<form method="post">
            آیا مطمئن هستید؟ <input type="hidden" name="confirm" value="yes"><input type="submit" value="بله">
        </form>';
    } else {
        $posts = glob('posts/*.htm');
        foreach ($posts as $post) {
            $content = file_get_contents($post);
            preg_match('/<h1>(.*?)<\/h1>/', $content, $title);
            echo "<a href='?action=delete&file=" . basename($post) . "'>" . htmlspecialchars($title[1] ?? 'بدون عنوان') . "</a><br>";
        }
    }
}

// 4. تنظیمات سایت
elseif ($action == 'settings') {
    if (isset($_POST['submit'])) {
        $siteName = htmlspecialchars($_POST['site_name']);
        $email = htmlspecialchars($_POST['email']);
        $headerColor = $_POST['header_color'];
        $footerColor = $_POST['footer_color'];
        $headerTextColor = $_POST['header_text_color'];
        $footerTextColor = $_POST['footer_text_color'];
        $postCount = (int)$_POST['post_count'];

        if (file_put_contents('header.htm', "<div style='background-color: $headerColor; width: 100%; text-align: center; padding: 10px 0; margin: 0; color: $headerTextColor;'>$siteName</div>") === false) {
            echo "خطا در نوشتن header.htm!";
            exit;
        }
        if (file_put_contents('footer.htm', "<div style='background-color: $footerColor; text-align: center; padding: 10px;'><a href='archive.htm' target='_blank'>آرشیو</a> | <a href='mailto:$email'>تماس با ما</a></div>") === false) {
            echo "خطا در نوشتن footer.htm!";
            exit;
        }

        // ذخیره تنظیمات در admin/settings.txt
        $settings = [
            'site_name' => $siteName,
            'email' => $email,
            'header_color' => $headerColor,
            'footer_color' => $footerColor,
            'header_text_color' => $headerTextColor,
            'footer_text_color' => $footerTextColor,
            'post_count' => $postCount
        ];
        file_put_contents('admin/settings.txt', json_encode($settings));

        if (!empty($_POST['new_password'])) {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            if (file_put_contents('admin/admin.txt', $hashed) === false) {
                echo "خطا در نوشتن admin.txt!";
                exit;
            }
        }

        updateIndexAndArchive();
        echo "تنظیمات ذخیره شد!";
    } else {
        $settings = file_exists('admin/settings.txt') ? json_decode(file_get_contents('admin/settings.txt'), true) : [];
        $defaultSettings = [
            'site_name' => 'سایت من',
            'email' => '#',
            'header_color' => '#D3D3D3',
            'footer_color' => '#D3D3D3',
            'header_text_color' => '#000000',
            'footer_text_color' => '#000000',
            'post_count' => 3
        ];
        $settings = array_merge($defaultSettings, $settings);

        echo '<form method="post">
            نام سایت: <input type="text" name="site_name" value="' . htmlspecialchars($settings['site_name']) . '" required><br>
            ایمیل تماس: <input type="email" name="email" value="' . htmlspecialchars($settings['email']) . '" required><br>
            رنگ هدر: <input type="color" name="header_color" value="' . $settings['header_color'] . '"><br>
            رنگ فوتر: <input type="color" name="footer_color" value="' . $settings['footer_color'] . '"><br>
            رنگ متن هدر: <input type="color" name="header_text_color" value="' . $settings['header_text_color'] . '"><br>
            رنگ متن فوتر: <input type="color" name="footer_text_color" value="' . $settings['footer_text_color'] . '"><br>
            تعداد پست‌های موزاییکی: <input type="number" name="post_count" value="' . $settings['post_count'] . '" min="1"><br>
            رمز جدید (اختیاری): <input type="password" name="new_password"><br>
            <input type="submit" name="submit" value="ذخیره">
        </form>';
    }
}

// 5. خروج
elseif ($action == 'logout') {
    session_destroy();
    header('Location: dashboard.php');
    exit;
}

?>
</body>
</html>