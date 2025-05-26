<div class="h-column h-column-container d-flex h-col-lg-12 h-col-md-12 h-col-12  masonry-item style-138-outer style-local-1851-m3-outer">
  <div data-colibri-id="1851-m3" class="d-flex h-flex-basis h-column__inner h-px-lg-3 h-px-md-3 h-px-3 v-inner-lg-3 v-inner-md-3 v-inner-3 style-138 style-local-1851-m3 position-relative">
    <div class="w-100 h-y-container h-column__content h-column__v-align flex-basis-100 align-self-lg-start align-self-md-start align-self-start">
      <div data-colibri-id="1851-m4" class="h-row-container gutters-row-lg-0 gutters-row-md-0 gutters-row-0 gutters-row-v-lg-0 gutters-row-v-md-0 gutters-row-v-0 style-349 style-local-1851-m4 position-relative">
        <div class="h-row justify-content-lg-center justify-content-md-center justify-content-center align-items-lg-stretch align-items-md-stretch align-items-stretch gutters-col-lg-0 gutters-col-md-0 gutters-col-0 gutters-col-v-lg-0 gutters-col-v-md-0 gutters-col-v-0">
          <div class="h-column h-column-container d-flex h-col-lg-auto h-col-md-auto h-col-auto style-350-outer style-local-1851-m5-outer">
            <div data-colibri-id="1851-m5" class="d-flex h-flex-basis h-column__inner h-px-lg-0 h-px-md-0 h-px-0 v-inner-lg-0 v-inner-md-0 v-inner-0 style-350 style-local-1851-m5 position-relative">
              <div class="w-100 h-y-container h-column__content h-column__v-align flex-basis-100 align-self-lg-start align-self-md-start align-self-start">
                <div data-href="<?php the_permalink(); ?>" data-colibri-component="link" data-colibri-id="1851-m6" class="colibri-post-thumbnail <?php peregrine_post_thumbnail_classes(); ?> <?php peregrine_post_thumb_placeholder_classes(); ?> style-352 style-local-1851-m6 h-overflow-hidden position-relative h-element">
                  <div class="h-global-transition-all colibri-post-thumbnail-shortcode style-dynamic-1851-m6-height">
                    <?php peregrine_post_thumbnail(array (
                      'link' => true,
                    )); ?>
                  </div>
                  <div class="colibri-post-thumbnail-content align-items-lg-center align-items-md-center align-items-center flex-basis-100">
                    <div class="w-100 h-y-container"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="h-column h-column-container d-flex h-col-lg-auto h-col-md-auto h-col-auto style-351-outer style-local-1851-m7-outer">
            <div data-colibri-id="1851-m7" class="d-flex h-flex-basis h-column__inner h-px-lg-2 h-px-md-2 h-px-0 v-inner-lg-0 v-inner-md-0 v-inner-2 style-351 style-local-1851-m7 position-relative">
              <div class="w-100 h-y-container h-column__content h-column__v-align flex-basis-100 align-self-lg-start align-self-md-start align-self-start">
                <div data-colibri-id="1851-m8" class="h-blog-title style-142 style-local-1851-m8 position-relative h-element">
                  <div class="h-global-transition-all">
                    <?php peregrine_post_title(array (
                      'heading_type' => 'h4',
                      'classes' => 'colibri-word-wrap',
                    )); ?>
                  </div>
                </div>
                <div data-colibri-id="1851-m9" class="style-144 style-local-1851-m9 position-relative h-element">
                  <div class="h-global-transition-all">
                    <?php peregrine_post_excerpt(array (
                      'max_length' => 12,
                    )); ?>
                  </div>
                </div>
                <?php if ( \ColibriWP\Theme\Core\Hooks::prefixed_apply_filters( 'show_post_meta', true ) ): ?>
                <div data-colibri-id="1851-m10" class="h-blog-meta style-353 style-local-1851-m10 position-relative h-element">
                  <div name="1" class="metadata-item">
                    <span class="metadata-prefix">
                      <?php esc_html_e('by','peregrine'); ?>
                    </span>
                    <a href="<?php echo esc_url(get_author_posts_url( get_the_author_meta( 'ID' ) )); ?>">
                      <?php echo esc_html(get_the_author_meta( 'display_name', get_the_author_meta( 'ID' ) )); ?>
                    </a>
                    <span class="meta-separator">
                      <?php esc_html_e('&#x25AA;','peregrine'); ?>
                    </span>
                  </div>
                  <div name="2" class="metadata-item">
                    <span class="metadata-prefix">
                      <?php esc_html_e('on','peregrine'); ?>
                    </span>
                    <a href="<?php peregrine_post_meta_date_url(); ?>">
                      <?php peregrine_the_date('F j, Y'); ?>
                    </a>
                  </div>
                </div>
                <?php endif; ?>
                <div data-colibri-id="1851-m11" class="h-x-container style-145 style-local-1851-m11 position-relative h-element">
                  <div class="h-x-container-inner style-dynamic-1851-m11-group">
                    <span class="h-button__outer style-146-outer style-local-1851-m12-outer d-inline-flex h-element">
                      <a h-use-smooth-scroll="true" href="<?php the_permalink(); ?>" data-colibri-id="1851-m12" class="d-flex w-100 align-items-center h-button justify-content-lg-center justify-content-md-center justify-content-center style-146 style-local-1851-m12 position-relative">
                        <span>
                          <?php esc_html_e('read more','peregrine'); ?>
                        </span>
                        <span class="h-svg-icon h-button__icon style-146-icon style-local-1851-m12-icon">
                          <!--Icon by Icons8 Line Awesome (https://icons8.com/line-awesome)-->
                          <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="arrow-right" viewBox="0 0 512 545.5">
                            <path d="M299.5 140.5l136 136 11 11.5-11 11.5-136 136-23-23L385 304H64v-32h321L276.5 163.5z"></path>
                          </svg>
                        </span>
                      </a>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
