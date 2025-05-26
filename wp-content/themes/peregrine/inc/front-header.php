<?php return 
array (
  'navigation' => 
  array (
    'props' => 
    array (
      'showTopBar' => true,
      'sticky' => true,
      'overlap' => true,
      'width' => 'boxed',
      'layoutType' => 'logo-spacing-menu',
    ),
    'style' => 
    array (
      'ancestor' => 
      array (
        'sticky' => 
        array (
          'background' => 
          array (
            'color' => '#ffffff',
          ),
        ),
      ),
      'background' => 
      array (
        'color' => 'transparent',
      ),
      'padding' => 
      array (
        'top' => 
        array (
          'value' => '20',
        ),
      ),
    ),
  ),
  'logo' => 
  array (
    'props' => 
    array (
      'layoutType' => 'image',
    ),
  ),
  'header-menu' => 
  array (
    'props' => 
    array (
      'sticky' => true,
      'hoverEffect' => 
      array (
        'type' => 'bordered-active-item bordered-active-item--bottom',
        'group' => 
        array (
          'border' => 
          array (
            'transition' => 'effect-borders-grow grow-from-center',
          ),
        ),
        'activeGroup' => 'border',
        'enabled' => true,
      ),
      'showOffscreenMenuOn' => 'has-offcanvas-tablet',
    ),
  ),
  'title' => 
  array (
    'style' => 
    array (
      'textAlign' => 'center',
    ),
  ),
  'hero' => 
  array (
    'style' => 
    array (
      'background' => 
      array (
        'type' => 'image',
        'color' => 'rgb(10, 10, 11)',
        'overlay' => 
        array (
          'shape' => 
          array (
            'value' => 'none',
            'isTile' => false,
          ),
          'light' => false,
          'color' => 
          array (
            'value' => '#000000',
            'opacity' => 0.09,
          ),
          'enabled' => false,
          'type' => 'color',
          'gradient' => 
          array (
            'angle' => '-20',
            'steps' => 
            array (
              0 => 
              array (
                'color' => 'rgba(183, 33, 255, 0.8)',
                'position' => '0',
              ),
              1 => 
              array (
                'color' => 'rgba(33, 212, 253, 0.8)',
                'position' => '100',
              ),
            ),
            'name' => 'october_silence',
          ),
        ),
        'image' => 
        array (
          0 => 
          array (
            'source' => 
            array (
              'url' => 'laptop-1842297-modified-scaled-3.jpg',
              'gradient' => 
              array (
                'name' => 'october_silence',
                'angle' => 0,
                'steps' => 
                array (
                  0 => 
                  array (
                    'position' => '0',
                    'color' => '#b721ff',
                  ),
                  1 => 
                  array (
                    'position' => '100',
                    'color' => '#21d4fd',
                  ),
                ),
              ),
            ),
            'attachment' => 'scroll',
            'position' => 
            array (
              'x' => 50.92744514586056,
              'y' => 29.798038985897886,
            ),
            'repeat' => 'no-repeat',
            'size' => 'cover',
            'useParallax' => false,
          ),
        ),
        'slideshow' => 
        array (
          'duration' => 
          array (
            'value' => 1500,
          ),
          'speed' => 
          array (
            'value' => 1500,
          ),
        ),
      ),
      'padding' => 
      array (
        'top' => 
        array (
          'value' => '150',
          'unit' => 'px',
        ),
        'bottom' => 
        array (
          'value' => '150',
          'unit' => 'px',
        ),
      ),
      'separatorBottom' => NULL,
    ),
    'props' => 
    array (
      'layoutType' => 'textWithMediaOnRight',
      'heroSection' => 
      array (
        'layout' => 'textWithMediaOnRight',
      ),
    ),
  ),
);
