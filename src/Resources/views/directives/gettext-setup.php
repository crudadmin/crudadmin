<script src="<?php echo Gettext::getJSPlugin(Localization::class) ?>"></script>
<script src="<?php echo admin_asset('/js/Gettextable.js') ?>"></script>

<?php if ( EditorMode::isEnabled() && ($lang = Localization::get()) ){ ?>
<script>
window.CAEditorConfig = {
    active : <?php echo EditorMode::isActive() ? 'true' : 'false' ?>,
    requests : {
        admin : '<?php echo url('/admin') ?>',
        changeState : '<?php echo action('\Admin\Controllers\GettextController@updateEditorState', $lang->slug) ?>',
        updateText : '<?php echo action('\Admin\Controllers\GettextController@updateTranslations', $lang->slug) ?>'
    },
    token : "<?php echo csrf_token() ?>"
};
</script>

<script src="<?php echo admin_asset('/js/TranslatableEditor.js?v='.Admin::getAssetsVersion()) ?>"></script>
<link rel="stylesheet" href="<?php echo admin_asset('/css/frontend.css?v='.Admin::getAssetsVersion()) ?>">
<?php } ?>