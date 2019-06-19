<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title>File Browser</title>
</head>
<body>

<script src="<?php echo admin_asset('/plugins/ckeditor/plugins/ckfinder/ckfinder.js') ?>"></script>
<script>
    CKFinder.config( {
        connectorPath: {!! json_encode(route('ckfinder_connector')) !!},
        pass : '_token',
        _token : '{!! csrf_token() !!}'
    } );
    CKFinder.start();
</script>

</body>
</html>