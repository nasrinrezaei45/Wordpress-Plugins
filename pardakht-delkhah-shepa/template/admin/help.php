<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="<?php echo PDP_ASSETS . 'css/admin.css' ?>">
    <link rel="stylesheet" href="<?php echo PDP_ASSETS . 'css/uikit-rtl.min.css' ?>">
    <style>
        table {
            background: transparent;
        }

        .wrap {
            direction: rtl;

        }

        #tabs {
            margin-bottom: 45px;
        }

        #wpcontent {
            background: #f1f1f1;
        }
    </style>
</head>
<body>
<div class="wrap">
    <h4>ساخت برگه پرداخت</h4>
    <p>برای ساخت برگه پرداخت به تنظیمات مراجعه کنید از تب عمومی گزینه آدرس صفحه پرداخت را ثبت می کنید به عنوان مثال
        payment را داخل کادر آدرس صفحه پرداخت ثبت می کنید آدرس صفحه پرداخت شما به این <?php echo home_url('payment') ?>
        صورت می باشد برای نمایش فرم پرداخت از شورتکد استفاده نمی شود </p>

    <h4>افزونه وردپرس فارسی</h4>
    <p>جهت تبدیل تاریخ میلادی به شمسی این افزونه نیازمند افزونه وردپرس فارسی(wp-jalali) می باشد این افزونه را نصب بفرمایید</p>

    <h4>نظرات و پیشنهادات</h4>
    <p>نظرات و پیشنهادات خود را میتوانید از طریق آدرس ایمیل support@themelavin.ir برای ما ارسال نمایید.</p>
</div>
</body>
<script src="<?php echo PDP_ASSETS . 'js/uikit.min.js' ?>"></script>
<script src="<?php echo PDP_ASSETS . 'js/uikit-icons.min.js' ?>"></script>
</html>