<?php

namespace ionos\essentials\dashboard;

?>
 <div id="ionos-tab2" class="page-section ionos-tab">
    <div class="grid">
      <div class="grid-col grid-col--12">
        <h3 class="headline headline--sub"><?php \esc_html_e('Website security', 'ionos-essentials'); ?></h3>

          <div class="card">
            <div class="card__content">
              <div>
                <section class="card__section">
                  <div class="grid-col grid-col--4 grid-col--small-12"><?php blocks\vulnerability\render_callback(); ?></div>
                </section>
                <section class="card__section" style="display: flex; align-items: center; justify-content: space-between;">
                  <div>
                    <h3 class="headline"><?php \esc_html_e('Vulnerability alerting', 'ionos-essentials'); ?></h3>
                    <p class="paragraph">
                      <?php \esc_html_e('Vulnerabilities detected are immediately emails to'); ?>
                      <br>
                      <strong style="font-size: 1.2em">
                        <?php echo esc_html(get_option('admin_email')); ?>
                      </strong>
                      <br><br>
                      <a href="<?php echo esc_url(admin_url('options-general.php')); ?>" class="link link--action">
                        <?php \esc_html_e('Change email address', 'ionos-essentials'); ?>
                      </a>
                    </p>
                  </div>
                  <div>
                    <span class="input-switch">
                      <input type="checkbox" id="switch2">
                      <label for="switch1">
                        <span class="input-switch__on"></span>
                        <span class="input-switch__toggle"></span>
                        <span class="input-switch__off"></span>
                      </label>
                    </span>
                  </div>
                </section>
              </div>
            </div>
          </div>

        <div class="card">
          <div class="card__content">
            <div>
              <section class="card__section" style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                  <h3 class="headline headline--paragraph"><?php \esc_html_e('Password monitoring', 'ionos-essentials'); ?></h3>
                  <p class="paragraph">Protect your website from threats</p>
                </div>
                <div>
                  <span class="input-switch">
                    <input type="checkbox" id="switch1">
                    <label for="switch1">
                      <span class="input-switch__on"></span>
                      <span class="input-switch__toggle"></span>
                      <span class="input-switch__off"></span>
                    </label>
                  </span>
                </div>
              </section>

              <hr class="horizontal-separator" style="margin: 0 16px;">

                <?php
                if ( ! \ionos\essentials\is_stretch() ) {
                ?>
              <section class="card__section" style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                  <h3 class="headline headline--paragraph"><?php \esc_html_e('Disable XML-RPC access', 'ionos-essentials'); ?></h3>
                  <p class="paragraph">Protect your website from threats</p>
                </div>
                <div>
                  <span class="input-switch">
                    <input type="checkbox" id="switch3">
                    <label for="switch1">
                      <span class="input-switch__on"></span>
                      <span class="input-switch__toggle"></span>
                      <span class="input-switch__off"></span>
                    </label>
                  </span>
                </div>
              </section>

              <hr class="horizontal-separator" style="margin: 0 16px;">

              <?php } ?>

              <section class="card__section" style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                  <h3 class="headline headline--paragraph"><?php \esc_html_e('Prevent email login', 'ionos-essentials'); ?></h3>
                  <p class="paragraph">Protect your website from threats</p>
                </div>
                <div>
                  <span class="input-switch">
                    <input type="checkbox" id="switch4">
                    <label for="switch1">
                      <span class="input-switch__on"></span>
                      <span class="input-switch__toggle"></span>
                      <span class="input-switch__off"></span>
                    </label>
                  </span>
                </div>
              </section>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
