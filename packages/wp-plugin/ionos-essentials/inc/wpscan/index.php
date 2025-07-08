<?php
namespace ionos\essentials\wpscan;

?>

<?php function render_issues() { ?>
<div class="sheet ionos-wpscan">
  <section class="sheet__section">
    <div class="grid">
        <div class="grid-col grid-col--12">
            <h2 class="headline headline--critical">
              <?php
                $count = 5;
                echo esc_html( sprintf( \_n( 'One critical issue', '%d critical issues', $count, 'ionos-essentials' ), $count ) );
              ?>
              <i class="exos-icon exos-icon-info-outlined-16" data-tooltip="HI"></i>
              </h2>
        </div>

        <div class="grid grid-col-12">
          <ul class="sheet__stripes">
            <?php
                $plugins = get_plugins();
                $themes = array_slice(wp_get_themes(), 0, 1, true);
                $critical_issues = array_merge($plugins, $themes);
                foreach($critical_issues as $issue ){
                  //print_r($issue);

                  if(is_array($issue)){
                    $theme_or_plugin = 'plugin';
                  }else {
                    $theme_or_plugin = 'theme';
                  }
            ?>
              <li class="settings-stripe settings-stripe--link __direct-selection <?php echo esc_attr( $theme_or_plugin ); ?>">
                <div class="settings-stripe__label"><strong><?php echo esc_html( $issue['Name'] ); ?></strong></div>
                <div class="settings-stripe__value"></div>
                <div class="settings-stripe__action"><button class="button button-primary">Update</button></div>
              </li>

            <?php } ?>
            </ul>
        </div>



    </div>
  </section>
</div>
<?php } ?>

