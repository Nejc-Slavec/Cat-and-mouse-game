(function($) {
    window.CP_Customizer.addModule(function(CP_Customizer) {
        CP_Customizer.hooks.addFilter('set_content_value_promise', function(save, options) {
            var promise = hop.exportToOption(save ? undefined : {
                html: false,
                css: false
            }, options)
            return promise;
        });

        function mountPreview(partials) {
            hop.iframeHop.mount(partials);
        }

        function initBuilder() {
            var hopInit = CP_Customizer.preview.frame().colibriInit;
            if (!hopInit) return;
            $('#accordion-section-general_site_typography, #accordion-section-general_site_typography > *').off('click')
            $('#accordion-section-general_site_typography').on("click", function(event) {
                top.hop.sidebar.selectPanel('typography');
            });
            $('#accordion-section-general_site_multilanguage, #accordion-section-general_site_multilanguage > *').off('click')
            $('#accordion-section-general_site_multilanguage').on("click", function(event) {
                top.hop.sidebar.selectPanel('multilanguage');
            });
            $('#accordion-section-templates, #accordion-section-templates > *').off('click')
            $('#accordion-section-templates').on("click", function(event) {
                top.hop.sidebar.selectPanel('templates');
            });
            $('#accordion-section-general_site_colors, #accordion-section-general_site_colors > *').off('click')
            $('#accordion-section-general_site_colors').on("click", function(event) {
                top.hop.sidebar.selectPanel('colors');
            });
            $('#accordion-section-general_site_spacing, #accordion-section-general_site_spacing > *').off('click')
            $('#accordion-section-general_site_spacing').on("click", function(event) {
                top.hop.sidebar.selectPanel('spacing');
            });
            $('#accordion-section-general_site_effects, #accordion-section-general_site_effects > *').off('click')
            $('#accordion-section-general_site_effects').on("click", function(event) {
                top.hop.sidebar.selectPanel('effects');
            });
            $('#accordion-section-general_site_google_fonts, #accordion-section-general_site_google_fonts > *').off('click')
            $('#accordion-section-general_site_google_fonts').on("click", function(event) {
                top.hop.sidebar.selectPanel('googleFonts');
            });
            if (_extendBuilderWPData.colibri_managed_sections) {
                for (var section_id in _extendBuilderWPData.colibri_managed_sections) {
                    if (_extendBuilderWPData.colibri_managed_sections.hasOwnProperty(section_id)) {
                        $(['#accordion-section-' + section_id, '#accordion-section-' + section_id + ' > *'].join(',')).off('click');
                        $('#accordion-section-' + section_id).on("click", (function (panelKey) {
                            return function (event) {
                                top.hop.sidebar.selectPanel(panelKey);
                            }
                        })(_extendBuilderWPData.colibri_managed_sections[section_id]));
                    }
                }
            }
            CP_Customizer.unbind('PREVIEW_LOADED', initBuilder);
            //CP_Customizer.bind('PREVIEW_LOADED', mountPreview);
            // append sidebar to the customizer root//
            var extendSidebar = jQuery("<div class='extend-builder-sidebar'></div>");
            jQuery('#customize-controls .wp-full-overlay-sidebar-content').append(extendSidebar);

            CP_Customizer.bind('PREVIEW_LOADED', function() {
                let newInit = CP_Customizer.preview.frame().colibriInit;
                hop.loadPages(newInit);
                mountPreview(newInit.pages[0].partials);
            });

            var api = {
                call: function(action, options, success, error) {
                    jQuery.post(ajaxurl, {
                        action: 'extend_builder',
                        api: JSON.stringify({
                            action: action,
                            data: options
                        }),
                        _wpnonce:  _extendBuilderWPData.extend_builder_api_nonce,
                    }, function(response) {
                        //console.error('call response###', response);
                        success(response);
                    });
                },
                postsTypes: {
                    list: function(data, cb) {
                        api.call('postsTypes/list', data, cb);
                    }
                }
            }

            var init = {
                sidebar: {
                    selector: ".extend-builder-sidebar"
                },
                sections: {
                    selector: "#extend-builder-sections"
                },
                data: hopInit,
                service: {
                    api: api,
                    rest : CP_Customizer.IO.rest,
                    cachedCalls: {
                        shortcodes: {},
                    },
                    invalidateCache: function () {
                        this.cachedCalls = {
                            shortcodes: {}
                        };
                    },
                    listPageTypes: function (success, error) {
                        api.call('list_page_types', {}, success, error);
                    },
                    openMediaBrowser: function(options) {
                        CP_Customizer.openMediaBrowser(options.type, function(src) {
                            options.callback(src);
                        }, options.data || {});
                    },
                    renderShortcode: function(options) {
                        if (options.callback && options.shortcode) {
                            var context = {
                                query: CP_Customizer.preview.data().queryVars,
                                main_query: CP_Customizer.preview.data().mainQueryVars,
                            }
                            var encodedShortcode = btoa(encodeURIComponent(options.shortcode));
                            var currentChangeset = CP_Customizer.utils.deepClone(wp.customize.previewer.query());

                            // look for a cached shortcode
                            if (this.cachedCalls.shortcodes[encodedShortcode]) {

                                if (options.invalidateCache) {
                                    this.cachedCalls.shortcodes[encodedShortcode] = undefined;
                                } else {
                                    console.log('Cached Shortcode Hit:', options.shortcode)
                                    options.callback({
                                        html: this.cachedCalls.shortcodes[encodedShortcode]
                                    });
                                    return;
                                }
                            }

                            var self = this;
                            var data = _.extend( _.omit(currentChangeset,['customized']), {
                                action: 'cp_shortcode_refresh',
                                shortcode: encodedShortcode,
                                _wpnonce: _extendBuilderWPData.cp_shortcode_refresh_nonce,
                                rawShortcode : options.shortcode,
                                context: context,
                                _: Date.now()
                            });
                            jQuery.ajax({
                                url: ajaxurl,
                                method: 'POST',
                                data: data
                            }).done(function (response) {
                                if (response != -1) {
                                    if (options.cache) {
                                        self.cachedCalls.shortcodes[encodedShortcode] = response;
                                    }
                                    options.callback({
                                        html: response
                                    });
                                }
                            });
                        }
                    }
                }
            }
            var preview = CP_Customizer.preview;
            init.service.invalidateCache();
            hop.init(init)
            hop.sidebar.$on("change", function() {
                CP_Customizer.updateState();
            });
           
            top.wp.customize.previewedDevice.bind(function(device) {
                console.error('device', device);
                hop.sidebar.$store.dispatch("ui/selectedMedia", device);
            });
            hop.sidebar.$on("device", function(device) {
                console.error('device', device);
                top.wp.customize.previewedDevice.set(device);
            });
            mountPreview(hopInit.pages[0].partials);
        }
        CP_Customizer.bind('PREVIEW_LOADED', initBuilder);
    });
})(jQuery, window);
