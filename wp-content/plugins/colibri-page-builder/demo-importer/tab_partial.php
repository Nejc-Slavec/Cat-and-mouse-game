<?php
$is_ocdi_installed = class_exists( "OCDI\OneClickDemoImport" ) || class_exists('ColibriWP\PageBuilder\OCDI\OneClickDemoImport');
?>

<div class="tab-cols colibri-admin-panel">
    <h2 class="colibri-import-demo-sites"><?php _e( 'Import Demo sites with one click', 'colibri-page-builder' ); //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></h2>

	<?php
	if ( $is_ocdi_installed ) {
		require __DIR__ . "/list.php";
	} else {
		require __DIR__ . "/install-ocdi.php";
	}
	?>

    <div class="colibri-admin-overlay">
        <div class="colibri-admin-overlay-content">
            <span class="colibri-admin-loader"></span>
            <span class="colibri-admin-overlay-message"></span>
        </div>
    </div>
</div>
