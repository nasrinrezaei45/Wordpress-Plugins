<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>نتیجه پرداخت</title>
    <link rel="stylesheet" href="<?php echo PDP_ASSETS . 'css/custom.css' ?>">
    <link rel="stylesheet" href="<?php echo PDP_ASSETS . 'css/uikit-rtl.min.css' ?>">
    <style>
        .btn-link:hover {
            text-decoration: none;
            color: #fff;
        }

        .btn-link {
            color: #fff;
        }
    </style>
</head>
<body>
<div class="uk-section">
    <div class="uk-container">
        <div class="box-pardakht">
            <div class="uk-grid">
                <div class="uk-width-1-1@m">
                    <?php if ($success): ?>
                        <div style="text-align: center" class="form-payment">
                            <img style="width: 9%;text-align: center;margin-bottom: 35px;"
                                 src="<?php echo PDP_ASSETS . 'img/verified-account.png' ?>" alt="">
                            <br>
                            <div style="color: #0eda2b;font-weight: bold;text-align: center;font-size: 25px;" class="">
                                پرداخت با موفقیت انجام شد
                            </div>
                            <div style="padding: 22px 25px 10px;background: #f5f5f5;border-radius: 5px;margin-top: 11px;">
                                <p style="text-align: right;color: orange;"><span style="margin-left: 6px;"
                                                                                  uk-icon="tag"></span>مبلغ
                                    : <?php echo $price . '  تومان ' ?></p>
                                <p style="text-align: right"><span style="margin-left: 6px;" uk-icon="database"></span>شناسه
                                    پرداخت : <?php echo $ref_num ?></p>
                                <p style="text-align: right;    color: #0bca0b;"><span style="margin-left: 6px;"
                                                                                       uk-icon="calendar"></span>تاریخ
                                    : <?php echo pdp_persian_number(date_i18n('Y/m/d', strtotime($date_paid))) ?>
                                </p>
                                <p style="text-align: right"></p>
                            </div>
                            <div style="text-align: right;font-size: 13px;margin-top: 35px;">
                                <?php echo wpautop($pdp_option['description_verify']) ?>
                            </div>
                            <div style="color: #095bf1;" id="countDown">00:</div>
                            <button name="submit_payment"
                                    style="background: #<?php echo $pdp_option['color_btn_payment'] ?>"
                                    class="uk-button btn-payment">
                                <a class="btn-link" href="<?php echo esc_url(home_url()); ?>">بازگشت به سایت</a>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div style="text-align: center" class="form-payment">
                            <br>
                            <br>
                            <div style="color: #da0300;font-weight: bold;text-align: center;font-size: 25px;" class="">
                                پرداخت شما ناموفق بود
                            </div>
                            <br>
                            <br>
                            <button style="background: #da0300" name="submit_payment"
                                    style="background: #<?php echo $pdp_option['color_btn_payment'] ?>"
                                    class="uk-button btn-payment">
                                <a class="btn-link" href="<?php echo esc_url(home_url()); ?>">بازگشت به سایت</a>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script src="<?php echo PDP_ASSETS . 'js/uikit.min.js' ?>"></script>
<script src="<?php echo PDP_ASSETS . 'js/uikit-icons.min.js' ?>"></script>
<script>
    //var count = 20;
    //var timeValue = setInterval(function(){
    //    count--;
    //    document.getElementById('countDown').innerHTML = "00:"+count;
    //    if (count <= 0) {
    //        clearInterval(timeValue);
    //        document.getElementById('countDown').innerHTML = "00:00";
    //        window.location = '<?php //echo esc_url(home_url())?>//';
    //    }
    //},1000);

</script>

</html>