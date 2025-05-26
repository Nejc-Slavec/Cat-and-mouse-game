(function ($) {

    var redirectToIndex = false;

    function openDemoInstallPopup(demo_index, callback) {
        tb_show("Import Demo", '#TB_inline');
        var $tbWindow = $("#TB_window"),
            $tbContent = $("#TB_ajaxContent");
        $tbWindow.addClass('colibri-demo-import-popup');
        $tbWindow.removeAttr('style');
        $tbContent.removeAttr('style').addClass('colibri-demo-import-popup');

        var template = wp.template('colibri-demo-import-popup');
        var demo = extendthemes_ocdi.import_files[demo_index];


        var data = {
            'name': demo.import_file_name || "",
            'preview_image': demo.import_preview_image_url || "",
            'preview_url': demo.preview_url,
            'plugins': _.toArray(demo.plugins || []),
            'pro': demo.is_pro,
            'allow_pro': !!parseInt(extendthemes_ocdi.plugin_state),
            'id': demo_index
        };
        var content = template(data);

        $tbContent.append(content);

        $tbWindow.find('[data-name="import-data"]').click(function () {

            if ($(this).hasClass('disabled')) {
                return;
            }

            $(this).prop('disabled', true).addClass('disabled');
            if (callback) {
                callback(demo_index)
            }
        })
    }

    function showOverlay(message) {
        $('.colibri-admin-overlay-message').html(message);
        $('.colibri-admin-overlay').fadeIn();
    }

    function hideOverlay() {
        $('.colibri-admin-overlay').fadeOut();
    }


    function activateOCDI() {
        showOverlay('Activating One Click Demo Import Plugin...');
        wp.ajax.post('colibri_page_builder_active_ocdi', {}).always(function () {
            var location = window.location.toString().replace(window.location.hash, "") + "#demo=" + (1 + parseInt(redirectToIndex));
            window.location = location;
            window.location.reload();
        });

    }

    function prepareOCDIInstall() {
        $('.ocdi__gl-item-button').click(function () {
            redirectToIndex = $(this).val();
            if (window.ocdi_current_state === "installed") {
                activateOCDI();
            } else {
                showOverlay('Installing One Click Demo Import Plugin...');
                wp.updates.ajax('install-plugin', {
                    slug: 'one-click-demo-import',
                    success: activateOCDI,
                });
            }

        });
    }

    function prepareImportBindings() {

        $(function () {
            var hashId = (location.hash || '').replace('#demo=', '');
            setTimeout(function () {
                if (hashId) {
                    $('.ocdi__gl-item-button[value="' + (parseInt(hashId) - 1) + '"]').click();
                }
            }, 500)
        });

        $('.ocdi__gl-item-button').click(function () {
            openDemoInstallPopup(($(this).val()), function (demo_index) {
                tb_remove();
                $('[data-colibri-demo-import-runner="' + demo_index + '"]').trigger('click');
                $('.colibri-popup-import-button[value="' + demo_index + '"]').fadeOut();
                $('.colibri-demo-import-indeterminate-loader').fadeIn();
            });

        });

        $(document).bind('ocdiImportComplete', function () {
            $('.colibri-demo-import-indeterminate-loader').hide();

            colibriLoadingScreen.show('Preparing theme styles...');

            wp.ajax.post('get_after_import_builder_data', {
                _wpnonce: extendthemes_ocdi.ajax_nonce
            }).done(function (data) {
                for (var key in data) {
                    if (!data.hasOwnProperty(key)) {
                        continue;
                    }
                    window[key] = data[key];
                }

                colibriVirtual.renderer.generate(window._colibriAllPartialsExport, {}).then(function () {
                    console.error('ready');
                    colibriLoadingScreen.hide();
                });

            });
        });
    }


    if (window.ocdi_needs_instalation) {
        prepareOCDIInstall();
    } else {
        prepareImportBindings();
    }
})(jQuery);
