<?php
use ionos\essentials\jetpack_flow\Manager;
?>

<div class="wrapper">
  <div class="header">
      <img class="logo" src="<?php echo( 'branding.logo_variant1' ); ?>" />
  </div>
  <div class="container">
    <form>
      <input type="hidden" name="coupon" value="<?php echo $_GET['coupon']; ?>">
      <input type="hidden" name="page" value="<?php echo Manager::HIDDEN_PAGE_SLUG; ?>">
      <input type="hidden" name="step" value="install">

      <h1 class="screen-reader-text"><?php _e( 'Jetpack Backup', 'ionos-assistant' ); ?></h1>
      <img src="<?php echo plugins_url( '/img/jetpack-logo.svg'); ?>" class="jetpack-logo" alt="">
      <p><?php _e( 'We are going to install Jetpack Backup now.', 'ionos-assistant' ); ?></p>
      <div class="buttons">
        <button class="btn primarybtn" type="submit"><?php _e( 'Ok', 'ionos-assistant' ); ?></button>
        <a class="linkbtn" href="<?php echo admin_url(); ?>"><?php _e( 'No thanks', 'ionos-assistant' ); ?></a>
      </div>
    </form>
  </div>
</div>
