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
    <h1>تنظیمات</h1>
    <br>
    <div id="tabs">
        <?php
        if (isset($_GET['tab'])) {
            $active_tab = $_GET['tab'];
        }
        $pdp_option = get_option('pdp_options');
        ?>
        <h2 class="nav-tab-wrapper">
            <a style="margin-right: 0px;" href="?page=option_pdp&tab=general"
               class="nav-tab <?php echo @$active_tab == 'general' ? 'nav-tab-active' : ''; ?>">عمومی</a>
            <a style="margin-right: 0px;" href="?page=option_pdp&tab=forms"
               class="nav-tab <?php echo @$active_tab == 'forms' ? 'nav-tab-active' : ''; ?>">فرم پرداخت</a>
            <a style="margin-right: 0px;" href="?page=option_pdp&tab=notifications"
               class="nav-tab <?php echo @$active_tab == 'notifications' ? 'nav-tab-active' : ''; ?>">اطلاع رسانی ها</a>
        </h2>
        <form method="post" action="">
            <div id="tab_container">
                <?php if (isset($_GET['tab']) && $_GET['tab'] == "general"): ?>
                    <div class="tab_content" id="general">
                        <table class="form-table">
                            <tr valign="top">
                                <th colspan=2>
                                    <h4>تنظیمات درگاه پرداخت پی</h4>
                                </th>
                            </tr>
                            <tr valign="top">
                                <th>کد api</th>
                                <td>
                                    <input class="regular-text" id="pdp_api_pay"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_api_pay" value="<?php if (isset($pdp_option['api_pay'])) {
                                        echo $pdp_option['api_pay'];
                                    } ?>"/>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>آدرس صفحه پرداخت</th>
                                <td>
                                    <input class="regular-text" id="pdp_url_payment"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_url_payment" value="<?php if (isset($pdp_option['url_payment'])) {
                                        echo $pdp_option['url_payment'];
                                    } ?>"/>
                                </td>
                            </tr>
                        </table>
                        <?php do_action('pdp_general_settings', $pdp_option); ?>
                    </div><!--end #general-->
                <?php elseif (isset($_GET['tab']) && $_GET['tab'] == "forms"): ?>
                    <div class="tab_content" id="forms">
                        <table class="form-table">
                            <tr valign="top">
                                <th>
                                    <label for="rcp_settings[currency_position]">مخفی کردن توضیحات</label>
                                </th>
                                <td>
                                    <input name="pdp_hidden_description" type="checkbox" id="pdp_hidden_description"
                                           value="1"
                                        <?php
                                        echo $pdp_option['hidden_description'] ? 'checked' : ''
                                        ?>>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>متن توضیحات</th>
                                <td>
                                    <?php
                                    $content = $pdp_option['description_form'];
                                    wp_editor(wpautop($content), 'pdp_description_form', $settings = array('textarea_rows' => '10'));
                                    ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>متن توضیحات صفحه نتیجه پرداخت</th>
                                <td>
                                    <?php
                                    $content = $pdp_option['description_verify'];
                                    wp_editor(wpautop($content), 'pdp_description_verify', $settings = array('textarea_rows' => '10'));
                                    ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>رنگ دکمه</th>
                                <td>
                                    <input id="pdp_color_btn_payment"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_color_btn_payment" class="jscolor"
                                           value="<?php if (isset($pdp_option['color_btn_payment'])) echo $pdp_option['color_btn_payment'] ?>"/>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>متن دکمه</th>
                                <td>
                                    <input id="pdp_txt_btn_payment"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_txt_btn_payment"
                                           value="<?php if (isset($pdp_option['txt_btn_payment'])) echo $pdp_option['txt_btn_payment'] ?>"/>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>عنوان فرم پرداخت</th>
                                <td>
                                    <input id="pdp_title_form_payment"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_title_form_payment"
                                           value="<?php if (isset($pdp_option['title_form_payment'])) echo @$pdp_option['title_form_payment'] ?>"/>
                                </td>
                            </tr>
                        </table>
                        <?php do_action('pdp_forms_settings', @$pdp_option); ?>
                    </div><!--end #forms-->
                <?php elseif (isset($_GET['tab']) && $_GET['tab'] == "notifications"): ?>
                    <div class="tab_content" id="notifications">
                        <table class="form-table">
                            <tr valign="top">
                                <th>
                                    <label for="rcp_settings[currency_position]">سامانه پیامکی</label>
                                </th>
                                <td>
                                    <select id="pdp_panel_payamak" name="pdp_panel_payamak">
                                        <option value="activepayamak" <?php selected('activepayamak', @$pdp_option['panel_payamak']['panel_name']); ?>>
                                            اکتیو پیامک
                                        </option>

                                        <option value="ippanel" <?php selected('ippanel', @$pdp_option['panel_payamak']['panel_name']); ?>>
                                            ippanel
                                        </option>

                                        <option value="smsir" <?php selected('smsir', @$pdp_option['panel_payamak']['panel_name']); ?>>
                                            sms.ir
                                        </option>

                                        <option value="melipayamak" <?php selected('melipayamak', @$pdp_option['panel_payamak']['panel_name']); ?>>
                                            ملی پیامک
                                        </option>

                                        <option value="kavenegar" <?php selected('kavenegar', @$pdp_option['panel_payamak']['panel_name']); ?>>
                                            کاوه نگار
                                        </option>

                                        <option value="payamresan" <?php selected('payamresan', @$pdp_option['panel_payamak']['panel_name']); ?>>
                                            پیام رسان
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php
                                        switch ($pdp_option['panel_payamak']['panel_name'])
                                        {
                                            case 'kavenegar':
                                                echo 'برای استفاده از سامانه پیامک کاوه نگار شما APIKey که از سامانه کاوه نگار دریافت میکنید را در کادر نام کاربری سامانه وارد کنید پسورد سامانه نیاز نیست';
                                                break;
                                            case 'smsir':
                                                echo ' برای استفاده از سامانه پیامک sms.ir باید APIKey را در کادر نام کاربری سامانه و Security code را در کادر پسورد سامانه وارد کنید.';
                                                break;
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>
                                    <label for="rcp_settings[currency_position]">نام کاربری سامانه</label>
                                </th>
                                <td>
                                    <input class="regular-text" id="pdp_username_sms"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_username_sms"
                                           value="<?php if (isset($pdp_option['panel_payamak']['username_sms'])) echo @$pdp_option['panel_payamak']['username_sms'] ?>"/>
                                    <p class="description"></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>
                                    <label for="rcp_settings[currency_position]">پسورد سامانه</label>
                                </th>
                                <td>
                                    <input class="regular-text" id="pdp_pass_sms"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_pass_sms"
                                           value="<?php if (isset($pdp_option['panel_payamak']['pass_sms'])) echo @$pdp_option['panel_payamak']['pass_sms'] ?>"/>
                                    <p class="description"></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>
                                    <label for="rcp_settings[currency_position]">شماره سامانه پیامکی</label>
                                </th>
                                <td>
                                    <input class="regular-text" id="pdp_pass_sms"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_number_sms"
                                           value="<?php if (isset($pdp_option['panel_payamak']['number_sms'])) echo @$pdp_option['panel_payamak']['number_sms'] ?>"/>
                                    <p class="description"></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>
                                    <label for="rcp_settings[currency_position]">شماره موبایل مدیر</label>
                                </th>
                                <td>
                                    <input class="regular-text" id="pdp_mobile_number_admin_sms"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_mobile_number_admin_sms"
                                           value="<?php if (isset($pdp_option['panel_payamak']['mobile_number_admin_sms'])) echo @$pdp_option['panel_payamak']['mobile_number_admin_sms'] ?>"/>
                                    <p class="description"></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>
                                    <label for="pdp_active_number_users">ارسال پیامک به کاربر</label>
                                </th>
                                <td>
                                    <input name="pdp_active_number_users" type="checkbox" id="pdp_active_number_users" value="1"
                                        <?php
                                        if (isset($pdp_option['panel_payamak']['active_number_users']))echo  'checked';
                                        ?>>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>
                                    <label for="pdp_active_number_admin">ارسال پیامک به مدیر</label>
                                </th>
                                <td>
                                    <input name="pdp_active_number_admin" type="checkbox" id="pdp_active_number_admin" value="1"
                                        <?php
                                        if (isset($pdp_option['panel_payamak']['active_number_admin']))echo  'checked';
                                        ?>>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>
                                    <label for="pdp_text_sms_user">متن پیامک کاربر</label>
                                </th>
                                <td>
                                    <textarea name="pdp_text_sms_user" style="border-radius: 5px;padding: 5px;"
                                              id="pdp_text_sms_user" cols="30" rows="6"><?php if (isset($pdp_option['panel_payamak']['text_sms_user'])) echo $pdp_option['panel_payamak']['text_sms_user'] ?></textarea>
                                    <br>
                                    <code style="direction: rtl">
                                        نام پرداخت کننده : %name_user%
                                        مبلغ : %price_user%
                                        شناسه پیگیری : %tracking_id%
                                    </code>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>
                                    <label for="pdp_text_sms_admin">متن پیامک مدیر</label>
                                </th>
                                <td>
                                    <textarea name="pdp_text_sms_admin" id="pdp_text_sms_admin"
                                              style="border-radius: 5px;padding: 5px;" cols="30" rows="6"><?php if (isset($pdp_option['panel_payamak']['text_sms_admin'])) echo $pdp_option['panel_payamak']['text_sms_admin'] ?></textarea>
                                    <br>
                                    <code style="direction: rtl">
                                        نام پرداخت کننده : %name_user%
                                        مبلغ : %price_user%
                                        شناسه پیگیری : %tracking_id%
                                    </code>
                                </td>
                            </tr>

                        </table>
                        <?php do_action('pdp_notifications_settings', $pdp_option); ?>
                    </div><!--end #notifications-->
                <?php else: ?>
                    <div class="tab_content" id="general">
                        <table class="form-table">
                            <tr valign="top">
                                <th colspan=2>
                                    <h4>تنظیمات درگاه پرداخت پی</h4>
                                </th>
                            </tr>
                            <tr valign="top">
                                <th>کد api</th>
                                <td>
                                    <input class="regular-text" id="pdp_api_pay"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_api_pay" value="<?php if (isset($pdp_option['api_pay'])) {
                                        echo $pdp_option['api_pay'];
                                    } ?>"/>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>آدرس صفحه پرداخت</th>
                                <td>
                                    <input class="regular-text" id="pdp_url_payment"
                                           style="height: 30px;border-radius: 5px;padding: 17px;"
                                           name="pdp_url_payment" value="<?php if (isset($pdp_option['url_payment'])) {
                                        echo $pdp_option['url_payment'];
                                    } ?>"/>
                                </td>
                            </tr>
                        </table>
                        <?php do_action('pdp_general_settings', $pdp_option); ?>
                    </div><!--end #general-->
                <?php endif; ?>

            </div><!--end #tab_container-->
            <p class="submit">
                <input type="submit" name="pdp_save_option" class="button-primary" value="ذخیره تنظیمات"/>
            </p>

        </form>
    </div>
    <div class="clear"></div>
</div>
</body>
<script src="<?php echo PDP_ASSETS . 'js/uikit.min.js' ?>"></script>
<script src="<?php echo PDP_ASSETS . 'js/uikit-icons.min.js' ?>"></script>
<script type="text/javascript" src="<?php echo PDP_ASSETS . 'js/jquery-dataTables.js' ?>"></script>
<script type="text/javascript" src="<?php echo PDP_ASSETS . 'js/notify.js' ?>"></script>
<script type="text/javascript" src="<?php echo PDP_ASSETS . 'js/jscolor.js' ?>"></script>

</html>