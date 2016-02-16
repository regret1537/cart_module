<?php echo $header; ?>
<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) {?>
        <?php     echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if (!empty($error_permission)) {?>
    <div class="warning"><?php echo $error_permission; ?></div>
    <?php } ?>
    <div class="box">
        <div class="heading">
            <h1><img src="view/image/payment.png" alt="" /> <?php echo $heading_title; ?></h1>
            <div class="buttons">
                <a onclick="$('#form').submit();" class="button">
                    <span><?php echo $button_save; ?></span>
                </a>
                <a onclick="location = '<?php echo $cancel; ?>';" class="button">
                    <span><?php echo $button_cancel; ?></span>
                </a>
            </div>
        </div>
        <div class="content">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td>
                            <span class="required">*</span>&nbsp;<?php echo $des_merchant_id; ?>
                        </td>
                        <td>
                            <input type="text" name="allpay_merchant_id" value="<?php echo isset(${'allpay_merchant_id'}) ? ${'allpay_merchant_id'} : ''; ?>" size="10" />
                            <br />
                            <?php if (isset($error_merchant_id)) { ?>
                            <span class="error"><?php echo $error_merchant_id; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span>&nbsp;<?php echo $des_hash_key; ?>
                        </td>
                        <td>
                            <input type="text" name="allpay_hash_key" value="<?php echo isset(${'allpay_hash_key'}) ? ${'allpay_hash_key'} : ''; ?>" size="20">
                            <br />
                            <?php if (isset($error_hash_key)) { ?>
                            <span class="error"><?php echo $error_hash_key; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span>&nbsp;<?php echo $des_hash_iv; ?>
                        </td>
                        <td>
                            <input type="text" name="allpay_hash_iv" value="<?php echo isset(${'allpay_hash_iv'}) ? ${'allpay_hash_iv'} : ''; ?>" size="20" />
                            <br />
                            <?php if (isset($error_hash_iv)) { ?>
                            <span class="error"><?php echo $error_hash_iv; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $des_payment_method; ?>
                        </td>
                        <td>
                            <?php foreach ($payment_methods as $payment_name => $payment_des) {?>
                            <?php if (${$payment_name} == 'on')?>
                            <input type="checkbox" name="<?php echo $payment_name; ?>"<?php echo (${$payment_name} == 'on'? 'checked="checked"' : ''); ?>> <?php echo $payment_des; ?><br />
                            <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $des_order_status; ?>
                        </td>
                        <td>
                            <select name="allpay_order_status_id">
                                <?php $selected_html = 'selected="selected"'; ?>
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php     if ($order_status['order_status_id'] == ${'allpay_order_status_id'}) { ?>
                                <?php         $is_select = $selected_html; ?>
                                <?php     } else { ?>
                                <?php         $is_select = ''; ?>
                                <?php     } ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"<?php echo $is_select; ?>>
                                    <?php echo $order_status['name']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $des_paid_status; ?></td>
                        <td>
                            <select name="allpay_paid_status_id">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php     if ($order_status['order_status_id'] == ${'allpay_paid_status_id'}) { ?>
                                <?php         $is_select = $selected_html; ?>
                                <?php     } else { ?>
                                <?php         $is_select = ''; ?>
                                <?php     } ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"<?php echo $is_select; ?>>
                                    <?php echo $order_status['name']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $des_unpaid_status; ?>
                        </td>
                        <td>
                            <select name="allpay_unpaid_status_id">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php     if ($order_status['order_status_id'] == ${'allpay_unpaid_status_id'}) { ?>
                                <?php         $is_select = $selected_html; ?>
                                <?php     } else { ?>
                                <?php         $is_select = ''; ?>
                                <?php     } ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"<?php echo $is_select; ?>>
                                    <?php echo $order_status['name']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $des_round_method; ?></td>
                        <td>
                            <select name="allpay_round_method">
                                <?php foreach ($round_methods as $idx => $round_method) { ?>
                                <?php     if ($idx == ${'allpay_round_method'}) { ?>
                                <?php         $is_select = $selected_html; ?>
                                <?php     } else { ?>
                                <?php         $is_select = ''; ?>
                                <?php     } ?>
                                <option value="<?php echo $idx; ?>"<?php echo $is_select; ?>>
                                    <?php echo $round_method; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $des_geo_zone; ?>
                        </td>
                        <td>
                            <select name="allpay_geo_zone_id">
                              <option value="0">
                                  <?php echo $text_all_zones; ?>
                              </option>
                              <?php foreach ($geo_zones as $geo_zone) { ?>
                              <?php     if ($geo_zone['geo_zone_id'] == ${'allpay_geo_zone_id'}) { ?>
                              <?php         $is_select = $selected_html; ?>
                              <?php     } else { ?>
                              <?php         $is_select = ''; ?>
                              <?php     } ?>
                              <option value="<?php echo $geo_zone['geo_zone_id']; ?>"<?php echo $is_select; ?>>
                                  <?php echo $geo_zone['name']; ?>
                              </option>
                              <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $des_payment_status; ?>
                        </td>
                        <td>
                            <select name="allpay_status">
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <option value="1"<?php echo (${'allpay_status'} ? $selected_html : ''); ?>><?php echo $text_enabled; ?></option>                               
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $des_sort_order; ?>
                        </td>
                        <td>
                            <input type="text" name="allpay_sort_order" value="<?php echo ${'allpay_sort_order'}; ?>" size="1" />
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
    <?php echo $footer; ?>