<?php

require_once __DIR__ . "/loop.php";
require_once __DIR__ . "/demo-install-popup.php";

?>

<div class="colibri-demo-import-indeterminate-loader">
    <div class="colibi-loader-message">
        <span>
            <?php esc_html_e( 'Please wait!', 'mesmerize-companion' ); ?>
        </span>
        <span>
          <?php esc_html_e( 'The import process can take a few minutes.', 'mesmerize-companion' ); ?>
        </span>
    </div>
    <span class="colibri-demo-import-progress-bar">
            <span class="colibri-demo-import-indeterminate"></span>
        </span>
</div>

<div class="ocdi__response  js-ocdi-ajax-response">

</div>
