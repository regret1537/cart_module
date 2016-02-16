<form action="<?php echo $allpay_form_action; ?>" id="allpay_form" method="POST">
  <div class="checkout-product">
    <table>
      <thead>
        <tr>
          <td colspan="2">
            <?php echo $allpay_title; ?>
          </td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <?php echo $allpay_payment_desc; ?>
          </td>
          <td>
            <select name="allpay_choose_payment">
              <?php foreach($allpay_payment_methods as $payment_name => $payment_desc) { ?>
              <option name="<?php echo $payment_name; ?>"><?php echo $payment_desc; ?></option>
              <?php } ?>
            </select>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="buttons">
    <div class="right">
      <input type="button" value="<?php echo $allpay_button_confirm; ?>" id="button-confirm" class="<?php echo MijoShop::getButton(); ?>" onclick="document.getElementById('allpay_form').submit();" />
    </div>
  </div>
</form>
