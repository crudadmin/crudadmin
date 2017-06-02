<template>
  <!-- Horizontal Form -->
  <form method="post" action="" v-bind:id="'form-'+model.slug" v-on:submit.prevent="saveForm">
    <div v-bind:class="['box', { 'box-info' : isActive, 'box-warning' : !isActive }]">

      <div class="box-header with-border">
        <h3 class="box-title"><span v-if="model.localization" data-toggle="tooltip" data-original-title="Tento záznam je viacjazyčný" class="fa fa-globe"></span> {{ title }}</h3>
        <button v-if="row && canaddrow" v-on:click.prevent="row=null" type="button" class="pull-right btn btn-default btn-sm">{{ newRowTitle }}</button>
      </div>

      <div class="box-body">
        <div class="row" v-for="groups in chunkGroups">
          <div v-for="group_name in groups">
              <div v-bind:class="getGroupClass(group_name)">
                <h4 v-if="canShowGroupName(group_name)">{{ group_name }}</h4>
                <form-input-builder v-for="field_key in getGroup(group_name).fields" v-if="canShowField(model.fields[field_key])" :model="model" :row="row" :index="$index" :key="field_key" :field="model.fields[field_key]"></form-input-builder>
              </div>
          </div>
        </div>
      </div>

      <div class="box-footer">
          <button v-if="progress" type="button" name="submit" v-bind:class="['btn', 'btn-' + ( row ? 'success' : 'primary')]"><i class="fa updating fa-refresh"></i> {{ row ? 'Ukláda' : 'Odosiela' }} sa</button>
          <button v-if="!progress" type="submit" name="submit" v-bind:class="['btn', 'btn-' + ( row ? 'success' : 'primary')]">{{ row ? 'Uložiť' : 'Odoslať' }}</button>
      </div>

    </div>
  </form>
  <!-- /.box -->
</template>
<script>
  import FormInputBuilder from '../Forms/FormInputBuilder.vue';
  export default {

    props : ['model', 'row', 'rows', 'langid', 'canaddrow', 'progress'],

    data(){
      return {
        submit : false,
        isActive : true,
        form : null,
      };
    },

    ready()
    {
      //Initialize form
      this.form = $('form#form-' + this.model.slug);

      //Enable ckeditors
      this.form.find('.js_editor').ckEditors();

      //Reset form
      this.initForm(null);
    },


    watch: {
      //After click on edit button, push data into form values
      row : {
        handler : function (row, oldRow) {
          this.initForm(row);
        },
        deep: true,
      },
      //On change language reset editing form
      // langid(langid){
      //   this.row = null;
      // },
    },

    computed: {
      title(){
        var title;

        if ( this.row )
        {
          //Update title
          if ( title = this.$root.getModelProperty(this.model, 'settings.title.update') )
          {
            //Bind value from row to title
            for ( var key in this.row )
              title = title.replace(':'+key, this.row[key]);

            return title;
          }

          return 'Upraviť záznam č. ' + this.row.id;
        }

        //Insert title
        if ( title = this.$root.getModelProperty(this.model, 'settings.title.insert') )
          return title;

        return 'Nový záznam';
      },
      newRowTitle(){
        var title;

        if ( title = this.$root.getModelProperty(this.model, 'settings.buttons.insert') )
          return title;

        return 'nový záznam';
      },
      chunkGroups(){
        var groups = Object.keys(this.model.fields_groups),
            chunkSize = 2,
            data = [];

        for (var i=0; i<groups.length; i+=chunkSize)
            data.push(groups.slice(i,i+chunkSize));

        return data;
      },
    },

    methods: {
      //Return group class
      getGroupClass(group_name){
        if ( this.getGroup(group_name).width == 'half' )
          return 'col-md-6';

        return 'col-md-12';
      },
      //Return group by key
      getGroup(key){
        return this.model.fields_groups[key];
      },
      canShowGroupName(group_name){
        var group = this.getGroup(group_name);

        return !$.isNumeric(group_name) && group.type!='default';
      },
      canShowField(field){
        return !('removeFromForm' in field);
      },
      //Resets form values and errors
      initForm(row){
        this.form.resetForm();

        this.resetErrors();

        //Checks if form can be editable
        if ( row && this.canaddrow && this.model.editable == false && this.$parent.hasChilds() == 0 )
        {
          this.row = null;
          return;
        }

        for ( var key in this.model.fields )
        {
          this.model.fields[key].value = row ? row[key] : null;

          this.$broadcast('updateField', [key, this.model.fields[key]]);
        }

        //Set box color
        if (!row) {
          this.isActive = true;
        } else {
          this.isActive = row.published_at == null;
        }
      },
      resetErrors(){
        this.form.find('.form-group.has-error').removeClass('has-error').find('.help-block').remove();
        this.form.find('.fa.fa-times-circle-o').remove();
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
          data[this.model.foreign_column[this.$parent.getParentTableName()]] = this.$parent.$parent.row.id;
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

                    _this.form.find( 'input[name="'+key+'"], select[name="'+key+'"], textarea[name="'+key+'"]' ).each(function(){
                        var where = $(this);

                        if ( $(this).is('select') || $(this).is('textarea') ){
                          where = where.parent().children().last().prev();
                        }

                        else if ( $(this).is('input:radio') )
                        {
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
        var action = this.row == null ? 'store' : 'update';

        this.sendForm(e, action, function(response){
          if ( ! response.data )
            return false;

          //Push new row
          if ( action == 'store' )
          {
            //Bind values for input builder
            this.$broadcast('onSubmit', response.data.rows[0]);

            //Send notification about new row
            this.$dispatch('proxy', 'onCreate', response.data.rows);

            //If form has disabled autoreseting
            var autoreset = this.$root.getModelProperty(this.model, 'settings.autoreset');

            //Reseting form after new row
            if ( !(this.model.minimum == 1 && this.model.maximum == 1) && autoreset !== false)
            {
              this.initForm(null);

            //If is disabled autoreseting form, then select inserted row
            } else if ( autoreset === false ){
              this.row = response.data.rows[0];
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

        }.bind(this));

      },
    },

    components : { FormInputBuilder }
  }
</script>