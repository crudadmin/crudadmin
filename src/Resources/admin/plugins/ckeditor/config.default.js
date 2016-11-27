/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here. For example:
    config.language = 'sk';
    config.defaultLanguage = 'sk';

    //CKFinder
    config.filebrowserBrowseUrl = 'js/plugins/ckeditor/plugins/ckfinder/ckfinder.html';
    config.filebrowserImageBrowseUrl = 'js/plugins/ckeditor/plugins/ckfinder/ckfinder.html?type=Images';
    config.filebrowserFlashBrowseUrl = 'js/plugins/ckeditor/plugins/ckfinder/ckfinder.html?type=Flash';
    //config.filebrowserUploadUrl = 'js/plugins/ckeditor/plugins/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
    //config.filebrowserImageUploadUrl = 'js/plugins/ckeditor/plugins/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
    config.filebrowserFlashUploadUrl = 'js/plugins/ckeditor/plugins/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';
    config.filebrowserWindowWidth = '50%';
    config.filebrowserWindowHeight = '50%';


    config.toolbar = [
        { name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates', '-', 'Undo', 'Redo' ] },
        { name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord' ] },
        { name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },
        // { name: 'forms', items: [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },
        { name: 'links', items: [ 'Link', 'Unlink', /*'Anchor'*/ ] },
        '/',
        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
        { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'BidiLtr', 'BidiRtl', /*'Language'*/ ] },
        { name: 'insert', items: [ 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', /*'PageBreak', */ 'Iframe', 'oembed' ] },
        '/',
        { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
        { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
        { name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },
        { name: 'others', items: [ '-' ] },
        // { name: 'about', items: [ 'About' ] }
    ];

    // Toolbar groups configuration.
    config.toolbarGroups = [
        { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
        { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
        { name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ] },
        { name: 'forms' },
        '/',
        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
        { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
        { name: 'links' },
        { name: 'insert' },
        { name: 'oembed' },
        '/',
        { name: 'styles' },
        { name: 'colors' },
        { name: 'tools' },
        { name: 'others' },
        { name: 'about' }
    ];

    config.allowedContent = {
        script: true,
        $1: {
            // This will set the default set of elements
            elements: CKEDITOR.dtd,
            attributes: true,
            styles: true,
            classes: true
        }
    };

    config.entities = false;
    config.enterMode = CKEDITOR.ENTER_BR;
    config.extraPlugins = 'oembed';
};