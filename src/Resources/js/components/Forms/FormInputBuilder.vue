<template>
  <div v-bind:class="{ 'is-changed-from-history' : isChangedFromHistory }">
    <!-- STRING INPUT -->
    <div class="form-group" v-if="isString || isPassword">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <input v-bind:id="getId" @keyup="changeValue" :data-field="getFieldKey" v-bind:disabled="isDisabled" type="{{ isPassword ? 'password' : 'text' }}" v-bind:name="key" class="form-control" maxlength="{{ field.max }}" value="{{ !isPassword ? getValueOrDefault: '' }}" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- NUMBER/DECIMAL INPUT -->
    <div class="form-group" v-if="isInteger">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <input v-bind:id="getId" @keyup="changeValue" :data-field="getFieldKey" v-bind:disabled="isDisabled" type="number" v-bind:name="key" class="form-control" v-bind:step="isDecimal ? '0.01' : ''" v-bind:value="getValueOrDefault" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- DATETIME INPUT -->
    <div class="form-group" v-if="isDatepicker">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <input v-bind:id="getId" @keyup="changeValue" :data-field="getFieldKey" v-bind:disabled="isDisabled" type="text" v-bind:name="key" class="form-control" value="{{ getValueOrDefault }}" placeholder="{{ field.placeholder || getName }}">
      <small>{{ field.title }}</small>
    </div>

    <!-- Checkbox INPUT -->
    <div class="form-group" v-if="isCheckbox">
      <label v-bind:for="getId" class="checkbox">
        {{ getName }} <span v-if="field.placeholder">{{ field.placeholder }}</span>
        <input type="checkbox" @change="changeValue" v-bind:id="getId" :data-field="getFieldKey" v-bind:disabled="isDisabled" v-bind:checked="getValueOrDefault == 1" value="1" class="ios-switch green" v-bind:name="key">
        <div><div></div></div>
      </label>
      <small>{{ field.title }}</small>
    </div>

    <!-- TEXT INPUT -->
    <div class="form-group" v-if="isText || isEditor">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <textarea v-bind:id="getId" @change="changeValue" :data-field="getFieldKey" v-bind:disabled="isDisabled" v-bind:name="key" v-bind:class="{ 'form-control' : isText, 'js_editor' : isEditor }" rows="5" placeholder="{{ field.placeholder || getName }}">{{ getValueOrDefault }}</textarea>
      <small>{{ field.title }}</small>
    </div>

    <!-- FILE INPUT -->
    <div class="form-group" v-if="isFile">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>

      <div class="file-group">
        <input v-bind:id="getId" :data-field="getFieldKey" v-bind:disabled="isDisabled" type="file" v-bind:multiple="isMultipleUpload" v-bind:name="isMultipleUpload ? key + '[]' : key" @change="addFile" class="form-control" placeholder="{{ field.placeholder || getName }}">
        <input v-if="!field.value && file_will_remove == true" type="hidden" name="$remove_{{ key }}" value="1">

        <button v-if="field.value && !isMultipleUpload || !file_from_server" @click.prevent="removeFile" type="button" class="btn btn-danger btn-md" data-toggle="tooltip" title="" :data-original-title="trans('delete-file')"><i class="fa fa-remove"></i></button>

        <div v-show="isMultiple && !isMultirows && getFiles.length > 0">
          <select v-bind:id="getId + '_multipleFile'" v-bind:name="(isMultiple && !isMultirows && getFiles.length > 0) ? '$uploaded_'+key+'[]' : ''" data-placeholder=" " multiple>
            <option selected v-for="file in getFiles">{{ file }}</option>
          </select>
        </div>

        <small v-show="uploadSelectPluginAfterLoad">{{ field.title }}</small>

        <span v-if="field.value && !hasMultipleFilesValue && file_from_server && !isMultiple">
          <file :file="field.value" :field="key" :model="model"></file>
        </span>

      </div>
    </div>

    <!-- Row Confirmation -->
    <form-input-builder
      v-if="field.confirmed == true && !isConfirmation"
      :model="model"
      :history="history"
      :field="field"
      :index="index"
      :key="key + '_confirmation'"
      :row="row"
      :confirmation="true"></form-input-builder>

    <!-- SELECT INPUT -->
    <div class="form-group" v-if="isSelect">
      <label v-bind:for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
      <div :class="{ 'can-add-select' : canAddRow }">
        <select v-bind:id="getId" :data-field="getFieldKey" v-bind:disabled="isDisabled" name="{{ !isMultiple ? key : '' }}" v-bind:data-placeholder="field.placeholder ? field.placeholder : trans('select-option-multi')" v-bind:multiple="isMultiple" class="form-control">
          <option v-if="!isMultiple" value="">{{ trans('select-option') }}</option>
          <option v-for="mvalue in missingValueInSelectOptions" v-bind:value="mvalue" :selected="hasValue(mvalue, value, isMultiple)">{{ mvalue }}</option>
          <option v-for="data in fieldOptions" v-bind:selected="hasValue(data[0], value, isMultiple) || (!this.isOpenedRow && data[0] == field.default)" v-bind:value="data[0]">{{ data[1] }}</option>
        </select>
        <button v-if="canAddRow" @click="allowRelation = true" type="button" :data-target="'#'+getModalId" data-toggle="modal" class="btn-success"><i class="fa fa-plus"></i></button>
      </div>
      <small>{{ field.title }}</small>

      <!-- Modal for adding relation -->
      <div class="modal fade" v-if="canAddRow && allowRelation" :id="getModalId" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title">&nbsp;</h4>
            </div>
            <div class="modal-body">
              <model-builder
                :langid="langid"
                :hasparentmodel="getRelationModelParent"
                :parentrow="getRelationRow"
                :model="getRelationModel">
              </model-builder>
            </div>
          </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
      </div><!-- /.modal -->
    </div>

    <!-- RADIO INPUT -->
    <div class="form-group radio-group" v-if="isRadio">
      <label>{{ getName }} <span v-if="isRequired" class="required">*</span></label>
        <div class="radio" v-if="!isRequired">
          <label>
            <input type="radio" v-bind:name="key" value="">
            {{ trans('no-option') }}
          </label>
        </div>

        <div class="radio" v-for="data in field.options">
          <label>
            <input type="radio" @change="changeValue" v-bind:name="key" v-bind:checked="hasValue(data[0], getValueOrDefault)" v-bind:value="data[0]">

            {{ data[1] }}
          </label>
        </div>
      </select>
      <small>{{ field.title }}</small>
    </div>
  </div>
</template>

<script>
  import File from '../Partials/File.vue';
  import ModelBuilder from './ModelBuilder.vue';

  export default {
      name: 'form-input-builder',
      props: ['model', 'field', 'index', 'key', 'row', 'confirmation', 'history', 'langid', 'hasparentmodel'],

      components: { File },

      data(){
        return {
          file_will_remove : false,
          file_from_server : true,
          filterBy : null,
          allowRelation : false,
        };
      },

      created(){
        /*
         * Fix for double recursion in VueJS
         */
        this.$options.components['model-builder'] = Vue.extend(ModelBuilder);
      },

      ready()
      {
        this.bindDatepickers();

        this.bindFilters();

        this.onChangeSelect();

        this.$nextTick(function(){
          $('#'+this.getId).ckEditors();
        });
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
        newTitleRow(){
          return this.$root.getModelProperty(this.getRelationModel, 'settings.title.insert', this.trans('new-row'));
        },
        bindDatepickers(){
          if ( ! this.isDatepicker )
            return;

          jQuery.datetimepicker.setLocale('sk');

          //Add datepickers
          $('#' + this.getId).datetimepicker({
            lang: 'sk',
            format: this.getDateFormat,
            timepicker: this.field.type != 'date',
            datepicker: this.field.type != 'time',
            scrollInput: false,
          });
        },
        /*
         * If field has filters, then check of other fields values for filtrating
         */
        bindFilters(){
          if ( !this.isSelect && !this.isRadio )
            return;

          if ( !this.getFilterBy )
            return;

          this.$watch('row.'+this.getFilterBy[0], function(value){
            this.filterBy = value;
          })
        },
        /*
         * Apply event on changed value
         */
        changeValue(e, value, no_field){
          var value = e ? e.target.value : value;

          if ( this.field.type == 'checkbox' )
            value = e ? e.target.checked : value;

          //Update field values
          if ( no_field != true )
            this.field.value = value;

          this.$set('row.' + this.key, value)
        },
        /*
         * Apply on change events into selectbox
         */
        onChangeSelect(){
          if ( this.isSelect )
          {
            var select = $('#' + this.getId),
                _this = this;

            select.change(function(e){
              if ( _this.isMultiple ){
                //Chosen need to be updated after delay for correct selection order
                setTimeout(function(){
                  //Send values in correct order
                  _this.changeValue(null, $(this).getSelectionOrder());

                  //Update fake select on change value
                  _this.rebuildSelect();
                }.bind(this), 50);
              } else {
                _this.changeValue(null, $(this).val());
              }
            })
          }
        },
        chosenOptions(){
          return {
            disable_search_threshold: 10,
            search_contains : true
          };
        },
        updateField(field){
          if (field.type == 'file')
            this.file_from_server = true;

          //When VueJs DOM has been rendered
          this.$nextTick(function () {
            //After change value, update same value in ckeditor
            if ( field.type == 'editor'){
              var editor = CKEDITOR.instances[this.getId];

              //If is editor not ready yet, then wait for ready state
              editor.setData( field.value ? field.value : '' );
              editor.on('instanceReady', function(){
                editor.setData( field.value ? field.value : '' );
              });
            }

            //If is select
            if ( this.isSelect )
            {
              var select = $('#' + this.getId).chosen(this.chosenOptions()).trigger("chosen:updated");

              //Rebuild multiple order into fake select which will send data into request
              if ( this.isMultiple ){

                //Set selection order into multiple select
                if ( field.value ){
                  //Error exception when is some options missing, or filtrated by filters
                  try {
                    select.setSelectionOrder(field.value);
                  } catch(e){

                  }
                }

                this.rebuildSelect();
              }
            }

            //Update multiple files upload
            if ( this.field.type == 'file' && this.isMultiple && !this.isMultirows )
              $('#' + this.getId+'_multipleFile').chosen(this.chosenOptions()).trigger("chosen:updated");
          })
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
        hasValue(key, value, multiple)
        {
          if ( multiple == true && $.isArray(value) )
          {
            if ( value.indexOf( $.isNumeric(key) ? parseInt(key) : key ) > -1 )
              return true;
          } else if ((key || key == 0) && value && key == value) {
            return true;
          }

          return false;
        },
        rebuildSelect(){
          //If is not multiple select
          if ( !(this.isSelect && this.isMultiple) )
            return;

          var select = $('#' + this.getId),
              fake_select = select.prev();

          var values = select.getSelectionOrder();

          if ( ! fake_select.is('select') )
            fake_select = select.before('<select name="'+this.key+'[]" multiple="multiple" style="display: none"></select>').prev();

          //Remove inserted options
          fake_select.find('option').remove();

          for ( var i = 0; i < values.length; i++ )
            fake_select.append($('<option></option>').attr('selected', true).attr('value', values[i]).text(values[i]));
        },
        trans(key){
          return this.$root.trans(key);
        },
        getFilter(options){
          var filter = {};

          if ( (options && options[0] && typeof options[0][1] == 'object' && options[0][1] !== null) && ('language_id' in options[0][1]) == true )
            filter['language_id'] = this.getLangageId;

          if ( this.getFilterBy )
            filter[this.getFilterBy[1]] = this.filterBy;

          return filter;
        },
        pushOption(row, action){
          //Store or update option field
          if ( action == 'store' )
          {
            var filterBy = this.getFilterBy;

            //Add relation into added row
            if ( filterBy && this.row[filterBy[0]] )
              row[filterBy[1]] = this.row[filterBy[0]];

            //Push added option into array
            this.field.options.unshift([row.id, row]);

            //Set multiple values or one value
            if ( this.isMultiple ){
              if ( ! this.field.value )
                this.field.value = [row.id];
              else
                this.field.value.push(row.id);

              this.changeValue(null, this.field.value, false);
            } else {
              this.changeValue(null, row.id);
            }
          } else if ( action == 'update' ) {
            for ( var i = 0; i < this.field.options.length; i++ )
              if ( this.field.options[i][0] == row.id ){
                for ( var key in row )
                  this.field.options[i][1][key] = row[key];
              }
          } else if ( action == 'delete' ) {
            //Remove value also from field values
            if ( this.isMultiple ){
              if ( $.isArray(this.field.value) ){
                this.field.value.splice(this.field.value.indexOf(row), 1);

                this.changeValue(null, this.field.value, false);
              }
            } else if ( this.field.value == row ) {
              this.changeValue(null, null);
            }

            //Remove deleted field from options
            for ( var key in this.field.options ){
              if ( this.field.options[key][0] == row ){
                this.field.options.splice(key, 1)

                break;
              }
            }
          }
        },
      },

      computed : {
        getRelationRow(){
          var filterBy = this.getFilterBy;

          if ( ! filterBy || ! this.row[filterBy[0]] )
            return {};

          return {
            id : this.row[filterBy[0]],
          }
        },
        getRelationModel(){
          if ( ! this.canAddRow )
            return;

          var relationTable = (this.field.belongsTo||this.field.belongsToMany).split(',')[0];

          return this.$root.models_list[relationTable];
        },
        /*
         * Return model of parent filtration field
         */
        getRelationModelParent(){
          var filterBy = this.getFilterBy;

          if ( ! filterBy || ! this.row[filterBy[0]] )
            return false;

          var field = this.model.fields[filterBy[0]],
              relationTable = (field.belongsTo||field.belongsToMany).split(',')[0];

          return this.$root.models_list[relationTable];
        },
        isOpenedRow(){
          return this.row && 'id' in this.row;
        },
        fieldOptions(){
          //On change fields options rebuild select
          this.updateField(this.field);

          return this.$root.languageOptions(this.field.options, this.field, this.getFilter(this.field.options));
        },
        getId()
        {
          //Get parent model builder
          var modelBuilder = this.$parent;
          while(modelBuilder.$options.name != 'model-builder')
            modelBuilder = modelBuilder.$parent;

          parent = modelBuilder.getParentTableName(this.model.withoutParent == true);

          return 'id-' + this.model.slug + '-' + modelBuilder.depth_level + '-' + parent + '-' + this.index + '-' + this.key;
        },
        getModalId(){
          return 'form-relation-modal-'+this.getId;
        },
        getFieldKey()
        {
          return this.model.slug + '-' + this.key;
        },
        getFilterBy(){
          if ( !('filterBy' in this.field) )
            return null;

          var filterBy = this.field.filterBy.split(','),
              column;

          //Get column of relation field
          this.model.fields[column = filterBy[0]+'_id']||this.model.fields[column = filterBy[0]]

          filterBy[0] = column;

          return filterBy;
        },
        getName()
        {
          if ( this.isConfirmation )
          {
            return this.field.name + ' ('+this.trans('confirmation')+')';
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
          return this.field.type == 'text' || this.field.type == 'longtext';
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
        isRadio()
        {
          return this.field.type == 'radio';
        },
        isConfirmation()
        {
          return this.confirmation == true;
        },
        isDatepicker()
        {
          return this.field.type == 'date' || this.field.type == 'datetime' || this.field.type == 'time';
        },
        isCheckbox()
        {
          return this.field.type == 'checkbox';
        },
        isDisabled()
        {
          return this.field.disabled == true;
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
          return (this.isMultirows && !this.isOpenedRow) || this.isMultiple;
        },
        getDateFormat()
        {
          return this.field.date_format;
        },
        getValueOrDefault()
        {
          if ( ! this.isOpenedRow ){
            var default_value = this.field.default;

            //If is current date value in datepicker
            if ( this.isDatepicker && default_value && default_value.toUpperCase() == 'CURRENT_TIMESTAMP' ){
              default_value = moment().format(this.$root.fromPHPFormatToMoment(this.field.date_format));
            }

            return default_value;
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
        /*
         If is selected row, which not belongs to selected language,
         then select options from language of selected row
         */
        getLangageId(){
          return this.isOpenedRow && 'language_id' in this.row ? this.row.language_id : this.$root.language_id;
        },
        missingValueInSelectOptions(){
          if ( !this.isOpenedRow )
            return [];

          var options = this.fieldOptions,
              missing = [],
              original_value = this.field.$original_value;

          //For multiple selects
          if ( this.isMultiple )
          {
            if ( original_value )
            {
              for (var i = 0; i < original_value.length; i++)
              {
                var searched = options.filter(function(item){
                  return item[0] == original_value[i];
                }.bind(this));

                //Add missing values, when is filter off
                if (searched.length == 0 && !this.filterBy){
                  missing.push(original_value[i]);
                }
              }
            }
          }

          //For single select
          else {
            //Check if is value in options
            for ( var i = 0; i < options.length; i++ )
            {
              if ( options[i][0] == original_value )
                return [];
            }

            return this.filterBy || [null, undefined].indexOf(original_value) > -1 ? [] : [original_value];
          }

          return missing;
        },
        isChangedFromHistory(){
          if ( ! this.history )
            return false;

          return this.history.fields.indexOf(this.key) > -1;
        },
        /*
         * Can show adding row just for first level of forms (not when user click to add new row in form),
         * and also when is filter activated, then show just when is filter also selected
         */
        canAddRow(){
          return this.field.canAdd === true && this.hasparentmodel !== false && (!this.getFilterBy || this.filterBy);
        },
      },
  }
</script>