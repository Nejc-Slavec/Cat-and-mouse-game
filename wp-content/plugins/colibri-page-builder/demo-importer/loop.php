<?php $demos = apply_filters( 'colibri_page_builder/demo-sites-list', \ColibriWP\Theme\View::getData( 'demos' ) ); ?>

<div class="colibri-ocdi-demo-list js-ocdi-gl-item-container">
    <?php foreach ( $demos as $index => $demo ): ?>
        <div class="colibri-ocdi-demo-item js-ocdi-gl-item">
            <div class="colibri-ocdi-demo-item-content-wrapper">
                <?php if ( $demo['is_pro'] && ! \ColibriWP\PageBuilder\PageBuilder::instance()->isPRO() ): ?>
                    <div class="colibri-ocdi-demo-item-pro-badge">
                        PRO
                    </div>
                <?php endif; ?>
                <div class="colibri-ocdi-demo-item-image"
                     style="background-image: url('<?php echo esc_url($demo['import_preview_image_url']); ?>')">
                    <div class="colibri-ocdi-demo-overlay">
                        <a target="_blank" href="<?php echo esc_url($demo['preview_url']); ?>">
                            <div class="colibri-ocdi-demo-overlay__preview">
                                <span class="colibri-ocdi-demo-overlay__preview__icon dashicons dashicons-search"></span>
                                <span class="colibri-ocdi-demo-overlay__preview__label">preview</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="colibri-ocdi-demo-item-action">
                    <div class="colibri-ocdi-demo-item-name">
                        <h4><?php echo esc_html($demo['import_file_name']); ?></h4>
                    </div>
                    <div class="colibri-ocdi-demo-item-import">
                        <a class="button" target="_blank"
                           href="<?php echo esc_url( colibri_try_demo_url( $demo['slug'] ) ); ?>">
                            <?php esc_html_e( 'Try Online', 'colibri-page-builder' ); ?>
                        </a>
                        <button value="<?php echo esc_attr( $index ); ?>"
                                class="ocdi__gl-item-button button button-primary colibri-popup-import-button">
                            <?php esc_html_e( 'Import', 'colibri-page-builder' ); ?>
                        </button>
                        <input data-colibri-demo-import-runner="<?php echo esc_attr( $index ); ?>" type="hidden"
                               value="<?php echo esc_attr( $index ); ?>" class="js-ocdi-gl-import-data">
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
