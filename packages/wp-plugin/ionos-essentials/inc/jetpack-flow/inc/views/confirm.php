<?php

use ionos\essentials\jetpack_flow\Manager;

?>

<div class="wrapper">
  <div class="container">
    <form>
      <input type="hidden" name="coupon" value="<?php echo esc_attr($_GET['coupon']); ?>">
      <input type="hidden" name="page" value="<?php echo esc_attr(Manager::HIDDEN_PAGE_SLUG); ?>">
      <input type="hidden" name="step" value="install">

      <h1 class="screen-reader-text"><?php esc_html('Jetpack Backup', 'ionos-essentials'); ?></h1>
      <img src="<?php echo esc_url(\plugins_url('', __DIR__) . '/img/jetpack-logo.svg'); ?>" class="jetpack-logo" alt="">
      <p><?php esc_html('We are going to install Jetpack Backup now.', 'ionos-essentials'); ?></p>
      <div class="buttons">
        <button class="btn primarybtn" type="submit"><?php esc_html('Ok', 'ionos-essentials'); ?></button>
        <a class="linkbtn" href="<?php echo esc_attr(admin_url()); ?>"><?php esc_html('No thanks', 'ionos-essentials'); ?></a>
      </div>
    </form>
  </div>
</div>
