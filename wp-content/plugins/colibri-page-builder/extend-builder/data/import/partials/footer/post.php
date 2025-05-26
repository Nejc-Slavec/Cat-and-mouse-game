<?php namespace ExtendBuilder; load_file_value('partial_data', array (
  'partial' => 
  array (
    'id' => 13,
    'name' => 'footer_post',
    'type' => 'footer',
    'data' => 
    array (
      'json' => '{"name":"hop-footer","children":[{"name":"hop-section","props":{"anchor":false,"name":"Copyright","attrs":{"id":"copyright"}},"children":[{"name":"hop-row","children":[{"name":"hop-column","style":{"descendants":{"outer":{"columnWidth":{"type":"custom","custom":{"value":100,"unit":"%"}}}}},"children":[{"name":"hop-copyright","slot":null,"parentId":"13-f4","index":1,"id":"13-f5","partialId":13,"styleRef":53,"type":"footer"}],"id":"13-f4","parentId":"13-f3","partialId":13,"styleRef":52,"type":"footer"}],"parentId":"13-f2","index":0,"id":"13-f3","partialId":13,"styleRef":51,"type":"footer"}],"id":"13-f2","parentId":"13-f1","partialId":13,"styleRef":50,"type":"footer"}],"id":"13-f1","ui":{"isSelected":false,"isHovered":false},"type":"footer","partialId":13,"styleRef":49}',
      'meta' => 
      array (
        'styleRefs' => 
        array (
          0 => 49,
          1 => 50,
          2 => 51,
          3 => 52,
          4 => 53,
        ),
      ),
      'html' => '<div data-enabled="true" data-colibri-component="footer-parallax" data-colibri-id="13-f1" class="page-footer style-49 style-local-13-f1 position-relative">
  <!---->
  <div data-colibri-component="section" data-colibri-id="13-f2" id="copyright" class="h-section h-section-global-spacing d-flex align-items-lg-center align-items-md-center align-items-center style-50 style-local-13-f2 position-relative">
    <!---->
    <!---->
    <div class="h-section-grid-container h-section-boxed-container">
      <!---->
      <div data-colibri-id="13-f3" class="h-row-container gutters-row-lg-1 gutters-row-md-1 gutters-row-2 gutters-row-v-lg-1 gutters-row-v-md-1 gutters-row-v-2 style-51 style-local-13-f3 position-relative">
        <!---->
        <div class="h-row justify-content-lg-center justify-content-md-center justify-content-center align-items-lg-stretch align-items-md-stretch align-items-stretch gutters-col-lg-1 gutters-col-md-1 gutters-col-2 gutters-col-v-lg-1 gutters-col-v-md-1 gutters-col-v-2">
          <!---->
          <div class="h-column h-column-container d-flex h-col-lg-auto h-col-md-auto h-col-auto style-52-outer style-local-13-f4-outer">
            <div data-colibri-id="13-f4" class="d-flex h-flex-basis h-column__inner h-px-lg-1 h-px-md-1 h-px-2 v-inner-lg-1 v-inner-md-1 v-inner-2 style-52 style-local-13-f4 position-relative">
              <!---->
              <!---->
              <div class="w-100 h-y-container h-column__content h-column__v-align flex-basis-100 align-self-lg-start align-self-md-start align-self-start">
                <!---->
                <div data-colibri-id="13-f5" class="style-53 style-local-13-f5 position-relative h-element">
                  <!---->
                  <div class="h-global-transition-all">[colibri_copyright]© {year} {site-name}. Created for free using WordPress and
                    <a target="_blank" href="https://colibriwp.com">Colibri</a>[/colibri_copyright]</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>',
      'id' => 13,
      'lang' => 'default',
      'dynamic' => false,
      'type' => '',
    ),
    'slug' => '13',
  ),
  'partialCss' => 
  array (
    'local-13-f4' => 
    array (
      'desktop' => '#colibri .style-local-13-f4-outer {
  width: 100% ;
  flex: 0 0 auto;
  -ms-flex: 0 0 auto;
}
',
    ),
  ),
  'styleRefs' => 
  array (
    0 => 49,
    1 => 50,
    2 => 51,
    3 => 52,
    4 => 53,
  ),
  'rules' => 
  array (
    0 => 
    array (
      'id' => 49,
      'type' => 'hop-footer',
      'props' => 
      array (
        'useFooterParallax' => true,
      ),
      'v' => '1.1',
      'nodes' => 
      array (
        0 => '13-f1',
      ),
    ),
    1 => 
    array (
      'id' => 50,
      'type' => 'hop-section',
      'style' => 
      array (
        'padding' => 
        array (
          'top' => 
          array (
            'path' => 'value',
            'value' => '30',
          ),
          'bottom' => 
          array (
            'path' => 'value',
            'value' => '30',
          ),
        ),
      ),
      'v' => '1.1',
      'nodes' => 
      array (
        0 => '13-f2',
      ),
    ),
    2 => 
    array (
      'id' => 51,
      'props' => 
      array (
        'layout' => 
        array (
          'equalWidth' => false,
          'horizontalInnerGap' => 1,
          'verticalGap' => 1,
          'horizontalGap' => 1,
        ),
        'media' => 
        array (
          'mobile' => 
          array (
            'layout' => 
            array (
              'verticalGap' => 2,
              'horizontalGap' => 2,
            ),
          ),
        ),
      ),
      'type' => 'hop-row',
      'v' => '1.1',
      'nodes' => 
      array (
        0 => '13-f3',
      ),
    ),
    3 => 
    array (
      'id' => 52,
      'type' => 'hop-column',
      'props' => 
      array (
        'layout' => 
        array (
          'vSpace' => 
          array (
            'value' => '0',
          ),
          'horizontalInnerGap' => 1,
          'verticalInnerGap' => 1,
        ),
        'media' => 
        array (
          'mobile' => 
          array (
            'layout' => 
            array (
              'horizontalInnerGap' => 2,
              'verticalInnerGap' => 2,
            ),
          ),
        ),
      ),
      'style' => 
      array (
        'padding' => 
        array (
          'left' => 
          array (
            'unit' => 'px',
          ),
          'right' => 
          array (
            'unit' => 'px',
          ),
          'top' => 
          array (
            'unit' => 'px',
          ),
          'bottom' => 
          array (
            'unit' => 'px',
          ),
        ),
        'media' => 
        array (
          'mobile' => 
          array (
            'padding' => 
            array (
              'left' => 
              array (
                'unit' => 'px',
              ),
              'right' => 
              array (
                'unit' => 'px',
              ),
              'top' => 
              array (
                'unit' => 'px',
              ),
              'bottom' => 
              array (
                'unit' => 'px',
              ),
            ),
          ),
        ),
        'descendants' => 
        array (
          'outer' => 
          array (
            'media' => 
            array (
              'mobile' => 
              array (
                'padding' => 
                array (
                  'top' => 
                  array (
                    'unit' => 'px',
                  ),
                  'bottom' => 
                  array (
                    'unit' => 'px',
                  ),
                  'left' => 
                  array (
                    'unit' => 'px',
                  ),
                  'right' => 
                  array (
                    'unit' => 'px',
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
      'v' => '1.1',
      'nodes' => 
      array (
        0 => '13-f4',
      ),
    ),
    4 => 
    array (
      'id' => 53,
      'type' => 'hop-copyright',
      'nodeId' => '13-f6',
      'v' => '1.1',
      'nodes' => 
      array (
        0 => '13-f5',
      ),
    ),
  ),
  'cssById' => 
  array (
    49 => 
    array (
      'desktop' => '',
      'tablet' => '',
      'mobile' => '',
    ),
    50 => 
    array (
      'desktop' => '#colibri .style-50 {
  height: auto;
  min-height: unset;
  padding-top: 30px;
  padding-bottom: 30px;
}
',
      'tablet' => '',
      'mobile' => '',
    ),
    51 => 
    array (
      'desktop' => '',
      'tablet' => '',
      'mobile' => '',
    ),
    52 => 
    array (
      'desktop' => '.style-52 > .h-y-container > *:not(:last-child) {
  margin-bottom: 0px;
}
#colibri .style-52 {
  text-align: center;
  height: auto;
  min-height: unset;
}
',
      'tablet' => '',
      'mobile' => '',
    ),
    53 => 
    array (
      'desktop' => '',
      'tablet' => '',
      'mobile' => '',
    ),
  ),
));
