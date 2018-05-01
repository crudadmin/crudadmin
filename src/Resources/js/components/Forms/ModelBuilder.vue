<template>
  <!-- Additional top layouts -->
  <div v-for="layout in layouts | positionLayout 'top'">
    {{{ layout.view }}}
  </div>

  <div v-bind:class="[ 'box', { 'single-mode' : isSingle, 'box-warning' : isSingle } ]" v-show="canShowForm || (hasRows && canShowRows || isSearching)">


    <div class="box-header" v-bind:class="{ 'with-border' : isSingle }" v-show="ischild && (!model.in_tab || isEnabledGrid || canShowSearchBar) || ( !isSingle && (isEnabledGrid || canShowSearchBar))">
      <h3 v-if="ischild" class="box-title">{{ model.name }}</h3> <span class="model-info" v-if="model.title && ischild">{{{ model.title }}}</span>

      <div class="pull-right" v-if="!isSingle">
        <div class="search-bar" :class="{ interval : search.interval }" v-bind:id="getFilterId" v-show="canShowSearchBar">
          <div class="input-group input-group-sm">
            <div class="input-group-btn">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">{{ getSearchingColumnName(search.column) }}
                <span class="caret"></span></button>
                <ul class="dropdown-menu">
                  <li v-bind:class="{ active : !search.column }"><a href="#" @click.prevent="search.column = null">{{ trans('search-all') }}</a></li>
                  <li v-bind:class="{ active : search.column == 'id' }"><a href="#" @click.prevent="search.column = 'id'">{{ getSearchingColumnName('id') }}</a></li>
                  <li v-for="key in getSearchableFields" v-bind:class="{ active : search.column == key }"><a href="#" @click.prevent="search.column = key">{{ getSearchingColumnName(key) }}</a></li>
                  <li v-bind:class="{ active : search.column == 'created_at' }"><a href="#" @click.prevent="search.column = 'created_at'">{{ getSearchingColumnName('created_at') }}</a></li>
                </ul>
            </div>
            <!-- /btn-group -->

            <!-- Search columns -->
            <input type="text" v-show="isSearch" :placeholder="trans('search')+'...'" debounce="300" v-model="search.query" class="form-control">

            <input type="text" v-show="isDate" v-model="search.query" class="form-control js_date">

            <select type="text" v-show="isCheckbox" v-model="search.query" class="form-control">
              <option value="0">{{ trans('off') }}</option>
              <option value="1">{{ trans('on') }}</option>
            </select>

            <div class="select" v-show="isSelect">
              <select type="text" v-model="search.query" class="form-control js_chosen" :data-placeholder="trans('get-value')">
                <option value="">{{ trans('show-all') }}</option>
                <option v-for="data in (isSelect ? model.fields[search.column].options : []) | languageOptions model.fields[search.column]" v-bind:value="data[0]">{{ data[1] }}</option>
              </select>
            </div>
            <!-- Search columns -->

            <div class="interval" v-if="canBeInterval" data-toggle="tooltip" data-original-title="Interval">
              <button class="btn" @click="search.interval = !search.interval" :class="{ 'btn-default' : !search.interval, 'btn-primary' : search.interval }"><i class="fa fa-arrows-h"></i></button>
            </div>

            <input type="text" v-show="search.interval && isSearch" :placeholder="trans('search')+'...'" debounce="300" v-model="search.query_to" class="form-control">

            <input type="text" v-show="search.interval && isDate" v-model="search.query_to" class="form-control js_date">

            <div class="interval" v-if="search.query || search.query_to" data-toggle="tooltip" :data-original-title="trans('reset')">
              <button class="btn btn-default" @click="search.query = ''"><i class="fa fa-times"></i></button>
            </div>
          </div>
        </div>

        <ul class="pagination pagination-sm no-margin" v-if="isEnabledGrid" data-toggle="tooltip" :data-original-title="trans('edit-size')">
          <li v-for="size in sizes" v-bind:class="{ 'active' : size.active, 'disabled' : size.disabled }"><a href="#" @click.prevent="changeSize(size)" title="">{{ size.name }}</a></li>
        </ul>
      </div>
    </div>

    <div class="box-body">

      <div v-bind:class="{ 'row' : true, 'flex-table' : activeSize == 0 }">

        <!-- left column -->
        <div class="col col-form col-lg-{{ 12 - activeSize }} col-md-12 col-sm-12" v-show="canShowForm" v-if="activetab!==false">
          <form-builder
            :progress.sync="progress"
            :rows.sync="rows"
            :history="history"
            :model="model"
            :langid="selected_language_id ? selected_language_id : langid"
            :selectedlangid.sync="selected_language_id"
            :canaddrow="canAddRow"
            :hasparentmodel="hasparentmodel"
            :row.sync="row"
          ></form-builder>
        </div>
        <!--/.col (left) -->

        <!-- right column -->
        <div class="col col-rows col-lg-{{ 12 - ( 12 - activeSize ) }} col-md-12 col-sm-12" v-show="hasRows && canShowRows">
          <model-rows-builder
            :model.sync="model"
            :rows.sync="rows"
            :row.sync="row"
            :langid="selected_language_id ? selected_language_id : langid"
            :progress.sync="progress"
            :search="search"
            :iswithoutparent="isWithoutParentRow"
            :activetab="activetab"
            :history="history">
          </model-rows-builder>
        </div>
        <!--/.col (right) -->

      </div>

      <model-builder
        v-if="(isOpenedRow || child.without_parent == true) && child.in_tab !== true"
        v-for="child in model.childs"
        :hasparentmodel="hasparentmodel"
        :langid="langid"
        :ischild="true"
        :model="getModel(child)"
        :activetab="activetab"
        :parentrow="row">
      </model-builder>
    </div>
  </div>

  <!-- Additional bottom layouts -->
  <div v-for="layout in layouts | positionLayout 'bottom'">
    {{{ layout.view }}}
  </div>
</template>

<script>
  import FormBuilder from './FormBuilder.vue';
  import ModelRowsBuilder from './ModelRowsBuilder.vue';

  export default {
    props : ['model', 'langid', 'ischild', 'parentrow', 'activetab', 'hasparentmodel'],
    name : 'model-builder',
    data : function(){
      return {
        sizes : [
          { size : 8, key : 'small', name : 'Small', active : false, disabled : false },
          { size : 6, key : 'medium', name : 'Medium', active : false, disabled : false },
          { size : 4, key : 'big', name : 'Big', active : false, disabled : false },
          { size : 0, key : 'full', name : 'Full width', active : false, disabled : false },
        ],

        activeSize : null,

        row : this.emptyRowInstance(),

        /*
         * Search engine
         */
        search : {
          column : this.$root.getModelProperty(this.model, 'settings.search.column', null),
          query : null,
          query_to : null,
          used : false,
          interval : false,
        },

        /*
         * Loaded rows from db
         */
        rows : {
          data : [],
          buttons : {},
          count : 0,
          loaded : false,
          save_children : [],
        },

        /*
         * History for selected row
         */
        history : {
          history_id : null,
          id : null,
          rows : [],
          fields : [],
        },

        //Additional layouts/components for model
        layouts : [],
        components : [],

        language_id : null,
        selected_language_id : null,

        progress : false,

        depth_level : 0,
      };
    },

    created() {
      //For file paths
      this.root = this.$root.$http.$options.root;

      //If model builder model parent
      if ( [null, undefined].indexOf(this.hasparentmodel) > -1 )
        this.hasparentmodel = true;

      //Set deep level of models
      this.setDeepLevel();
    },

    ready() {
      this.checkIfCanShowLanguages();

      this.initSearchSelectboxes();

      this.resetSearchBar();

      this.updateParentChildData();
    },

    watch : {
      sizes : {
        deep: true,
        handler(data){
          this.activeSize = data.filter(function(row){

            if ( row.active == true )
            {
              var rows = this.getStorage();
              rows[ this.model.slug ] = row.size;
              localStorage.sizes = JSON.stringify( rows );
            }

            return row.active == true;
          }.bind(this))[0].size;

          this.activeSize;
        }
      },
      search : {
        deep : true,
        handler(search, oldsearch){
          //Update select
          this.reloadSearchBarSelect();
        },
      },
      activetab(value){
        if ( value === true )
          this.sendRowsData();
      },
      parentrow(row, oldrow){
        //When parent row has been changed, then load children rows
        if ( ! _.isEqual(row, oldrow) ){
          var children = null;

          //Get rows builder child
          for ( var i = 0; i < this.$children.length; i++ )
            if ( 'reloadRows' in this.$children[i] ){
              children = this.$children[i];
              break;
            }

          if ( children ){
            children.reloadRows();
          }
        }
      },
      layouts(layouts){
        var Vue = this;

        /*
         * Run all inline javascripts
         */
        for ( var key in layouts )
        {
          var layout = layouts[key];

          $('<div>'+layout.view+'</div>').find('script').each(function(){
            //Run external js
            if ( $(this).attr('src') ){
              var js = document.createElement('script');
                  js.src = $(this).attr('src');
                  js.type = 'text/javascript';

              $('body').append(js);
            }

            //Run inline javascripts
            else {
              try {
                var func = new Function($(this).html());

                func.call(Vue);
              } catch(e){
                console.error(e);
              }
            }
          });
        }
      }
    },

    filters: {
      /*
       * Returns correct values into multilangual select
       */
      languageOptions(array, field){
        return this.$root.languageOptions(array, field);
      },

      /*
       * Return layouts for correct position
       */
      positionLayout(array, position){
        return array.filter(function(row){
          return row.position == position;
        })
      }
    },

    events : {

      //Receive event and send into child components
      proxy(name, param){
        this.$broadcast(name, param);
      },

      //Send into all childs parent row data
      sendParentRow(){
        this.$broadcast('getParentRow', {
          model : this.model,
          slug : this.model.slug,
          row : this.row,
          rows : this.rows.data,
          count : this.rows.count,
          component : this,
        });

        return true;
      },

    },

    methods : {
      /*
       * Send into parent model all actual row and data changes
       */
      updateParentChildData(){
        this.$watch('rows.data', function(data){
          this.sendRowsData();
        });
      },
      sendRowsData(){
        this.$broadcast('rows-changed', {
            slug : this.model.slug,
            model : this.model,
            rows : this.rows.data,
            count : this.rows.count,
          }, true);
      },
      resetSearchBar(){
        //On change column reset input
        this.$watch('search.column', function(column, prevcolumn){

          //Reset searched value if previous column was select or option
          if ( prevcolumn && prevcolumn in this.model.fields && ['select', 'option'].indexOf(this.model.fields[prevcolumn].type) !== -1 )
            this.search.query = null;

          this.search.interval = false;

          this.reloadDatetimeSearch();
        });
      },
      setDeepLevel(){
        var parent = this.$parent,
            depth = 0;

        while(parent.$options.name != 'base-page-view')
        {
          if ( parent.$options.name == 'model-builder' )
            depth++;

          parent = parent.$parent;
        }

        this.depth_level = depth;
      },
      trans(key){
        return this.$root.trans(key);
      },
      initSearchSelectboxes(){
        window.js_date_event = document.createEvent('HTMLEvents');

        var dispached = false;

        js_date_event.initEvent('change', true, true);

        $('#'+this.getFilterId+' .js_chosen').chosen({disable_search_threshold: 5}).on('change', function(){
            if ( dispached == false )
            {
                dispached = true;
                this.dispatchEvent(js_date_event);
            } else {
                dispached = false;
            }
        });
      },
      reloadDatetimeSearch(){
        if ( ! this.isDate )
          return;

        var column = this.search.column;

        //Add datepickers
        $('#'+this.getFilterId+' .js_date').datetimepicker({
          lang: this.$root.locale,
          format: column == 'created_at' ? 'd.m.Y' : this.model.fields[column].date_format,
          timepicker: column == 'created_at' ? false : this.model.fields[column].type != 'date',
          datepicker: column == 'created_at' ? true : this.model.fields[column].type != 'time',
          scrollInput: false,
        });
      },
      getParentTableName(force){
        var row = this.$parent.row;

        //if is model loaded in field, and has parent row, then load model of that parent
        if ( this.hasparentmodel && typeof this.hasparentmodel == 'object' && 'slug' in this.hasparentmodel )
          return this.hasparentmodel.slug;

        if ( force !== true && ((!row || !( 'id' in row )) || this.hasparentmodel === false) )
          return 0;

        return this.$parent.model.slug;
      },
      /*
       * Returns if model has next childs
       */
      hasChilds(){
        var length = 0;

        for ( var key in this.model.childs )
          length++;

        return length;
      },
      getStorage(){
        return $.parseJSON(localStorage.sizes||'{}')||{};
      },
      enableOnlyFullScreen(){
          for ( var key in this.sizes )
          {
            if ( key != 3 )
            {
              this.sizes[key].disabled = true;
              this.sizes[key].active = false;
            }
          }

          return this.sizes[3].active = true;
      },
      checkActiveSize(columns){

        var data = this.getStorage(),
            defaultValue = this.$root.getModelProperty(this.model, 'settings.grid.default');

        //Full screen
        if ( ! this.canShowForm || this.isSingle )
        {
          return this.enableOnlyFullScreen();
        }

        //Select size from storage
        if ( this.model.slug in data)
        {
          for ( var key in this.sizes )
            if ( this.sizes[key].size == data[ this.model.slug ] )
            {
              return this.sizes[key].active = true;
            }
        } else if ( defaultValue !== null ){

          // If model has default grid property
          for ( var key in this.sizes )
            if ( this.sizes[key].size == defaultValue || this.sizes[key].key == defaultValue )
            {
              return this.sizes[key].active = true;
            }
        }

        /*
         * When is localStorage value empty, then automatic chose the best grid value
         */

        if ( columns.length >= 5 )
          this.sizes[2].disabled = true;

        if ( this.hasChilds() > 0 )
        {
          return this.sizes[3].active = true;
        }

        //Full screen
        if ( columns.length > 5 )
          return this.sizes[3].active = true;

        //Big
        if ( columns.length <= 1 )
          return this.sizes[2].active = true;

        //Small
        if ( columns.length == 5 )
          return this.sizes[0].active = true;

        //Small
        this.sizes[1].active = true;

      },
      changeSize(row){
        if ( row.disabled == true )
          return false;

        for ( var key in this.sizes )
          this.sizes[key].active = false;

        row.active = true;
      },

      saveLanguageFilter(rows){

        rows = rows.sort(function(a, b){
          return parseInt(b.id) - parseInt(a.id);
        });

        this.buffer.rows = rows;

        this.$broadcast('updateBufferRows');

        return rows;
      },
      checkIfCanShowLanguages(){
        var languages_active = false;

        for ( var i = 0; i < this.$parent.$children.length; i++ )
        {
          var parent = this.$parent.$children[i];

          while ( 'model' in parent ){
            if ( parent.model.localization == true )
            {
              languages_active = true;
            }
            parent = parent.$parent;
          }
        }

        //Show or hide languages menu
        this.$root.languages_active = languages_active ? true : false;
      },
      getSearchingColumnName(column){
        if ( column == 'id' )
          return this.$root.trans('number');

        if ( column == 'created_at' )
          return this.$root.trans('created-at');

        if ( ! column || !(column in this.model.fields) )
          return this.trans('search-all');

        var field = this.model.fields[column],
            name = field.name.length > 20 ? field.name.substr(0, 20) + '...' : field.name;

        return name;
      },
      reloadSearchBarSelect(){
        $('#'+this.getFilterId+' .js_chosen').trigger("chosen:updated");
      },
      /*
       * Close history rows
       */
      closeHistory(with_fields){
        this.history.id = null;
        this.history.rows = [];

        if ( ! with_fields )
        {
          this.history.fields = [];
          this.history.history_id = null;
        }
      },
      newRowTitle(){
        return this.$root.getModelProperty(this.model, 'settings.buttons.insert', this.trans('new-row'));
      },
      resetForm(){
        this.row = this.emptyRowInstance();
      },
      emptyRowInstance(){
        var row = {};

        if ( this.model.foreign_column != null && this.parentrow )
          row[this.model.foreign_column[this.getParentTableName()]] = this.parentrow.id;

        return row;
      },
      getModel(model){
        //if is recursive model
        if ( typeof model === 'string' ){
          return _.cloneDeep(this.model);
        }

        return model;
      },
    },

    computed: {
      canBeInterval(){
        var column = this.search.column;

        if ( ['created_at', 'id'].indexOf(column) > -1 )
          return true;

        return column in this.model.fields && (['integer', 'decimal', 'date', 'datetime', 'time'].indexOf(this.model.fields[column].type) > -1) ? true : false;
      },
      isOpenedRow(){
        return this.row && 'id' in this.row;
      },
      /*
       * Return if acutal model can be added without parent row, and if parent row is not selected
       */
      isWithoutParentRow(){
        return this.model.without_parent == true && this.parentrow && this.$parent.isOpenedRow !== true && this.hasparentmodel == true;
      },
      getFilterId(){
        return 'js_filter' + this.getModelKey;
      },
      getModelKey(){
        return this.model.slug + '-' + this.getParentTableName();
      },
      //Checks if is enabled grid system
      isEnabledGrid(){
        if ( this.$root.getModelProperty(this.model, 'settings.grid.enabled') === false || this.$root.getModelProperty(this.model, 'settings.grid.disabled') === true )
          return false;

        return true;
      },
      //Returns if is model in single row mode
      isSingle(){
        return this.model.minimum == 1 && this.model.maximum == 1;
      },
      canShowRows(){
        if ( this.isSingle ){
          this.row = this.rows.data[0];
          this.row;

          this.enableOnlyFullScreen();

          return false;
        }

        return true;
      },
      canAddRow(){
        //Disabled adding new rows
        if ( this.model.insertable == false )
          return false;

        //Unlimited allowed rows
        if ( this.model.maximum == 0 )
          return true;

        if ( this.model.maximum <= this.rows.count )
          return false;

        return true;
      },
      canShowForm(){
        if ( !this.isOpenedRow && !this.canAddRow || this.isOpenedRow && this.model.editable == false)
          return false;

        return true;

      },
      hasRows(){
        if ( this.rows.loaded == false && this.model.maximum != 1 )
          return true;

        return this.rows.data.length > 0;
      },
      /*
       * Show search if has been at least one time used, or if is not single row, or if is more then 10 rows
       */
      canShowSearchBar(){
        var searching = this.$root.getModelProperty(this.model, 'settings.search.enabled', null);

        //If is forced showing searchbar
        if ( searching === true )
          return true;
        else if ( searching === false )
          return false;

        return this.search.used === true || (this.model.maximum==0 || this.model.maximum > 10) && this.rows.count > 10;
      },
      getSearchableFields(){
        var keys = [];

        //Get searchable fields
        for ( var key in this.model.fields )
        {
          var field = this.model.fields[key];

          if ( 'belongToMany' in field || 'multiple' in field || ( 'removeFromForm' in field && 'hidden' in field ) || field.type == 'password' )
            continue;

          keys.push(key);
        }

        return keys;
      },

      /*
       * Search columns
       */
      isSearch(){
        return (this.isCheckbox || this.isDate || this.isSelect) ? false : true;
      },
      isCheckbox(){
        var column = this.search.column;

        return column && column in this.model.fields && this.model.fields[column].type == 'checkbox' ? true : false;
      },
      isDate(){
        var column = this.search.column;

        if ( column == 'created_at' )
          return true;

        return column && column in this.model.fields && (['date', 'datetime', 'time'].indexOf(this.model.fields[column].type) > -1) ? true : false;
      },
      isSelect(){
        var column = this.search.column;

        return column && column in this.model.fields && (['select', 'radio'].indexOf(this.model.fields[column].type) > -1) ? true : false;
      },
      isSearching(){
        return this.search.used == true;
      },
    },

    components : { FormBuilder, ModelRowsBuilder }
  }
</script>