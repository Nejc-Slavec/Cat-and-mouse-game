<script type="text/template" id="tmpl-colibri-demo-import-popup" style="display: none">
    <div class="colibri-demo-import-popup-container">
        <div class="demo-container">
            <div class="image-container colibri-ocdi-demo-item-image" style="background-image: url('{{ data.preview_image }}');">
            </div>
            <div class="colibri-ocdi-demo-item-name demo-name">
                <h4>{{ data.name }}</h4>
            </div>

        </div>

        <div class="info">
            <h2><?php esc_html_e( 'You are about to import a demo site', 'colibri-page-builder' ); ?></h2>
            <ul>
                <li><?php esc_html_e( 'Current pages will be moved to trash. You can restore the content back at any time.',
                        'colibri-page-builder' ); ?></li>
                <li><?php esc_html_e( 'Posts, pages, images, widgets, menus and other theme settings will get imported.',
                        'colibri-page-builder' ); ?></li>
                <li class="danger"><?php esc_html_e( 'Your current design will be completely overwritten by the new template. This process is irreversible. If you wish to be able to go back to the current design, please create a backup of your site before proceeding with the import.',
                        'colibri-page-builder' ); ?></li>
            </ul>
        </div>
        <div class="popup-plugins-column">
            <# if(data.plugins && data.plugins.length){ #>
            <div class="plugins">
                <h3><?php esc_html_e( 'The following plugins will be installed and activated as they are part of the demo',
					    'colibri-page-builder' ); ?></h3>
                <ul class="plugins-list">
                    <# _(data.plugins).each(function(plugin){ #>
                    <li>
                        <label>
                            <span>{{ plugin }}</span>
                        </label>
                    </li>
                    <# }); #>
                </ul>
            </div>
            <# } #>
            <div class="popup-footer">
                <# if(data.pro && !data.allow_pro){ #>
                <div class="colibri-demo-import-popup-pro">
                    <h2><?php echo esc_html( sprintf( __( 'This demo site is available only in %s',
						    'colibri-page-builder' ),
						    'Colibri Page Builder PRO' ) ); ?></h2>
                    <a href='https://colibriwp.com'
                       class='button button-hero button-primary colibri-demo-upgrade-to-pro'
                       target='_blank'><?php esc_html_e( 'Check all PRO features', 'colibri-page-builder' ); ?></a>
                </div>
                <# } else { #>
                <a class="button button-hero button-primary" data-name="import-data" data-id="{{ data.id }}">
				    <?php esc_html_e( 'Start importing', 'colibri-page-builder' ); ?>
                </a>
                <# } #>
            </div>
        </div>
    </div>
</script>
