<style>
    <?php
	  \ColibriWP\PageBuilder\License\License::getInstance()->getActivationForm()->getInlineCSS();
	  ?>
    .colibri-page-builder-upgade-view {
        text-align: left;
        color: #72777C;
    }
    .colibri-page-builder-upgade-view h2 {
        text-align: left;
        margin: 0;
        margin-bottom: 15px;
        font-size: 1.31em;
        font-weight: 500;
        line-height: 1.23;
        color: #23282D;
    }
    .colibri-page-builder-upgade-view h3 {
        margin:0;
        font-size: 16px;
        font-weight: 500;
        line-height: 19px;
        color: #72777C;
    }
    .colibri-page-builder-upgade-view .description {
        font-size: 16px;
        font-style: italic;
        font-weight: 500;
        line-height: 19px;
        margin: 0;
    }
    form#colibri-page-builder-activate-license-form {
        display: inline-block;
    }
    #colibri-page-builder-activate-license-form td:first-child{
        padding-left: 0px;
    }
    #colibri-page-builder-activate-license-form td:first-child input {
        margin-left: 0px;
    }
    #colibri-page-builder-activate-license-form table {
        margin-top: 0;
    }
    #colibri-page-builder-activate-license-form table td{
        padding: 15px 5px;
    }
    .colibri-page-builder-upgade-view .spinner-holder .spinner {
        display: block !important;
        visibility: visible !important;
        margin: 0;
    }

    .spinner-holder.plugin-installer-spinner {
        display: inline-block;
        line-height: 20px;
        padding: 8px 20px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        background-color: whitesmoke;
    }

    .spinner-holder.plugin-installer-spinner .spinner {
        float: none;
        display: inline-block !important;
    }

    .spinner-holder.plugin-installer-spinner span {
        float: none;
        display: inline-block !important;
        vertical-align: bottom;
    }

</style>

<div class="test">
	<?php
	\ColibriWP\PageBuilder\License\License::getInstance()->getActivationForm()->makeUpgradeView();
	?>
</div>
<?php wp_enqueue_script( 'wp-util' ); ?>
<script type="text/javascript">
	<?php echo \ColibriWP\PageBuilder\License\License::getInstance()->getActivationForm()->getUpgradeInlineJS(); ?>
</script>
