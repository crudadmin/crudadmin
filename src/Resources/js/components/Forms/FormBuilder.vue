<template>
  <!-- Horizontal Form -->
  <form method="post" action="" v-bind:id="formID" :data-form="model.slug" v-on:submit.prevent="saveForm">
    <div v-bind:class="['box', { 'box-info' : isActive, 'box-warning' : !isActive }]">

      <div class="box-header with-border" :class="{ visible : (hasLocaleFields || canShowGettext || (isOpenedRow && model.history)) }">
        <h3 class="box-title"><span v-if="model.localization" data-toggle="tooltip" :data-original-title="trans('multilanguages')" class="fa fa-globe"></span> {{ title }}</h3>
        <button v-if="isOpenedRow && canShowGettext" @click="openGettextEditor()" type="button" class="add-row-btn pull-right btn btn-default btn-sm"><i class="fa fa-globe"></i> {{ trans('gettext-open') }}</button>
        <button v-if="isOpenedRow && canaddrow && !isSingle" @click.prevent="resetForm" type="button" class="add-row-btn pull-right btn btn-default btn-sm"><i class="fa fa-plus"></i> {{ newRowTitle }}</button>
        <button v-if="isOpenedRow && model.history && isSingle" type="button" @click="showHistory(row)" class="btn btn-sm btn-default" data-toggle="tooltip" title="" :data-original-title="trans('history.changes')"><i class="fa fa-history"></i> {{ trans('history.show') }}</button>

        <component
          v-for="name in getComponents('form-header')"
          :key="name"
          :model="model"
          :row="row"
          :rows="rows.data"
          :is="name">
        </component>

        <div class="dropdown pull-right multi-languages" v-if="hasLocaleFields && selectedLanguage">
          <button class="btn btn-default dropdown-toggle" type="button" id="languageDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <i class="fa fa-globe"></i> <span class="text">{{ getLangName(selectedLanguage) }}</span>
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu" aria-labelledby="languageDropdown">
            <li v-for="lang in languages" v-if="selectedLanguage.id != lang.id" :data-slug="lang.slug"><a href="#" @click.prevent="selectedlangid = lang.id"><i class="fa fa-exclamation-triangle"></i>{{ getLangName(lang) }}</a></li>
          </ul>
        </div>

      </div>

      <div class="box-body" :class="{ cantadd : !cansave }">
        <component
          v-for="name in getComponents('form-top')"
          :key="name"
          :model="model"
          :row="row"
          :rows="rows.data"
          :is="name">
        </component>

        <form-tabs-builder
          :model="model"
          :childs="true"
          :langid="langid"
          :inputlang="selectedLanguage"
          :row="row"
          :cansave.sync="cansave"
          :hasparentmodel="hasparentmodel"
          :history="history">
        </form-tabs-builder>

        <component
          v-for="name in getComponents('form-bottom')"
          :key="name"
          :model="model"
          :row="row"
          :rows="rows.data"
          :is="name">
        </component>
      </div>

      <div class="box-footer" v-if="canUpdateForm">
        <component
          v-for="name in getComponents('form-footer')"
          :key="name"
          :model="model"
          :row="row"
          :rows="rows.data"
          :is="name">
        </component>

        <button v-if="progress" type="button" name="submit" v-bind:class="['btn', 'btn-' + ( isOpenedRow ? 'success' : 'primary')]"><i class="fa updating fa-refresh"></i> {{ isOpenedRow ? trans('saving') : trans('sending') }}</button>
        <button v-if="!progress" type="submit" name="submit" v-bind:class="['btn', 'btn-' + ( isOpenedRow ? 'success' : 'primary')]">{{ isOpenedRow ? saveButton : sendButton }}</button>
      </div>

    </div>
  </form>
  <!-- /.box -->
</template>
<script>
  import FormTabsBuilder from '../Forms/FormTabsBuilder.vue';
  export default {
    name : 'form-builder',

    props : ['model', 'row', 'rows', 'langid', 'canaddrow', 'progress', 'history', 'hasparentmodel', 'selectedlangid', 'gettext_editor'],

    components: { FormTabsBuilder },

    data(){
      return {
        submit : false,
        isActive : true,
        cansave : true,
        form : null,
      };
    },

    ready()
    {
      //Initialize form
      this.form = $('#'+this.formID);

      //Reset form
      this.initForm(this.row);
    },

    watch: {
      //After click on edit button, push data into form values
      row : {
        handler : function (row, oldRow) {
          //Form cannot be resetted if data has been synced from db
          var canResetForm = !this.isOpenedRow || ! oldRow || row.id != oldRow.id;

          //Init new form after change row
          if ( !row || !oldRow || row.id != oldRow.id || this.history.history_id )
          {
            this.initForm(row, canResetForm);
            this.$dispatch('sendParentRow');
          }
        },
        deep: true,
      },
      //On change language reset editing form
      // langid(langid){
      //   this.$parent.resetForm();
      // },
    },

    computed: {
      formID(){
        return 'form-' + this.$parent.depth_level + '-' + this.model.slug;
      },
      isSingle(){
        return this.model.minimum == 1 && this.model.maximum == 1;
      },
      isOpenedRow(){
        return this.row && 'id' in this.row;
      },
      title(){
        var title;

        if ( this.isOpenedRow )
        {
          //If update title has not been set
          if ( !(title = this.$root.getModelProperty(this.model, 'settings.title.update')) )
            return this.trans('edit-row-n')+' ' + this.row.id;

          //Bind value from row to title
          for ( var key in this.row )
          {
            var value = this.row[key];

            if ( this.isFieldSelect(key) )
            {
              var values = this.$root.languageOptions(this.model.fields[key].options, key);

              for ( var i = 0; i < values.length; i++ )
                if ( values[i][0] == value )
                {
                  value = values[i][1];
                  break;
                }
            }

            title = title.replace(':'+key, value);
          }

          return title;
        }

        //Insert title
        else if ( title = this.$root.getModelProperty(this.model, 'settings.title.insert') )
          return title;

        return this.trans('new-row');
      },
      newRowTitle(){
        return this.$parent.newRowTitle();
      },
      saveButton(){
        return this.$root.getModelProperty(this.model, 'settings.buttons.update') || this.trans('save');
      },
      sendButton(){
        return this.$root.getModelProperty(this.model, 'settings.buttons.create') || this.trans('send');
      },
      hasLocaleFields(){
        for ( var key in this.model.fields )
          if ( this.model.fields[key].locale == true )
            return true;

        return false;
      },
      languages(){
        return this.$root.languages;
      },
      selectedLanguage(){
        if ( ! this.selectedlangid )
          return this.languages[0];

        for ( var key in this.languages )
          if ( this.languages[key].id == this.selectedlangid )
            return this.languages[key];

        return this.languages[0];
      },
      canUpdateForm(){
        if ( this.isOpenedRow && this.$root.getModelProperty(this.model, 'settings.editable') == false )
          return false;

        return this.cansave;
      },
      canShowGettext(){
        if ( this.model.slug == 'languages' && this.$root.gettext )
          return true;

        return false;
      },
    },

    methods: {
      getLangName(lang){
        return this.$root.getLangName(lang);
      },
      showHistory(row){
        this.$parent.showHistory(row);
      },
      getComponents(type){
        return this.$parent.getComponents(type);
      },
      resetForm(){
        this.$parent.resetForm();
      },
      //Resets form values and errors
      initForm(row, reset){
        var is_row = row && 'id' in row;

        //Resets document values of elements
        //can be reseted just when is changed row for other, or inserting new row
        if ( reset !== false )
        {
          this.form.resetForm();

          for ( var key in this.model.fields ){
            this.$set('model.fields.'+key+'.value', null);
          }
        }

        this.resetErrors();

        if ( ! is_row )
          this.$parent.resetForm();

        //Checks if form can be editable
        if ( is_row && this.canaddrow && this.model.editable == false && this.$parent.hasChilds() == 0 )
        {
          this.$parent.resetForm();
          return;
        }

        for ( var key in this.model.fields )
        {
          if ( ! is_row || this.model.fields[key].value != row[key] || reset )
          {
            //Value can not be undefined, because of model change events.
            //If value will be undefined, full rows table will be realoaded (bug)
            var value = is_row ? row[key] : null,
                value = value === undefined ? null : value;

            //Set value and default value of field from database
            this.model.fields[key].value = value;
            this.$set('model.fields.'+key+'.$original_value', value);

            this.$broadcast('updateField', [key, this.model.fields[key]]);
          }
        }

        //Set box color
        if ( !is_row ) {
          this.isActive = true;

          if ( this.hasParentModel() )
            this.$parent.closeHistory();
        } else {
          this.isActive = row.published_at == null;
        }
      },
      resetErrors(){
        this.form.find('.form-group.has-error').firstLevelForm(this.form[0]).removeClass('has-error').find('.help-block').remove();
        this.form.find('.fa.fa-times-circle-o').firstLevelForm(this.form[0]).remove();
        this.form.find('.multi-languages .has-error').firstLevelForm(this.form[0]).removeClass('has-error');
        this.removeActiveTab(this.form.find('.nav-tabs li.has-error').firstLevelForm(this.form[0]));
        this.progress = false;
      },
      sendForm(e, action, callback)
      {
        //Disable send already sent form
        if ( this.submit == true )
          return;

        //Resets ckeditor values for correct value
        if (typeof CKEDITOR!='undefined'){
            for (var key in CKEDITOR.instances){
                CKEDITOR.instances[key].updateElement();
            }
        }

        //Data for request
        var data = {
            _model : this.model.slug,
        };

        //Data at the end of request
        var additional_data = {};

        //Check if form belongs to other form
        if ( this.model.foreign_column != null && this.$parent.parentrow )
          data[this.model.foreign_column[this.$parent.getParentTableName()]] = this.$parent.parentrow.id;

        //If is updating, then add row ID
        if ( action == 'update' )
        {
          data['_id'] = this.row.id;
          data['_method'] = 'put';
        } else {
          //Check if is enabled language
          if ( this.langid )
            data['language_id'] = this.langid;

          //Push saved childs without actual parent row
          if ( this.hasParentModel() && this.$parent.rows.save_children.length > 0 )
            additional_data['_save_children'] = this.$parent.rows.save_children;
        }

        this.resetErrors();

        this.progress = true;

        $(e.target).ajaxSubmit({

          url : this.$root.requests[action],

          data : additional_data,

          //Add additional data into top of request, because of correct order in relations setters in laravel
          beforeSubmit(arr, $form, options) {
            for ( var key in data )
              arr.unshift({ name : key, value : data[key] });
          },

          success : data => {

            this.submit = true;
            this.progress = false;

            //Error occured
            if ( $.type(data) != 'object' || ! ('type' in data) )
              return this.unknownAjaxErrorResponse();

            //Fix for resubmiting form after closing with enter
            setTimeout(() => {

              this.$root.openAlert(data.title, data.message, data.type, null, () => {

                //Timeout for sending new request with enter
                setTimeout(() => {
                  this.submit = false;
                }, 500);

              });

            }, 100);

            callback( data );
          },

          error : response => {
            this.resetErrors();

            // Wrong validation
            this.$root.errorResponseLayer( response, 422, () => {
              var obj = response.responseJSON,
                  errors = [];

              //Laravel 5.5+ provides validation errors in errors object.
              if ( 'errors' in obj && !('length' in obj.errors) )
                obj = obj.errors;

              for ( var key in obj )
              {
                //One or multiple errors
                errors = typeof obj[key] == 'string' ? [ obj[key] ] : obj[key];

                //Display errors
                this.bindErrorMessages(key, errors);
              }
            })
          }
        });
      },
      unknownAjaxErrorResponse(){
        this.$root.arrorAlert(() => {

          this.progress = false;

          //Timeout for sending new request with enter
          setTimeout(() => {
            this.submit = false;
          }, 500);

        });
      },
      bindErrorMessages(key, errors){
        var keys = [],
            parts = key.split('.');

        //Add also multiple keys selectors
        if ( parts.length == 1 ){
          keys.push(key);
          parts.push(0);
        }

        parts = parts.map((item, i) => {
          if ( i == 0 )
            return item;

          return '['+item+']';
        });

        keys.push(parts.join(''));

        if ( parts[parts.length - 1] == '[0]' )
          keys.push(keys.slice(0, parts.length - 1).concat(['[]']).join(''))

        for ( var i = 0; i < errors.length; i++ )
        {
          _.uniqBy(keys).map(key => {
            this.form.find('input[name="'+key+'"], select[name="'+key+'"], textarea[name="'+key+'"]')
                     .firstLevelForm(this.form[0])
                     .each(this.showErrorMessage(errors[i], i));
          });
        }
      },
      showErrorMessage(message, i){
        var component = this;

        return function(){
          var where = $(this);

          //Colorize tabs
          component.colorizeTab($(this));

          component.colorizeLangDropdown($(this));

          if ( $(this).is('select') ){
            where = where.parent().parent().children().last().prev();
          }

          else if ( $(this).is('textarea') ){
            where = where.parent().children().last().prev();
          }

          else if ( $(this).is('input:radio') || $(this).parent().hasClass('label-wrapper') ){
            where = where.parent().parent().parent();

            if ( where.find('.help-block').length == 0 )
              where = where.children().last().prev();
            else
              where = null;
          }

          else if ( $(this).parent().hasClass('input-group') )
            where = $(this).parent();

          if ( where )
            where.after( '<span class="help-block">'+message+'</span>' );

          //On first error
          if ( i == 0 ){
            var label = $(this).closest('div.form-group').addClass('has-error').find('> label');

            if ( label.find('.fa-times-circle-o').length == 0 )
              label.prepend('<i class="fa fa-times-circle-o"></i> ');
          }
        };
      },
      buildEventData(row, request){
        return {
          table : this.model.slug,
          model : this.model,
          row : row,
          request : request
        };
      },
      saveForm(e)
      {
        //Devide if is updating or creating form
        var action = ! this.isOpenedRow ? 'store' : 'update';

        this.sendForm(e, action, function(response){
          if ( ! response.data )
            return false;

          //Push new row
          if ( action == 'store' )
          {
            var clonedRow = _.cloneDeep(response.data.rows[0]);

            //Reset actual items buffer
            if ( this.hasParentModel() )
              this.saveParentChilds(response);

            //Bind values for input builder
            this.$broadcast('onSubmit', this.buildEventData(clonedRow, response.data));

            //Send notification about new row
            this.$dispatch('proxy', 'onCreate', this.buildEventData(clonedRow, response.data));

            //If form has disabled autoreseting
            var autoreset = this.$root.getModelProperty(this.model, 'settings.autoreset');

            //Reseting form after new row
            if ( !(this.model.minimum == 1 && this.model.maximum == 1) && autoreset !== false)
            {
              this.initForm(this.$parent.emptyRowInstance());

            //If is disabled autoreseting form, then select inserted row
            } else if ( autoreset === false ){
              this.row = clonedRow;

              this.scrollToForm();
            }
          }

          //Update existing row
          else if ( action == 'update' ) {
            var clonedRow = _.cloneDeep(response.data.row);

            //Bind values for input builder
            this.$broadcast('onSubmit', this.buildEventData(clonedRow, response.data));

            //Send notification about updated row
            this.$dispatch('proxy', 'onUpdate', this.buildEventData(clonedRow, response.data));

            for ( var key in response.data.row )
            {
              this.row[key] = response.data.row[key];

              //Update values in fields cause updating files in form
              if ( key in this.model.fields )
              {
                this.model.fields[key].value = response.data.row[key];
              }
            }
          }

          //Add or update select options
          if ( this.hasparentmodel !== true )
            this.$parent.$parent.pushOption(action == 'store' ? response.data.rows[0] : response.data.row, action);
        }.bind(this));

      },
      removeActiveTab(tab, all){
        tab.filter(function(){
          return all === true || ! $(this).hasClass('model-tab');
        }).removeAttr('data-toggle').removeAttr('data-original-title').removeClass('has-error').tooltip("destroy").find('a > .fa.fa-exclamation-triangle').remove();
      },
      colorizeTab(input){
        var _this = this;

        input.parents('.tab-pane').each(function(){
          var getActiveTab = (panel) => {
            var li = panel.parent().prev().find('li'),
                id = panel.attr('id'),
                tab = id ? li.parent().find('> li > a[href="#'+id+'"]') : null;

            //Return tab by id, if those tabs are custom
            if ( tab )
              return tab.parent();

            return li.eq(panel.index());
          };

          //On button click, remove tabs alerts in actual tree, if tab has no more recursive errors
          $(this).one('click', function(){
            if ( $(this).find('.nav-tabs-custom:not(.default) li.has-error').length == 0 )
              _this.removeActiveTab(getActiveTab($(this)), true);
          });

          getActiveTab($(this)).each(function(){
            if ( ! $(this).hasClass('has-error') )
              $(this).attr('data-toggle', 'tooltip').attr('data-original-title', _this.trans('tab-error')).addClass('has-error').one('click', function(){

                var active = $(this).parent().find('li.has-error').not($(this)).length == 0 ? $(this).parents('.nav-tabs-custom').find('li.active.has-error') : [];

                _this.removeActiveTab($(this).extend(active), true);
              }).find('a').prepend('<i class="fa fa-exclamation-triangle"></i>');
          })
        });
      },
      colorizeLangDropdown(input){
        var field_wrapper = input.parents('.field-wrapper'),
            field_key = field_wrapper.attr('data-field'),
            field_lang = field_wrapper.attr('data-lang');

        if ( ! field_key )
          return;

        var field = this.model.fields[field_key];

        if ( field.locale != true || field_lang == this.selectedLanguage.slug )
          return;

        var dropdown = this.form.find('.multi-languages .dropdown-toggle');

        dropdown.addClass('has-error');
        dropdown.next().find('li[data-slug="'+field_lang+'"]').addClass('has-error');

        if ( field_lang == this.languages[0].slug )
          this.$root.openAlert(this.trans('warning'), this.trans('lang-error'), 'warning');
      },
      scrollToForm(){
        setTimeout(function(){
          $('html, body').animate({
              scrollTop: $('#'+this.formID).offset().top - 10
          }, 500);
        }.bind(this), 400);
      },
      hasParentModel(){
        return this.$parent.$options.name == 'model-builder';
      },
      saveParentChilds(response){
        this.$parent.rows.save_children = [];

        //If actual row has no parent, and need to ba saved when parent will be saved
        if ( this.$parent.isWithoutParentRow )
        {
          var parent = this.$parent.$parent;

          while(!('rows' in parent))
            parent = parent.$parent;

          for ( var i = 0; i < response.data.rows.length; i++ )
          {
            parent.rows.save_children.push({
              table : this.model.slug,
              id : response.data.rows[i].id,
              column : this.model.foreign_column[this.$parent.getParentTableName(true)],
            });
          }
        }
      },
      trans(key){
        return this.$root.trans(key);
      },
      openGettextEditor(){
        this.gettext_editor = this.row;
      },
      isFieldSelect(column){
        return column && column in this.model.fields && (['select', 'radio'].indexOf(this.model.fields[column].type) > -1) ? true : false;
      },
    },
  }
</script>