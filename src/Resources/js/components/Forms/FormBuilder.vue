<template>
  <!-- Horizontal Form -->
  <form method="post" action="" v-bind:id="'form-'+model.slug" v-on:submit.prevent="saveForm">
    <div v-bind:class="['box', { 'box-info' : isActive, 'box-warning' : !isActive }]">

      <div class="box-header with-border">
        <h3 class="box-title"><span v-if="model.localization" data-toggle="tooltip" :data-original-title="trans('multilanguages')" class="fa fa-globe"></span> {{ title }}</h3>
        <button v-if="isOpenedRow && canaddrow" v-on:click.prevent="resetForm" type="button" class="add-row-btn pull-right btn btn-default btn-sm"><i class="fa fa-plus"></i> {{ newRowTitle }}</button>
      </div>

      <div class="box-body" :class="{ cantadd : !cansave }">
        <form-tabs-builder
          :model="model"
          :childs="true"
          :langid="langid"
          :row="row"
          :cansave.sync="cansave"
          :hasparentmodel="hasparentmodel"
          :history="history">
        </form-tabs-builder>
      </div>

      <div class="box-footer" v-if="cansave">
        <button v-if="progress" type="button" name="submit" v-bind:class="['btn', 'btn-' + ( isOpenedRow ? 'success' : 'primary')]"><i class="fa updating fa-refresh"></i> {{ isOpenedRow ? trans('saving') : trans('sending') }}</button>
        <button v-if="!progress" type="submit" name="submit" v-bind:class="['btn', 'btn-' + ( isOpenedRow ? 'success' : 'primary')]">{{ isOpenedRow ? trans('save') : trans('send') }}</button>
      </div>

    </div>
  </form>
  <!-- /.box -->
</template>
<script>
  import FormTabsBuilder from '../Forms/FormTabsBuilder.vue';
  export default {
    name : 'form-builder',

    props : ['model', 'row', 'rows', 'langid', 'canaddrow', 'progress', 'history', 'hasparentmodel'],

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
      this.form = $('form#form-' + this.model.slug);

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
          if ( !row || !oldRow || row.id != oldRow.id || this.history.history_id ){
            this.initForm(row, canResetForm);
          }
        },
        deep: true,
      },
      //On change language reset editing form
      // langid(langid){
      //   this.row = {};
      // },
    },

    computed: {
      isOpenedRow(){
        return this.row && 'id' in this.row;
      },
      title(){
        var title;

        if ( this.isOpenedRow )
        {
          //Update title
          if ( title = this.$root.getModelProperty(this.model, 'settings.title.update') )
          {
            //Bind value from row to title
            for ( var key in this.row )
              title = title.replace(':'+key, this.row[key]);

            return title;
          }

          return this.trans('edit-row-n')+' ' + this.row.id;
        }

        //Insert title
        if ( title = this.$root.getModelProperty(this.model, 'settings.title.insert') )
          return title;

        return this.trans('new-row');
      },
      newRowTitle(){
        return this.$parent.newRowTitle();
      },
    },

    methods: {
      resetForm(){
        this.$parent.resetForm();
      },
      //Resets form values and errors
      initForm(row, reset){
        //Resets document values of elements
        //can be reseted just when ich changed row for other, or inserting new row
        if ( reset !== false )
        {
          this.form.resetForm();
        }

        this.resetErrors();

        if ( !row || !('id' in row) )
          this.row = {}

        //Checks if form can be editable
        if ( row && this.canaddrow && this.model.editable == false && this.$parent.hasChilds() == 0 )
        {
          this.row = {};
          return;
        }

        for ( var key in this.model.fields )
        {
          if ( ! row || this.model.fields[key].value != row[key] )
          {
            var value = row ? row[key] : null;

            //Set value and default value of field from database
            this.model.fields[key].value = value;
            this.$set('model.fields.'+key+'.$original_value', value);

            this.$broadcast('updateField', [key, this.model.fields[key]]);
          }
        }

        //Set box color
        if (!row || !('id' in row)) {
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
        this.removeActiveTab(this.form.find('.nav-tabs li.has-error').firstLevelForm(this.form[0]));
        this.progress = false;
      },
      sendForm(e, action, callback)
      {
        var _this = this;

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

        //Check if form belongs to other form
        if ( this.model.foreign_column != null )
        {
          data[this.model.foreign_column[this.$parent.getParentTableName()]] = this.$parent.parentrow.id;
        }

        //If is updating, then add row ID
        if ( action == 'update' )
        {
          data['_id'] = this.row.id;
          data['_method'] = 'put';
        } else {
          //Check if is enabled language
          if ( this.$root.language_id != null )
            data['language_id'] = this.$root.language_id;

          //Push saved childs without actual parent row
          if ( this.hasParentModel() && this.$parent.rows.save_children.length > 0 )
            data['_save_children'] = this.$parent.rows.save_children;
        }

        this.resetErrors();

        this.progress = true;

        var unknownError = function(){
          _this.$root.arrorAlert(function(){

            _this.progress = false;

            //Timeout for sending new request with enter
            setTimeout(function(){
              _this.submit = false;
            }, 500);

          });
        }

        $(e.target).ajaxSubmit({

          url : this.$root.requests[action],

          data : data,

          success(data){

            _this.submit = true;
            _this.progress = false;

            //Error occured
            if ( $.type(data) != 'object' || ! ('type' in data) )
            {
              unknownError();
              return;
            }

            //Fix for closing with enter
            setTimeout(function(){

              _this.$root.openAlert(data.title, data.message, data.type, null, function(){

                //Timeout for sending new request with enter
                setTimeout(function(){
                  _this.submit = false;
                }, 500);

              });


            }, 100);

            callback( data );
          },

          error(response){
            _this.resetErrors();

            // Wrong validation
            _this.$root.errorResponseLayer( response, 422, function(){

              var obj = response.responseJSON,
                  array = [];

              //Laravel 5.5 provides validation errors in errors object.
              if ( 'errors' in obj && !('length' in obj.errors) )
                obj = obj.errors;

              for ( var key in obj )
              {
                //One or multiple errors
                if ( typeof obj[key] == 'string' )
                    array = [ obj[key] ];
                else
                    array = obj[key];

                //Display errors
                for (var i = 0; i < array.length; i++){
                  for ( var a = 0; a <= 1; a++ )
                  {
                    var key = key.replace('.0', ''),
                        key = a == 0 ? key : key + '[]';

                    _this.form.find( 'input[name="'+key+'"], select[name="'+key+'"], textarea[name="'+key+'"]' ).firstLevelForm(_this.form[0]).each(function(){
                      var where = $(this);

                      //Colorize tabs
                      _this.colorizeTab($(this));

                      if ( $(this).is('select') ){
                        where = where.parent().parent().children().last().prev();
                      }

                      else if ( $(this).is('textarea') ){
                        where = where.parent().children().last().prev();
                      }

                      else if ( $(this).is('input:radio') ){
                        where = where.parent().parent().parent();

                        if ( where.find('.help-block').length == 0 )
                          where = where.children().last().prev();
                        else
                          where = null;
                      }

                      if ( where )
                        where.after( '<span class="help-block">'+array[i]+'</span>' );

                      //On first error
                      if ( i == 0 ){
                        var label = $(this).closest('div.form-group').addClass('has-error').find('> label');

                        if ( label.find('.fa-times-circle-o').length == 0 )
                          label.prepend('<i class="fa fa-times-circle-o"></i> ');
                      }
                    });
                  }
                }
              }
            })
          }
        });
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
            //Reset actual items buffer
            if ( this.hasParentModel() )
              this.saveParentChilds(response);

            //Bind values for input builder
            this.$broadcast('onSubmit', response.data.rows[0]);

            //Send notification about new row
            this.$dispatch('proxy', 'onCreate', [this.model.slug, response.data]);

            //If form has disabled autoreseting
            var autoreset = this.$root.getModelProperty(this.model, 'settings.autoreset');

            //Reseting form after new row
            if ( !(this.model.minimum == 1 && this.model.maximum == 1) && autoreset !== false)
            {
              this.initForm(null);

            //If is disabled autoreseting form, then select inserted row
            } else if ( autoreset === false ){
              this.row = response.data.rows[0];

              this.scrollToForm();
            }
          }

          //Update existing row
          else if ( action == 'update' ) {
            //Bind values for input builder
            this.$broadcast('onSubmit', response.data.row);

            //Send notification about updated row
            this.$dispatch('proxy', 'onUpdate', [this.model.slug, response.data.row]);

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
          var index = $(this).index();

          //On button click, remove tabs alerts in actual tree, if tab has no more recursive errors
          $(this).one('click', function(){
            if ( $(this).find('.nav-tabs-custom:not(.default) li.has-error').length == 0 )
              _this.removeActiveTab($(this).parent().prev().find('li').eq($(this).index()), true);
          });

          $(this).parent().prev().find('li').eq(index).each(function(){
            if ( ! $(this).hasClass('has-error') )
              $(this).attr('data-toggle', 'tooltip').attr('data-original-title', _this.trans('tab-error')).addClass('has-error').one('click', function(){

                var active = $(this).parent().find('li.has-error').not($(this)).length == 0 ? $(this).parents('.nav-tabs-custom').find('li.active.has-error') : [];

                _this.removeActiveTab($(this).extend(active), true);
              }).find('a').prepend('<i class="fa fa-exclamation-triangle"></i>');
          })
        });
      },
      scrollToForm(){
        setTimeout(function(){
          $('html, body').animate({
              scrollTop: $("#form-" + this.model.slug).offset().top - 10
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
      }
    },
  }
</script>