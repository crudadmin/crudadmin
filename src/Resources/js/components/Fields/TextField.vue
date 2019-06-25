<template>
    <div class="form-group" :class="{ disabled : disabled }">
        <label>{{ field_name }} <span v-if="required" class="required">*</span></label>
        <textarea
            rows="5"
            @keyup="changeValue"
            :id="id"
            :disabled="disabled"
            :name="field_key"
            :class="{ 'form-control' : isText, 'js_editor' : isEditor }"
            :placeholder="field.placeholder || field_name"
            :value="value">
        </textarea>
        <small>{{ field.title }}</small>
    </div>
</template>

<script>
    export default {
        props: ['id', 'model', 'field_name', 'field_key', 'field', 'value', 'required', 'disabled'],

        mounted(){
            this.$nextTick(function(){
                $('#'+this.id).ckEditors();
            });

            eventHub.$on('updateField', data => {
                if ( data[0] != this.field_key )
                    return;

                //After change value, update same value in ckeditor
                if ( ! this.isEditor )
                    return;

                var editor = CKEDITOR.instances[this.id];

                //If is editor not ready yet, then wait for ready state
                editor.setData( this.field.value ? this.field.value : '' );
                editor.on('instanceReady', function(){
                    editor.setData( this.field.value ? this.field.value : '' );
                });
            });
        },

        computed: {
            isText(){
                return ['text', 'longtext'].indexOf(this.field.type) > -1;
            },
            isEditor(){
                return ['editor', 'longeditor'].indexOf(this.field.type) > -1;
            },
        },

        methods : {
            changeValue(e){
                this.$parent.changeValue(e);
            },
        },
    }
</script>