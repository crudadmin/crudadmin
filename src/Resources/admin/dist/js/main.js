$(function(){

    var array = { };
        //Make the dashboard widgets sortable Using jquery UI
        jQuery(".connectedSortable").sortable({
            // connectWith: ".connectedSortable",
            placeholder: "sort-highlight",
            handle: ".box-header:not(.notDrag), .nav-tabs",
            forcePlaceholderSize: true,
            zIndex: 999999,
            start: function(e, ui){
                ui.placeholder.height(ui.item.height());
            },
            update: function(event, ui) {
                var parent = $(ui.item[0]).parent();

                jQuery(parent).find('input[name="row_id"].order').each(function(i){
                    array['order['+i+']'] = $(this).val();
                });

                if (parent.attr('data-update')!=''){
                    var data = parent.attr('data-update');
                        data = mq.parseJSON(data);

                    if (!('table' in data))
                        return false;

                    for (var key in data){
                           array[key] = data[key]
                    }

                    console.log(array)

                    array['submit'] = '';
                    mq.ajax(data.path, array, function(e){

                    });

                }
            }
        }).disableSelection();
        jQuery(".connectedSortable .box-header:not(.notDrag), .connectedSortable .nav-tabs-custom").css("cursor", "move");

        jQuery.fn.ckEditors = function(){
            if (this.length<0)
                return this;

            this.each(function(){
                $(this).removeClass('js_editor');
                CKEDITOR.config.height = $(this).attr('data-height') ? $(this).attr('data-height') : '250px';
                CKEDITOR.replace($(this).attr('id'));
            });
        };

})