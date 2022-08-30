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
<?php echo $__env->make('admin::directives.frontendeditor', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

/* We need admin props for CKEditor boot */
<?php echo $__env->make('admin::partials.crudadmin-props', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</script>

<script src="<?php echo admin_asset('/js/FrontendEditor.js') ?>"></script>
<link rel="stylesheet" href="<?php echo admin_asset('/css/frontend.css') ?>">
<?php } ?>