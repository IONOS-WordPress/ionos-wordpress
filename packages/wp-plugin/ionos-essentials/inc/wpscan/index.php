<?php
namespace ionos\essentials\wpscan;


function render_summary() { ?>
  <div class="card ionos_vulnerability ionos-wpscan-summary high">
    <div class="card__content">
      <section class="card__section">

      <div style="display: flex; align-items: center;">
        <div class="paragraph--critical" style="margin-right: 1em;">
          <i class="exos-icon exos-icon-warningmessage-32"></i>
        </div>
        <div>
          <h2 class="headline headline--sub"><?php echo \esc_html__('Vulnerability scan', 'ionos-essentials'); ?></h2>
          <div class="ionos_vulnerability__content">
            <div class="issue-row high">
              <span class="bubble high">1</span> <?php echo \esc_html__('critical issue found', 'ionos-essentials'); ?>
            </div>
            <div class="issue-row medium">
              <span class="bubble high">1</span> <?php echo \esc_html__('warnings found', 'ionos-essentials'); ?>
            </div>
          </div>
          <p class="paragraph paragraph--small">
            <?php echo \esc_html__('Last scan ran', 'ionos-essentials'); ?>
            7
            <?php echo \esc_html__('hours ago', 'ionos-essentials'); ?>
          </p>
        </div>
      </div>


        <p class="paragraph">
          We automatically scan daily and whenever a new plugin or theme is installed, using the WPScan vulnerability database. <a href="javascript: alert('How dare you to test this function sooo early');" class="link link--lookup">Learn more</a>
        </p>

      </section>

    </div>
  </div>


<?php
}
function render_issues() { ?>
<div class="sheet ionos-wpscan">
  <section class="sheet__section">
    <div class="grid">
        <div class="grid-col grid-col--12">
            <h2 class="headline headline--critical">
              <?php
                $count = 5;
                echo esc_html( sprintf( \_n( 'One critical issue', '%d critical issues', $count, 'ionos-essentials' ), $count ) );
              ?>
              <i class="exos-icon exos-icon-info-outlined-16 default-text-color"  data-tooltip="HI"></i>
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

