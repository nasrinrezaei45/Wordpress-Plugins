<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="<?php echo PDP_ASSETS . 'css/admin.css' ?>">
    <link rel="stylesheet" href="<?php echo PDP_ASSETS . 'css/uikit-rtl.min.css' ?>">
    <style>
        #wpcontent {
            background: #f1f1f1;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="uk-child-width-1-3@s uk-grid-match" uk-grid>
        <div>
            <div class="uk-card uk-card-default uk-card-hover uk-card-body">
                <div class="icon-total">
                    <span uk-icon="database"></span>
                </div>
                <div class="content-total">
                    <h3>مجموع کل پرداختی ها</h3>
                    <p class="total-payment">
                        <?php
                        if ($total[0]->result_value)
                        {
                            echo pdp_persian_number($total[0]->result_value) . ' تومان ';
                        }else{
                            echo 0 . ' تومان ';
                        }

                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <h1>لیست پرداخت ها</h1>
    <br>
    <div uk-grid>
        <div class="uk-width-3-3 filter-m">
            <div class="filter_box">
                <form id="search-box" action="" method="get">
                    <input type="hidden" name="page" value="pardakt_delkhah_pay">
                    <input class="uk-input uk-form-width-medium" type="text" name="query"
                           placeholder="جستجو با شناسه پیگیری">
                    <button style="float: none !important;" class="uk-button uk-button-primary">جستجو</button>
                </form>
                <span class="count-users">
                <span style="margin-top: 3px" uk-icon="icon: database"></span>
                    <?php
                    global $wpdb, $table_prefix;
                    $count_query = "select count(*) from {$table_prefix}pdp_payments";
                    $num = $wpdb->get_var($count_query);

                    echo '<div class="b-count">', $num, ' مورد', '</div>';

                    ?>
</span>
            </div>
            <div class="">
                <table id="table_id" class="uk-table display uk-table-hover uk-table-striped" cellspacing="0"
                       data-vertable="ver3">
                    <thead>
                    <tr class="head">
                        <th class="uk-table-shrink" data-column="column1"> پرداخت کننده</th>
                        <th class="uk-table-shrink" data-column="column2">مبلغ</th>
                        <th class="uk-table-shrink" data-column="column3">شناسه پیگیری</th>
                        <th class="uk-table-shrink" data-column="column4">شماره موبایل</th>
                        <th class="uk-table-shrink" data-column="column5">ایمیل</th>
                        <th class="uk-table-shrink" data-column="column6">تاریخ پرداخت</th>
                        <th class="uk-table-shrink" data-column="column7">توضیحات</th>
                        <th class="uk-table-shrink" data-column="column8">وضعیت</th>
                    </tr>
                    </thead>
                    <tbody style="overflow: auto">
                    <?php if ($payment_items): ?>
                        <?php foreach ($payment_items as $payment_item): ?>
                            <tr class="row100">
                                <td data-column="column1"><?php echo $payment_item->payment_name_user ?></td>
                                <td data-column="column2"><?php echo $payment_item->payment_amount . 'هزار تومان' ?></td>
                                <td data-column="column3"><?php echo $payment_item->payment_ref_num ?></td>
                                <td data-column="column4"><?php echo $payment_item->payment_user_mobile ?></td>
                                <td data-column="column5"><?php echo $payment_item->payment_user_email ?></td>
                                <td data-column="column6"><?php echo pdp_persian_number(date_i18n('Y/m/d', strtotime($payment_item->payment_paid_at))) ?></td>
                                <td data-column="column7"><?php echo $payment_item->payment_user_description ?></td>
                                <td data-column="column8">
                                    <?php
                                    if ($payment_item->payment_status == 1) {
                                        echo '<p style="color: #0ffb04">پرداخت شده</p>';
                                    } else {
                                        echo '<p style="color: #ff0002">ناموفق</p>';
                                    }
                                    ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                    <tr class="row100 head">
                        <th class="uk-table-shrink" data-column="column1"> پرداخت کننده</th>
                        <th class="uk-table-shrink" data-column="column2">مبلغ</th>
                        <th class="uk-table-shrink" data-column="column3">شناسه پیگیری</th>
                        <th class="uk-table-shrink" data-column="column4">شماره موبایل</th>
                        <th class="uk-table-shrink" data-column="column5">ایمیل</th>
                        <th class="uk-table-shrink" data-column="column6">تاریخ پرداخت</th>
                        <th class="uk-table-shrink" data-column="column7">توضیحات</th>
                        <th class="uk-table-shrink" data-column="column8">وضعیت</th>
                    </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
</div>
</body>
<script src="<?php echo PDP_ASSETS . 'js/uikit.min.js' ?>"></script>
<script src="<?php echo PDP_ASSETS . 'js/uikit-icons.min.js' ?>"></script>
<script type="text/javascript" src="<?php echo PDP_ASSETS . 'js/jquery-dataTables.js' ?>"></script>
<script type="text/javascript" src="<?php echo PDP_ASSETS . 'js/notify.js' ?>"></script>
<script>
    jQuery(document).ready(function () {
        jQuery('#table_id').DataTable();
    });
    jQuery('#table_id').DataTable({
        "language": {
            "paginate": {
                "previous": "قبلی",
                "next": "بعدی"
            }
        }
    });
    jQuery(document).ready(function () {
        jQuery('#table_idt').DataTable();
    });
    jQuery('#table_idt').DataTable({
        "language": {
            "paginate": {
                "previous": "قبلی",
                "next": "بعدی"
            }
        }
    });
</script>
</html>