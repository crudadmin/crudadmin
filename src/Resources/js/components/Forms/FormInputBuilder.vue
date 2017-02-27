<template>
    <!-- STRING INPUT -->
    <div class="form-group" v-if="isString || isPassword">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <input v-bind:id="getId" type="{{ isPassword ? 'password' : 'text' }}" v-bind:name="key" class="form-control" maxlength="{{ field.max }}" value="{{ !isPassword ? getValueOrDefault: '' }}" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- NUMBER/DECIMAL INPUT -->
    <div class="form-group" v-if="isInteger">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <input v-bind:id="getId" type="number" v-bind:name="key" class="form-control" v-bind:step="isDecimal ? '0.01' : ''" v-bind:value="getValueOrDefault" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- DATE INPUT -->
    <div class="form-group" v-if="isDate">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <input v-bind:id="getId" type="text" readonly v-bind:name="key" class="form-control js_date" value="{{ dateValue( getValueOrDefault ) }}" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- Checkbox INPUT -->
    <div class="form-group" v-if="isCheckbox">
      <label v-bind:for="getId" class="checkbox">
        {{ getName }} <span v-if="field.placeholder">{{ field.placeholder }}</span>
        <input type="checkbox" v-bind:id="getId" v-bind:checked="getValueOrDefault== 1" value="1" class="ios-switch green" v-bind:name="key">
        <div><div></div></div>
      </label>
      <small>{{ field.title }}</small>
    </div>

    <!-- TEXT INPUT -->
    <div class="form-group" v-if="isText || isEditor">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <textarea v-bind:id="getId" v-bind:name="key" v-bind:class="{ 'form-control' : isText, 'js_editor' : isEditor }" rows="5" placeholder="{{ field.placeholder || getName }}">{{ getValueOrDefault }}</textarea>
      <small>{{ field.title }}</small>
    </div>

    <!-- FILE INPUT -->
    <div class="form-group" v-if="isFile">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>

      <div class="form-group file-group">
        <input v-bind:id="getId" type="file" v-bind:multiple="isMultipleUpload" v-bind:name="isMultipleUpload ? key + '[]' : key" @change="addFile" class="form-control" placeholder="{{ field.placeholder || getName }}">
        <input v-if="!field.value && file_will_remove == true" type="hidden" name="$remove_{{ key }}" value="1">

        <button v-if="field.value && !isMultipleUpload || !file_from_server" @click.prevent="removeFile" type="button" class="btn btn-danger btn-md" data-toggle="tooltip" title="" data-original-title="Vymazať súbor"><i class="fa fa-remove"></i></button>

        <div v-show="isMultiple && !isMultirows && getFiles.length > 0">
          <select v-bind:id="getId + '_multipleFile'" v-bind:name="(isMultiple && !isMultirows && getFiles.length > 0) ? '$uploaded_'+key+'[]' : ''" data-placeholder=" " multiple>
            <option selected v-for="file in getFiles">{{ file }}</option>
          </select>
        </div>

        <small v-show="uploadSelectPluginAfterLoad">{{ field.title }}</small>

        <span v-if="field.value && !hasMultipleFilesValue && file_from_server && !isMultiple">
          <file :uploadpath="model.slug + '/' + key + '/' + field.value"></file>
        </span>

      </div>
    </div>

    <!-- Row Confirmation -->
    <form-input-builder v-if="field.confirmed == true && !isConfirmation" :model="model" :field="field" :index="index" :key="key + '_confirmation'" :row="row" :confirmation="true"></form-input-builder>

    <!-- SELECT INPUT -->
    <div class="form-group" v-if="isSelect">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <select v-bind:id="getId" name="{{ isMultiple ? key + '[]' : key }}" v-bind:data-placeholder="field.placeholder ? field.placeholder : 'Vyberte zo zoznamu možností'" v-bind:multiple="isMultiple" class="form-control">
        <option v-if="!isMultiple" value="">Vyberte jednú z možností</option>
        <option v-if="missingValueInSelectOptions" v-bind:value="value" selected="selected">{{ value }}</option>
        <option v-for="($key, option) in field.options | languageOptions" v-bind:selected="selectIndex(hasValue($key, value, isMultiple) || (!row && $key == field.default), $key)" v-bind:value="$key">{{ option }}</option>
      </select>
      <small>{{ field.title }}</small>
    </div>
</template>

<script>
  import File from '../Partials/File.vue';

  export default {
      name: 'form-input-builder',
      props: ['model', 'field', 'index', 'key', 'row', 'confirmation'],

      data(){
        return {
          file_will_remove : false,
          file_from_server : true,
          updateSelect : false,
        };
      },

      watch : {
        field : {
          deep : true,
          handler : function(field){
            this.updateField(field);
          }
        }
      },

      filters: {
        languageOptions(array){

          //Checks if values are devided by language
          var localization = false;

          for ( var key in array )
          {
            if (array[key] !== null && typeof array[key] === 'object')
            {
              localization = true;
              break;
            }
          }

          return localization ? array[ this.$root.language_id ] : array;
        }
      },

      ready()
      {
        if ( this.field.type == 'date' )
        {
          //Add datepickers
          $('#' + this.getId).datepicker({
            autoclose: true,
            format: this.getDateFormat,
          });

          this.getDateFormat;
        }
      },

      events : {
        onSubmit(row){
          if ( this.file_from_server == true && row != null )
            return;

          this.file_from_server = row ? true : false;

          this.field.value = row ? row[this.key] : '';
        },
        updateField(data){
          if ( data[0] != this.key )
            return;

          this.updateField(data[1]);
        },
      },

      methods : {
        updateField(field){

          //After change value, update same value in ckeditor
          if ( field.type == 'editor'){
            var editor = CKEDITOR.instances[this.getId];

            //If is editor not ready yet, then wait for ready state
            editor.setData( field.value ? field.value : '' );
            editor.on('instanceReady', function(){
              editor.setData( field.value ? field.value : '' );
            });
          }

          if (field.type == 'file')
            this.file_from_server = true;

          if ( this.isSelect )
          {
            $('#' + this.getId).chosen({disable_search_threshold: 10}).trigger("chosen:updated");
          }

          if ( this.field.type == 'file' && this.isMultiple && !this.isMultirows )
          {
            $('#' + this.getId+'_multipleFile').chosen({disable_search_threshold: 10}).trigger("chosen:updated");
          }
        },
        removeFile(){
          if ( ! this.isMultiple )
            this.field.value = null;

          this.file_will_remove = true;
          this.file_from_server = true;
          $('#'+this.getId).val('');
        },
        addFile(e){
          this.file_will_remove = false;
          this.file_from_server = false;
        },
        dateValue(value){
          return this.$parent.$parent.dateValue(value, this.field);
        },
        hasValue(key, value, multiple)
        {
          if ( multiple == true && $.isArray(value) )
          {
            if ( value.indexOf( $.isNumeric(key) ? parseInt(key) : key ) > -1 )
              return true;
          } else if (key && value && key == value) {
            return true;
          }

          return false;
        },
        selectIndex(select, index)
        {
          this.updateSelect = true;

          if ( this.updateSelectTimeout )
            clearTimeout(this.updateSelectTimeout);

          this.updateSelectTimeout = setTimeout(function(){
            $('#'+this.getId).trigger("chosen:updated");
          }.bind(this), 50);

          return select;
        }
      },

      computed : {
        getId()
        {
          var parent = 'getParentTableName' in this.$parent.$parent ?
            this.$parent.$parent.getParentTableName() : this.$parent.$parent.$parent.getParentTableName();

          return 'id-' + this.model.slug + '-' + parent + '-' + this.index + '-' + this.key;
        },
        getName()
        {
          if ( this.isConfirmation )
          {
            return this.field.name + ' (overenie znova)';
          }

          return this.field.name;
        },
        isString()
        {
          return this.field.type == 'string';
        },
        isDecimal()
        {
          return this.field.type == 'decimal';
        },
        isInteger()
        {
          return this.field.type == 'integer' || this.isDecimal;
        },
        isText()
        {
          return this.field.type == 'text';
        },
        isEditor()
        {
          return this.field.type == 'editor';
        },
        isFile()
        {
          return this.field.type == 'file';
        },
        isPassword()
        {
          return this.field.type == 'password';
        },
        isSelect()
        {
          return this.field.type == 'select';
        },
        isConfirmation()
        {
          return this.confirmation == true;
        },
        isDate()
        {
          return this.field.type == 'date';
        },
        isCheckbox()
        {
          return this.field.type == 'checkbox';
        },
        isMultiple()
        {
          return this.field.multiple && this.field.multiple === true || ('belongsToMany' in this.field);
        },
        isMultirows()
        {
          return this.field.multirows && this.field.multirows === true;
        },
        isMultipleUpload()
        {
          return (this.isMultirows && !this.row) || this.isMultiple;
        },
        getDateFormat()
        {
          var format = this.field.date_format.toLowerCase();

          return format.replace( 'd', 'dd' ).replace('m', 'mm').replace('y', 'yyyy');
        },
        getValueOrDefault()
        {
          if ( ! this.row )
          {
            return this.field.default;
          }

          return this.field.value;
        },
        uploadSelectPluginAfterLoad(){
          this.field.value;

          $('#' + this.getId + '_multipleFile').trigger("chosen:updated");

          return true;
        },
        value(){
          var value = this.field.value;

          if ( $.isArray(value) )
          {
            for ( var key in value )
            {
              if ( $.isNumeric( value[key] ) )
                value[key] = parseInt( value[key] );
            }
          }

          return value;
        },
        hasMultipleFilesValue(){
          return $.isArray(this.field.value);
        },
        getFiles(){
          var value = this.field.value;

          if ( ! value )
            return [];

          if ( $.isArray(value) )
            return value;

          return [ value ];
        },
        isRequired(){
            return 'required' in this.field && this.field.required == true;
        },
        missingValueInSelectOptions(){
          return this.row && !(this.field.value in this.field.options);
        },
      },

      components: { File },
  }
</script>