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