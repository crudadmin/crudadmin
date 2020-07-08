<script src="<?php echo Gettext::getJSPlugin(Localization::class) ?>"></script>
<script src="<?php echo admin_asset('/js/Gettextable.js') ?>"></script>

<?php if ( count($_visibleRoutes = EditorMode::getVisibleRoutes()) > 0 ) { ?>
<script type="text/javascript">
window.CAVisibleRoutes = {
<?php foreach ($_visibleRoutes as $_action => $_url): ?>
    '<?php echo encryptText($_action) ?>': '<?php echo $_url ?>',
<?php endforeach ?>
}
</script>
<?php } ?>

<?php if ( (EditorMode::isActive() || FrontendEditor::isActive()) ){ ?>
<script>
window.CAEditorConfig = {
    language : '<?php echo ($lang = Localization::get()) ? $lang->slug : '' ?>',
    enabled : <?php echo Admin::isEnabledFrontendEditor() ? 'true' : 'false' ?>,
    active : <?php echo EditorMode::isActive() ? 'true' : 'false' ?>,
    translatable : <?php echo EditorMode::isActiveTranslatable() ? 'true' : 'false' ?>,
    uploadable : <?php echo FrontendEditor::isActive() ? 'true' : 'false' ?>,
    linkable : <?php echo FrontendEditor::isActive() ? 'true' : 'false' ?>,
    requests : {
        admin : '<?php echo url('/admin') ?>',
        updateLink : '<?php echo action('\Admin\Controllers\FrontendEditorController@updateLink') ?>',
        updateContent : '<?php echo action('\Admin\Controllers\FrontendEditorController@updateContent') ?>',
        updateImage : '<?php echo action('\Admin\Controllers\FrontendEditorController@updateImage') ?>',

<?php if ( $lang = Localization::get() ){ ?>
        changeState : '<?php echo action('\Admin\Controllers\GettextController@updateEditorState', $lang->slug) ?>',
        updateText : '<?php echo action('\Admin\Controllers\GettextController@updateTranslations', $lang->slug) ?>',
<?php } ?>
    },
    ckeditor_path : '<?php echo admin_asset('/plugins/ckeditor/ckeditor.js') ?>',
    token : '<?php echo csrf_token() ?>'
};
</script>

<!-- We need admin props for CKEditor boot -->
<?php echo $__env->make('admin::partials.crudadmin-props', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<script src="<?php echo admin_asset('/js/FrontendEditor.js?v='.Admin::getAssetsVersion()) ?>"></script>
<link rel="stylesheet" href="<?php echo admin_asset('/css/frontend.css?v='.Admin::getAssetsVersion()) ?>">
<?php } ?>