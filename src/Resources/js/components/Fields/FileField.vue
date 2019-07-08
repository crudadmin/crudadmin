<template>
    <div class="form-group" :class="{ disabled : disabled }">
        <label>{{ field_name }} <span v-if="required" class="required">*</span></label>

        <div class="file-group">
            <input ref="fileInput" :disabled="disabled" type="file" :multiple="isMultipleUpload" :name="isMultipleUpload ? field_key + '[]' : field_key" @change="addFile" class="form-control" :placeholder="field.placeholder || field_name">
            <input v-if="!value && file_will_remove == true" type="hidden" :name="'$remove_' + field_key" :value="1">

            <button v-if="value && !isMultipleUpload || !file_from_server" @click.prevent="removeFile" type="button" class="btn btn-danger btn-md" data-toggle="tooltip" title="" :data-original-title="trans('delete-file')"><i class="fa fa-remove"></i></button>

            <div v-show="(isMultiple && !isMultirows) && getFiles.length > 0">
                <select ref="multipleFiles" :name="(hasLocale || (isMultiple && !isMultirows) && getFiles.length > 0) ? '$uploaded_'+field_key+'[]' : ''" data-placeholder=" " multiple>
                    <option selected v-for="file in getFiles">{{ file }}</option>
                </select>
            </div>

            <small>{{ field.title }}</small>

            <span v-if="value && !hasMultipleFilesValue && file_from_server && !isMultiple">
                <file :file="value" :field="field_key" :model="model"></file>
            </span>

        </div>
    </div>
</template>

<script>
    import File from '../Partials/File.vue';

    export default {
        props: ['id', 'row', 'model', 'field_name', 'field_key', 'field', 'value', 'required', 'disabled', 'depth_level'],

        components : { File },

        data(){
            return {
                file_will_remove : false,
                file_from_server : true,
            };
        },

        mounted(){
            this.addMultipleFilesSupport(true);

            eventHub.$on('updateField', data => {
                if ( data.table != this.model.slug || data.depth_level != this.depth_level || data.key != this.field_key )
                    return;

                this.file_from_server = true;

                this.addMultipleFilesSupport();
            });

            eventHub.$on('onSubmit', data => {
                var row = data.row;

                if ( data.table != this.model.slug || data.depth_level != this.depth_level )
                    return;

                if ( this.file_from_server == true && row != null )
                    return;

                this.file_from_server = row ? true : false;

                this.field.value = row ? row[this.field_key] : '';

                //Reset input value after file has been sent
                $(this.$refs.fileInput).val('');
            });
        },

        computed: {
            isOpenedRow(){
                return this.row && 'id' in this.row;
            },
            isMultiple(){
                return this.field.multiple === true;
            },
            isMultirows(){
                return this.field.multirows && this.field.multirows === true;
            },
            isMultipleUpload(){
                return (this.isMultirows && !this.isOpenedRow) || this.isMultiple;
            },
            hasMultipleFilesValue(){
                return $.isArray(this.field.value);
            },
            hasLocale(){
                return 'locale' in this.field;
            },
            getFiles(){
                var value = this.value;

                if ( ! value )
                    return [];

                if ( $.isArray(value) )
                    return value;

                return [ value ];
            },
        },

        methods : {
            changeValue(e){
                this.$parent.changeValue(e);
            },
            addMultipleFilesSupport(with_watcher){
                //Update multiple files upload
                if ( this.field.type == 'file' && this.isMultiple && !this.isMultirows ){
                    $(this.$refs.multipleFiles).chosen({
                        disable_search_threshold: 10,
                        search_contains : true
                    }).trigger('chosen:updated');
                }

                //On update value
                if ( with_watcher == true )
                {
                    this.$watch('field.value', () => {
                        this.$nextTick(() => {
                            $(this.$refs.multipleFiles).trigger('chosen:updated');
                        });
                    });
                }
            },
            removeFile(){
                if ( ! this.isMultiple ){
                    if ( this.hasLocale )
                        this.field.value[this.langslug] = null;
                    else
                        this.field.value = null;
                }

                this.file_will_remove = true;
                this.file_from_server = true;

                $('#'+this.getId).val('');
            },
            addFile(e){
                this.file_will_remove = false;
                this.file_from_server = false;
            },
            trans(key){
                return this.$root.trans(key);
            },
        },
    }
</script>