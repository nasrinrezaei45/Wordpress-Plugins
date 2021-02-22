<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>فرم پرداخت</title>
    <link rel="stylesheet" href="<?php echo PDP_ASSETS . 'css/custom.css' ?>">
    <link rel="stylesheet" href="<?php echo PDP_ASSETS . 'css/uikit-rtl.min.css' ?>">
</head>
<body>
<?php
if ($pdp_option['hidden_description']) {
    $wd = 'uk-width-1-1@m';
} else {
    $wd = 'uk-width-1-2@m';
}
?>
<div class="uk-section">
    <div class="uk-container">
        <div class="box-pardakht">
            <div class="uk-grid">
                <?php if (!$pdp_option['hidden_description']): ?>
                    <div class="uk-width-1-2@m">
                        <div class="description">
                            <div class="title-description">توضیحات</div>
                            <?php echo wpautop($pdp_option['description_form']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="<?php echo $wd ?>">
                    <div class="form-payment">
                        <div class="title-form"><?php echo $pdp_option['title_form_payment']?></div>
                        <?php if ($hasEr): ?>
                            <?php foreach ($errormessage as $error): ?>
                                <div class="uk-alert-danger" uk-alert>
                                    <p style="font-size: 12px;
    font-weight: 500;"><?php echo $error; ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <form action="" method="post">
                            <fieldset class="uk-fieldset">
                                <div class="uk-margin">
                                    <input style="width:99% !important" class="uk-input" type="text"
                                           name="pdp_fullname_payment"
                                           value="<?php echo(isset($_POST['pdp_fullname_payment']) ? $_POST['pdp_fullname_payment'] : ''); ?>"
                                           placeholder="نام و نام خانوادگی پرداخت کننده">
                                </div>
                                <div class="uk-margin input-price">
                                    <span class="price-toman">تومان</span>
                                    <input style="width:99% !important" class="uk-input" type="number"
                                           name="pdp_price_payment"
                                           value="<?php echo(isset($_POST['pdp_price_payment']) ? $_POST['pdp_price_payment'] : ''); ?>"
                                           placeholder="مبلغ واریزی">
                                </div>
                                <div class="uk-margin">
                                    <input name="pdp_email_payment"
                                           value="<?php echo(isset($_POST['pdp_email_payment']) ? $_POST['pdp_email_payment'] : ''); ?>"
                                           class="uk-input" type="email" placeholder="ایمیل">
                                    <input name="pdp_mobile_payment" class="uk-input" type="number"
                                           value="<?php echo(isset($_POST['pdp_mobile_payment']) ? $_POST['pdp_mobile_payment'] : ''); ?>"
                                           placeholder="شماره موبایل">
                                </div>
                                <div class="uk-margin">
                                    <textarea class="uk-textarea" name="pdp_description_payment" rows="5"
                                              placeholder="توضیحات"><?php echo(isset($_POST['pdp_description_payment']) ? $_POST['pdp_description_payment'] : ''); ?></textarea>
                                </div>
                                <div class="uk-margin" style="text-align: center;;">
                                    <?php wp_nonce_field('pdp_payment_users', 'pdp_payment_users_nonce') ?>
                                    <button name="submit_payment"
                                            style="background: #<?php echo $pdp_option['color_btn_payment'] ?>"
                                            class="uk-button btn-payment"><span uk-icon="arrow-left"></span>
                                        <?php echo $pdp_option['txt_btn_payment'] ?>
                                    </button>
                                </div>
                            </fieldset>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script src="<?php echo PDP_ASSETS . 'js/uikit.min.js' ?>"></script>
<script src="<?php echo PDP_ASSETS . 'js/uikit-icons.min.js' ?>"></script>
</html>