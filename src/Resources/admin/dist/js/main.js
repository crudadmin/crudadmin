jQuery.fn.ckEditors = function(){
    if (this.length<0)
        return this;

    $(this).each(function(){
        if ( ! $(this).hasClass('js_editor') || $(this).hasClass('editor_replaced') )
            return;

        $(this).addClass('editor_replaced');

        CKEDITOR.config.height = $(this).attr('data-height') ? $(this).attr('data-height') : '250px';
        CKEDITOR.replace($(this).attr('id')).on('instanceReady', () => {
            $(this).removeClass('js_editor');
        });
    });
};

//Check if validated input belongs to actual form level
jQuery.fn.firstLevelForm = function(form){
    return $(this).filter(function(){
        return $(this).parents('form')[0] == form;
    });
};