<template>
  <div>
    <div v-if="!hasComponent" v-show="canShowField" :class="{ 'is-changed-from-history' : isChangedFromHistory }">
      <!-- STRING INPUT -->
      <div class="form-group" :class="{ disabled : isDisabled }" v-if="isString || isPassword">
        <label :for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
        <input :id="getId" @keyup="changeValue" :data-field="getFieldKey" :disabled="isDisabled" :type="isPassword ? 'password' : 'text'" :name="getFieldName" class="form-control" :maxlength="field.max" :value="getValueOrDefault" :placeholder="field.placeholder || getName">
        <small>{{ field.title }}</small>
      </div>

      <!-- NUMBER/DECIMAL INPUT -->
      <div class="form-group" :class="{ disabled : isDisabled }" v-if="isInteger">
        <label :for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
        <input :id="getId" @keyup="changeValue" :data-field="getFieldKey" :disabled="isDisabled" type="number" :name="getFieldName" class="form-control" :step="isDecimal ? '0.01' : ''" :value="getValueOrDefault" :placeholder="field.placeholder || getName">
        <small>{{ field.title }}</small>
      </div>

      <!-- DATETIME INPUT -->
      <div class="form-group" :class="{ disabled : isDisabled, 'multiple-date' : isMultipleDatepicker }" v-if="isDatepicker">
        <label :for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
        <input :id="getId" @keyup="changeValue" :data-field="getFieldKey" :disabled="isDisabled" type="text" :name="isMultipleDatepicker ? '' : getFieldName" class="form-control" :value="getValueOrDefault" :placeholder="field.placeholder || getName">
        <input type="hidden" :name="getFieldName + '[]'" v-for="date in getMultiDates" :value="getMultiDateValue(date)">
        <small>{{ field.title }}</small>
      </div>

      <!-- Checkbox INPUT -->
      <div class="form-group" :class="{ disabled : isDisabled }" v-if="isCheckbox">
        <label :for="getId" class="checkbox">
          {{ getName }} <span v-if="field.placeholder">{{ field.placeholder }}</span>
          <input type="checkbox" @change="changeValue" :id="getId" :data-field="getFieldKey" :disabled="isDisabled" :checked="getValueOrDefault == 1" value="1" class="ios-switch green" :name="getFieldName">
          <div><div></div></div>
        </label>
        <small>{{ field.title }}</small>
      </div>

      <!-- TEXT INPUT -->
      <div class="form-group" :class="{ disabled : isDisabled }" v-if="isText || isEditor">
        <label :for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>
        <textarea :id="getId" @change="changeValue" :data-field="getFieldKey" :disabled="isDisabled" :name="getFieldName" :class="{ 'form-control' : isText, 'js_editor' : isEditor }" rows="5" :placeholder="field.placeholder || getName">{{ getValueOrDefault }}</textarea>
        <small>{{ field.title }}</small>
      </div>

      <!-- FILE INPUT -->
      <div class="form-group" :class="{ disabled : isDisabled }" v-if="isFile">
        <label :for="getId">{{ getName }} <span v-if="isRequired" class="required">*</span></label>

        <div class="file-group">
          <input :id="getId" :data-field="getFieldKey" :disabled="isDisabled" type="file" :multiple="isMultipleUpload" :name="isMultipleUpload ? getFieldName + '[]' : getFieldName" @change="addFile" class="form-control" :placeholder="field.placeholder || getName">
          <input v-if="!getValueOrDefault && file_will_remove == true" type="hidden" :name="'$remove_' + getFieldName" :value="1">

          <button v-if="getValueOrDefault && !isMultipleUpload || !file_from_server" @click.prevent="removeFile" type="button" class="btn btn-danger btn-md" data-toggle="tooltip" title="" :data-original-title="trans('delete-file')"><i class="fa fa-remove"></i></button>

          <div v-show="(isMultiple && !isMultirows) && getFiles.length > 0">
            <select :id="getId + '_multipleFile'" :name="(hasLocale || (isMultiple && !isMultirows) && getFiles.length > 0) ? '$uploaded_'+getFieldName+'[]' : ''" data-placeholder=" " multiple>
              <option selected v-for="file in getFiles">{{ file }}</option>
            </select>
          </div>

          <small>{{ field.title }}</small>

          <span v-if="getValueOrDefault && !hasMultipleFilesValue && file_from_server && !isMultiple">
            <file :file="getValueOrDefault" :field="field_key" :model="model"></file>
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
        :field_key="field_key + '_confirmation'"
        :row="row"
        :confirmation="true"></form-input-builder>

      <!-- SELECT INPUT -->
      <div class="form-group" :class="{ disabled : isDisabled || hasNoFilterValues }" v-show="isRequired || !hasNoFilterValues" v-if="isSelect">
        <label :for="getId">{{ getName }} <span v-if="isRequired || isRequiredIfHasValues" class="required">*</span></label>
        <div :class="{ 'can-add-select' : canAddRow }">
          <select :id="getId" :data-field="getFieldKey" :disabled="isDisabled" :name="!isMultiple ? key : ''" :data-placeholder="field.placeholder ? field.placeholder : trans('select-option-multi')" :multiple="isMultiple" class="form-control">
            <option v-if="!isMultiple" value="">{{ trans('select-option') }}</option>
            <option v-for="mvalue in missingValueInSelectOptions" :value="mvalue" :selected="hasValue(mvalue, value, isMultiple)">{{ mvalue }}</option>
            <option v-for="data in fieldOptions" :selected="hasValue(data[0], value, isMultiple) || ((!value || value === 0) && !this.isOpenedRow && data[0] == defaultFieldValue)" :value="data[0]">{{ data[1] == null ? trans('number') + ' ' + data[0] : data[1] }}</option>
          </select>
          <button v-if="canAddRow" @click="allowRelation = true" type="button" :data-target="'#'+getModalId" data-toggle="modal" class="btn-success"><i class="fa fa-plus"></i></button>
        </div>
        <small>{{ field.title }}</small>
        <input v-if="!hasNoFilterValues && isRequiredIfHasValues" type="hidden" :name="'$required_'+key" value="1">

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
              <input type="radio" :name="getFieldName" value="">
              {{ trans('no-option') }}
            </label>
          </div>

          <div class="radio" v-for="data in field.options">
            <label>
              <input type="radio" @change="changeValue" :name="getFieldName" :checked="hasValue(data[0], getValueOrDefault)" :value="data[0]">

              {{ data[1] }}
            </label>
          </div>
          <small>{{ field.title }}</small>
      </div>
    </div>

    <component
      v-if="hasComponent"
      :model="model"
      :field="field"
      :history_changed="isChangedFromHistory"
      :row="row"
      :key="key"
      :is="componentName">
    </component>
  </div>
</template>

<script>
  var Vue = require('vue');

  import File from '../Partials/File.vue';
  import ModelBuilder from './ModelBuilder.vue';

  export default {
      name: 'form-input-builder',
      props: ['model', 'field', 'field_key', 'index', 'row', 'confirmation', 'history', 'langid', 'hasparentmodel', 'langslug'],

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

        this.registerComponents();

        eventHub.$on('updateField', data => {
          if ( data[0] != this.field_key )
            return;

          this.updateField(data[1]);
        });
      },

      mounted()
      {
        //If this field has own component
        this.syncFieldsValueWithRow();

        this.bindDatepickers();

        this.bindFilters();

        this.onChangeSelect();

        this.$nextTick(function(){
          $('#'+this.getId).ckEditors();
        });

        this.addMultipleFilesSupport(true);
      },
      events : {
        onSubmit(data){
          var row = data.row;

          if ( data.table != this.model.slug || ! this.isFile )
            return;

          if ( this.file_from_server == true && row != null )
            return;

          this.file_from_server = row ? true : false;

          this.field.value = row ? row[this.field_key] : '';

          //Reset input value after file has been sent
          $('#' + this.getId).val('');
        },
      },

      methods : {
        //We need reset empty values because of infinity loop in setter
        //when is NaN setted
        resetEmptyValue(value){
          if ( value === undefined || _.isNaN(value) )
            return null;

          return value;
        },
        syncFieldsValueWithRow(){
          this.$watch('row.'+this.field_key, function(value){
            this.field.value = this.resetEmptyValue(value);
          });

          this.$watch('field.value', function(value){
            this.row[this.field_key] = this.resetEmptyValue(value);
          });
        },
        registerComponents(){
          if ( !('component' in this.field) )
            return;

          var components = this.field.component.split(','),
              component = null;

          for ( var i = 0; i < components.length; i++ )
          {
            var name = components[i].toLowerCase(),
                data = this.model.components[name],
                obj;

            try {
                obj = (new Function('return '+data))();
            } catch(error){
                console.error('Syntax error in component ' + component[i] + '.Vue' + "\n", error);
                continue;
            }

            if ( ! component )
              component = obj;
            else {
              if ( !('components' in component) )
                component.components = {};

              component.components[components[i]] = obj;
            }
          }

          if ( component )
            Vue.component(this.componentName, component);
        },
        newTitleRow(){
          return this.$root.getModelProperty(this.getRelationModel, 'settings.title.insert', this.trans('new-row'));
        },
        bindDatepickers(){
          var _this = this;

          if ( ! this.isDatepicker )
            return;

          //Add datepickers
          $('#' + this.getId).datetimepicker({
            lang: this.$root.locale,
            format: this.getDateFormat,
            timepicker: this.field.type != 'date',
            datepicker: this.field.type != 'time',
            scrollInput: false,
            timepickerScrollbar: false,
            step : this.field.date_step||30,
            scrollMonth: false,
            scrollYear: false,
            inline : this.isMultipleDatepicker,
            onGenerate: ! this.isMultipleDatepicker ? null : function( ct ){
              $(this).addClass('multiple-dates');

              var values = _this.getMultiDates;

              for ( var i = 0; i < values.length; i++ )
              {
                var date = _this.field.type == 'time' ? values[i].split(':') : new Date(values[i]);

                var selector = _this.field.type == 'time'
                                  ? 'div[data-hour="'+parseInt(date[0])+'"][data-minute="'+parseInt(date[1])+'"]'
                                  : 'td[data-date="'+date.getDate()+'"][data-month="'+date.getMonth()+'"][data-year="'+date.getFullYear()+'"]';

                $(this).find(selector).addClass('multiple-selected');
              }
            },
            onChangeDateTime: ! this.isMultipleDatepicker ? null : function(current_date_time) {
                var pickedDate = moment(current_date_time).format(this.field.type == 'time' ? 'HH:mm' : 'YYYY-MM-DD');

                var value = this.getMultiDates,
                    index = value.indexOf(pickedDate);

                if ( index > -1 )
                  value.splice(index, 1);
                else
                  value.push(pickedDate);

                this.changeValue(null, value);
            }.bind(this)
          });
        },
        getMultiDateValue(time){
          if ( this.field.type == 'time' && time.length == 5 && time[2] == ':' )
            return time;

          return moment(time).format('YYYY-MM-DD');
        },
        /*
         * If field has filters, then check of other fields values for filtrating
         */
        bindFilters(){
          if ( !this.isSelect && !this.isRadio )
            return;

          //If is filterer key is not from parent model
          if ( !this.getFilterBy || this.isParentFilterColumn )
            return;

          this.$watch('row.'+this.getFilterBy[0], function(value){
            this.filterBy = value;
          })
        },
        /*
         * If field has setters, then check for change of changer field
         */
        reloadSetters(value){
          for ( var key in this.model.fields )
          {
            var field = this.model.fields[key],
                fillBy = this.getFillBy(field);

            if ( ! fillBy || ! fillBy[0] || (fillBy[0] != this.field_key && fillBy[0] + '_id' != this.field_key) )
              continue;

            var options = this.field.options||[];

            for ( var k in options )
            {
              //Skip other values
              if ( options[k][0] != value )
                continue;

              this.$set('row.' + key, options[k][1][fillBy[1]||key]);

              break;
            }

          }
        },
        /*
         * Apply event on changed value
         */
        changeValue(e, value, no_field){
          var value = e ? e.target.value : value;

          if ( this.field.type == 'checkbox' )
            value = e ? e.target.checked : value;

          //Update specific language field
          if ( this.hasLocale ){
            var obj_value = typeof this.field.value === 'object' ? this.field.value||{} : {};
                obj_value[this.langslug] = value;

            //Update field values
            if ( no_field != true )
              this.field.value = obj_value;

            //Update specific row language value
            this.$set('row.' + this.field_key + '.' + this.langslug, value);
            return;
          }

          //Update field values
          if ( no_field != true )
            this.field.value = value;

          this.row[this.field_key] = value;
        },
        /*
         * Apply on change events into selectbox
         */
        onChangeSelect(){
          if ( this.isSelect )
          {
            var select = $('#' + this.getId),
                is_change = false,
                _this = this;

            select.change(function(e){
              is_change = true;

              if ( _this.isMultiple ){
                //Chosen need to be updated after delay for correct selection order
                setTimeout(function(){
                  //Send values in correct order
                  _this.changeValue(null, $(this).getSelectionOrder());

                  //Update fake select on change value
                  _this.rebuildSelect();
                }.bind(this), 50);
              } else {
                var value = $(this).val();

                _this.changeValue(null, value);

                _this.reloadSetters(value);
              }
            });

            //If field value has been updated by setter and not by the user
            this.$watch('field.value', function(value, oldvalue){
              if (
                is_change === true
                || ! value
                || (value === oldvalue || _.isEqual(value, oldvalue))
              ){
                is_change = false;
                return;
              }

              //Update selects when vuejs is fully rendered
              this.$nextTick(function(){
                this.reloadSelectWithMultipleOrders(this.field);
              })
            });
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
            if ( ['editor', 'longeditor'].indexOf(field.type) > -1 ){
              var editor = CKEDITOR.instances[this.getId];

              //If is editor not ready yet, then wait for ready state
              editor.setData( field.value ? field.value : '' );
              editor.on('instanceReady', function(){
                editor.setData( field.value ? field.value : '' );
              });
            }

            //Update datepickers
            this.bindDatepickers();

            //If is select
            if ( this.isSelect ){
              this.reloadSelectWithMultipleOrders(field);
            }

            this.addMultipleFilesSupport();
          })
        },
        addMultipleFilesSupport(with_watcher){
          //Update multiple files upload
          if ( this.field.type == 'file' && this.isMultiple && !this.isMultirows ){
            $('#' + this.getId+'_multipleFile').chosen(this.chosenOptions()).trigger("chosen:updated");
          }

          //On update value
          if ( with_watcher == true )
          {
            this.$watch('field.value', function(){
              this.$nextTick(function(){
                $('#' + this.getId + '_multipleFile').trigger("chosen:updated");
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
            fake_select = select.before('<select name="'+this.field_key+'[]" multiple="multiple" style="display: none"></select>').prev();

          //Remove inserted options
          fake_select.find('option').remove();

          for ( var i = 0; i < values.length; i++ )
            fake_select.append($('<option></option>').attr('selected', true).attr('value', values[i]).text(values[i]));
        },
        reloadSelectWithMultipleOrders(field){
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
        },
        trans(key){
          return this.$root.trans(key);
        },
        getFilter(options){
          var filter = {};

          if ( (options && options[0] && typeof options[0][1] == 'object' && options[0][1] !== null) && ('language_id' in options[0][1]) == true )
            filter['language_id'] = this.getLangageId;

          if ( this.getFilterBy )
            filter[this.getFilterBy[1]] = this.isStaticFilterColumn ? this.getStaticFilterBy : this.filterBy;

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
        //Get parent model builder
        getModelBuilder(slug, except){
          var modelBuilder = this.$parent,
              except = slug === '$parent' ? this.model.slug : null,
              slug = slug === '$parent' ? null : slug;

          while(modelBuilder && (
            modelBuilder.$options.name != 'model-builder'
            || (slug && modelBuilder.model.slug != slug)
            || (except && modelBuilder.model.slug === except)
          ))
            modelBuilder = modelBuilder.$parent;

          if ( slug && (!modelBuilder || modelBuilder.model.slug != slug) ){
            console.error('Model with table name "' + slug + '" does not exists in parents tree of models');

            return null;
          }

          return modelBuilder;
        },
        getFillBy(field){
          if ( !('fillBy' in field) )
            return null;

          var filterBy = field.fillBy.replace(',', '.').split('.'),
              column;

          //Get column of relation field
          this.model.fields[column = filterBy[0]+'_id']||this.model.fields[column = filterBy[0]]

          filterBy[0] = column;

          return filterBy;
        },
      },

      computed : {
        canShowField(){
          if ( this.field.ifExists === true && ! this.isOpenedRow )
            return false;

          if ( this.field.ifDoesntExists === true && this.isOpenedRow )
            return false;

          return true;
        },
        getMultiDates(){
          var value = this.field.value||[];

          if ( ! $.isArray(value) )
            value = [];

          //Check correct inputs values
          var val = _.cloneDeep(value).filter(function(item){
              return item.length == (this.field.type == 'time' ? 5 : 10);
          }.bind(this));

          return val;
        },
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
          var modelBuilder = this.getModelBuilder();

          parent = modelBuilder.getParentTableName(this.model.withoutParent == true);

          return 'id-' + this.model.slug + this.field_key + '-' + modelBuilder.depth_level + '-' + parent + '-' + this.index + '-' + this.langslug;
        },
        getModalId(){
          return 'form-relation-modal-'+this.getId;
        },
        getFieldKey()
        {
          return this.model.slug + '-' + this.field_key;
        },
        getFieldName()
        {
          if ( this.hasLocale )
            return this.field_key+'['+this.langslug+']';

          return this.field_key;
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
        /*
         * Return value of relation column from actual model or parent model by slug
         */
        getStaticFilterBy()
        {
          var column = this.getFilterBy[0].split('.'),
              model = column.length == 2 ? this.getModelBuilder(column[0]) : this;

          return model.row[column[column.length - 1]];
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
          return this.field.type == 'editor'  || this.field.type == 'longeditor';
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
          return this && this.field.type == 'select';
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
          return this && (this.field.type == 'date' || this.field.type == 'datetime' || this.field.type == 'time');
        },
        isMultipleDatepicker()
        {
          return this.field.multiple == true && this.isDatepicker;
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
        hasComponent(){
          return 'component' in this.field && this.field.component;
        },
        componentName(){
          if ( ! this.hasComponent )
            return;

          return this.field.component.split(',')[0].toLowerCase();
        },
        getValueOrDefault()
        {
          //If is password, return none value
          if ( this.isPassword )
            return '';

          if ( this.isMultipleDatepicker )
            return JSON.stringify(this.field.value||[]);

          //If row is not opened, then return default field value
          if ( ! this.isOpenedRow ){
            return this.defaultFieldValue;
          }

          //Localization field
          if ( this.hasLocale )
          {
            if ( this.field.value && this.langslug in this.field.value )
              return this.field.value[this.langslug];

            return this.defaultFieldValue;
          }

          return this.field.value;
        },
        defaultFieldValue(){
          var default_value = this.field.value||this.field.default;

          if ( ! default_value || ['number', 'string', 'boolean'].indexOf(typeof default_value) === -1 )
            return '';

          //If is current date value in datepicker
          if ( this.isDatepicker && default_value.toUpperCase() == 'CURRENT_TIMESTAMP' )
            default_value = moment().format(this.$root.fromPHPFormatToMoment(this.field.date_format));

          //Get value by other table
          if ( this.field.default )
          {
            var default_parts = this.field.default.split('.');

            if ( default_parts.length == 2 )
            {
              var model = this.getModelBuilder(default_parts[0]);

              if ( model && (default_parts[1] in model.row) )
                return model.row[default_parts[1]];
            }
          }

          return default_value||'';
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
          var value = this.getValueOrDefault;

          if ( ! value )
            return [];

          if ( $.isArray(value) )
            return value;

          return [ value ];
        },
        isRequired(){
          //Basic required attribute
          if ( 'required' in this.field && this.field.required == true )
              return true;

          //Required if attribute
          if ( this.field.required_if )
          {
            var parts = this.field.required_if.split(','),
                value = this.row[parts[0]];

            if (value && parts.slice(1).indexOf(value) > -1)
              return true;
          }

          //Required without attribute
          if ( this.field.required_without )
          {
            var parts = this.field.required_without.split(',');

            for ( var i = 0; i < parts.length; i++ )
            {
              if ( ! this.row[parts[i]] )
                return true;
            }
          }

          //Required without attribute
          if ( this.field.required_with )
          {
            var parts = this.field.required_with.split(',');

            for ( var i = 0; i < parts.length; i++ )
            {
              if ( this.row[parts[i]] )
                return true;
            }
          }

          return false;
        },
        isRequiredIfHasValues(){
          return 'required_with_values' in this.field && this.field.required_with_values == true;
        },
        hasLocale(){
          return 'locale' in this.field;
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

          return this.history.fields.indexOf(this.field_key) > -1;
        },
        /*
         * Can show adding row just for first level of forms (not when user click to add new row in form),
         * and also when is filter activated, then show just when is filter also selected
         */
        canAddRow(){
          return this.field.canAdd === true && this.hasparentmodel !== false && (!this.getFilterBy || this.filterBy);
        },
        isStaticFilterColumn(){
          return this.getFilterBy && !(this.getFilterBy[0] in this.model.fields);
        },
        isParentFilterColumn(){
          return this.getFilterBy && this.getFilterBy[0].split('.').length > 1;
        },
        hasNoFilterValues(){
          //If foreign key identificator is not field, bud static foreign key column
          if ( this.isStaticFilterColumn )
            return false;

          return this.getFilterBy && (!this.filterBy || this.fieldOptions.length == 0);
        }
      },
  }
</script>