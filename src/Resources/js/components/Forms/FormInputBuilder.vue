<template>
    <!-- STRING INPUT -->
    <div class="form-group" v-if="isString || isPassword">
      <label for="{{ getId }}">{{ getName }}</label>
      <input id="{{ getId }}" type="{{ isPassword ? 'password' : 'text' }}" name="{{ key }}" class="form-control" maxlength="{{ field.max }}" value="{{ !isPassword ? field.value : '' }}" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- NUMBER/DECIMAL INPUT -->
    <div class="form-group" v-if="isInteger">
      <label for="{{ getId }}">{{ getName }}</label>
      <input id="{{ getId }}" type="number" name="{{ key }}" class="form-control" v-bind:step="isDecimal ? '0.02' : ''" value="{{ field.value }}" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- DATE INPUT -->
    <div class="form-group" v-if="isDate">
      <label for="{{ getId }}">{{ getName }}</label>
      <input id="{{ getId }}" type="text" readonly name="{{ key }}" class="form-control js_date" value="{{ dateValue( field.value ) }}" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- Checkbox INPUT -->
    <div class="form-group" v-if="isCheckbox">
      <label for="{{ getId }}" class="checkbox">
        {{ getName }}
        <input type="checkbox" id="{{ getId }}" v-bind:checked="field.value == 1" value="1" class="ios-switch green" name="{{ key }}">
        <div><div></div></div>
      </label>
      <small>{{ field.title }}</small>
    </div>

    <!-- TEXT INPUT -->
    <div class="form-group" v-if="isText || isEditor">
      <label for="{{ getId }}">{{ getName }}</label>
      <textarea id="{{ getId }}" name="{{ key }}" v-bind:class="{ 'form-control' : isText, 'js_editor' : isEditor }" rows="5" placeholder="{{ field.placeholder || getName }}">{{ field.value }}</textarea>
      <small>{{ field.title }}</small>
    </div>

    <!-- FILE INPUT -->
    <div class="form-group" v-if="isFile">
      <label for="{{ getId }}">{{ getName }}</label>

      <div class="form-group file-group">
        <input id="{{ getId }}" type="file" v-bind:multiple="isMultiple && !row" name="{{ isMultiple && !row ? key + '[]' : key }}" @change="addFile" class="form-control" placeholder="{{ field.placeholder || getName }}">
        <input v-if="!field.value && file_will_remove == true" type="hidden" name="$remove_{{ key }}" value="1">
        <button v-if="field.value || !file_from_server" @click.prevent="removeFile" type="button" class="btn btn-danger btn-md" data-toggle="tooltip" title="" data-original-title="Vymazať súbor"><i class="fa fa-remove"></i></button>

        <small>{{ field.title }}</small>
        <span v-if="field.value && file_from_server">
          <file :uploadpath="model.slug + '/' + key + '/' + field.value"></file>
        </span>
      </div>
    </div>

    <!-- Row Confirmation -->
    <form-input-builder v-if="field.confirmed == true && !isConfirmation" :model="model" :row="row" :index="index" :key="key + '_confirmation'" :field="field" :confirmation="true"></form-input-builder>

    <!-- SELECT INPUT -->
    <div class="form-group" v-if="isSelect">
      <label for="{{ getId }}">{{ getName }}</label>
      <select id="{{ getId }}" name="{{ isMultiple ? key + '[]' : key }}" v-bind:multiple="isMultiple" class="form-control">
        <option v-if="!isMultiple" value="">Vyberte jednú z možností</option>
        <option v-for="option in field.options | languageOptions" v-bind:selected="hasValue($key, field.value, isMultiple) || (!row && $key == field.default)" value="{{ $key }}">{{ option }}</option>
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
        };
      },

      watch : {
        field : {
          deep : true,
          handler : function(newField, oldField){

            //After change value, update same value in ckeditor
            if ( newField.type == 'editor' ){
              CKEDITOR.instances[this.getId].setData( newField.value ? newField.value : '' );
            }

            if (newField.type == 'file')
              this.file_from_server = true;
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
        if ( this.field.type == 'date' && 'date_format' in this.field )
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
        }
      },

      methods : {
        removeFile(){
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
          } else if (key == value) {
            return true;
          }

          return false;
        }
      },

      computed : {
        getId()
        {
          return 'id-' + this.model.slug + '-' + this.index + '-' + this.key;
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
        getDateFormat()
        {
          var format = this.field.date_format.toLowerCase();

          return format.replace( 'd', 'dd' ).replace('m', 'mm').replace('y', 'yyyy');
        }
      },

      components: { File },
  }
</script>