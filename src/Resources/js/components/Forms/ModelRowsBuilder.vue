<template>
  <div class="box">
    <div class="box-header box-limit">
      <h3 class="box-title">{{ title }} <small>({{ rows.count }})</small></h3>

      <div class="form-group pull-right" v-if="isPaginationEnabled" :title="trans('rows-count')">
        <select @change="changeLimit" class="form-control" v-model="pagination.limit">
          <option v-for="count in pagination.limits">{{ count }}</option>
        </select>
      </div>

      <div class="dropdown fields-list">
        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
          {{ trans('rows-list') }}
          <span class="caret"></span>
        </button>
        <ul class="dropdown-menu menu-left dropdown-menu-right">
          <li @click="$event.stopPropagation()" v-for="(key, column) in enabled_columns" v-if="canShowColumn(column, key)">
            <label><input type="checkbox" v-model="column.enabled"> {{ columnName(key, column.name) }}</label>
          </li>
          <li role="separator" class="divider"></li>
          <li><a href="#" @click.prevent="enabled_columns = null">{{ trans('default') }}</a></li>
        </ul>
      </div>

      <div class="dropdown actions-list fields-list" v-if="checked.length > 0">
        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
          {{ trans('action') }}
          <span class="caret"></span>
        </button>
        <ul class="dropdown-menu menu-left dropdown-menu-right">
          <li v-if="model.deletable"><a @click.prevent="removeRow()"><i class="fa fa-remove"></i> {{ trans('delete') }}</a></li>
          <li v-if="model.publishable"><a @click.prevent="togglePublishedAt()"><i class="fa fa-eye"></i> {{ trans('publish-toggle') }}</a></li>
          <li role="separator" v-if="hasButtons" class="divider"></li>
          <li v-for="(button_key, button) in availableButtons"><a @click="buttonAction(button_key, button)"><i class="fa" :class="button.icon"></i> {{ button.name }}</a></li>
        </ul>
      </div>

      <component
        v-for="name in getComponents('table-header')"
        :model="model"
        :row="row"
        :rows="rows.data"
        :is="name">
      </component>
    </div>

    <div class="box-body box-table-body">
      <table-rows
        :model="model"
        :row.sync="row"
        :buttons="rows.buttons"
        :count="rows.count"
        :history="history"
        :gettext_editor.sync="gettext_editor"
        :rows="rows"
        :rowsdata.sync="rowsData"
        :enabledcolumns.sync="enabled_columns"
        :item="$row"
        :button_loading="button_loading"
        :checked.sync="checked"
        :dragging.sync="dragging"
        :orderby.sync="orderBy">
      </table-row>
    </div>

    <div class="box-footer" v-if="isPaginationEnabled && rows.count>pagination.limit || getComponents('table-footer').length > 0">
      <component
        v-for="name in getComponents('table-footer')"
        :model="model"
        :row="row"
        :rows="rows.data"
        :is="name">
      </component>

      <ul v-if="isPaginationEnabled && rows.count>pagination.limit" class="pagination pagination-sm no-margin pull-right">
        <li v-if="pagination.position>1"><a v-on:click.prevent="setPosition(pagination.position - 1)" href="#">«</a></li>
        <li v-bind:class="{ active : pagination.position == i + 1 }" v-if="showLimit(i)" v-for="i in Math.ceil(rows.count / pagination.limit)"><a href="#" @click.prevent="setPosition(i + 1)">{{ i + 1 }}</a></li>
        <li v-if="pagination.position<rows.count/pagination.limit"><a v-on:click.prevent="setPosition(pagination.position + 1)" href="#">»</a></li>
      </ul>
    </div>

    <refreshing v-if="pagination.refreshing"></refreshing>
  </div>
  <!-- /.box -->
</template>

<script>
  import Refreshing from '../Partials/Refreshing.vue';
  import TableRows from '../Partials/TableRows.vue';

  export default {
    props : ['model', 'row', 'rows', 'langid', 'progress', 'search', 'history', 'gettext_editor', 'iswithoutparent', 'activetab'],

    components : { Refreshing, TableRows },

    data : function(){
      //Load pagination limit from localStorage
      var limit = this.iswithoutparent ? 500 : ('limit' in localStorage ? localStorage.limit : this.$root.getModelProperty(this.model, 'settings.pagination.limit', 10));

      return {
        table : null,

        //Sorting
        pagination: {
          position: 1,
          limit : parseInt(limit),
          limits : [ 5, 10, 20, 30, 50, 100, 200, 500 ],
          refreshing : false,
          maxpages : 15,
        },

        searching : false,
        dragging : false,
        orderBy : null,

        refresh : {
          refreshing : true,
          count : 0,
          interval : this.getRefreshInterval(),
        },

        //Receive value from tablerows component
        checked : [],
        default_columns : [],
        enabled_columns : null,
        button_loading : false,
      };
    },

    created() {
      //For file paths
      this.root = this.$root.$http.options.root;

      //Set default order rows
      this.setOrder();

      //Refresh rows refreshInterval
      this.loadRows();
    },

    destroyed() {
      this.destroyTimeout();
    },

    events: {
      /*
       * When row is added, then push it into table
       */
      onCreate(array){
        if ( array[0] != this.model.slug )
          return;

        array = array[1];

        var pages = Math.ceil(this.rows.count / this.pagination.limit);

        //If last page is full, and need to add new page
        if ( this.isReversed(true) && this.rows.count > 0 && !this.$parent.isWithoutParentRow && pages == this.rows.count / this.pagination.limit ){
          this.setPosition( pages + 1, this.$parent.isWithoutParentRow ? true : null );
        }

        //If user is not on lage page, then change page into last, for see added rows
        else if ( this.isReversed(true) && this.pagination.position < pages && !this.$parent.isWithoutParentRow ){
          this.setPosition( pages );
        }

        //If row can be pushed without reloading rows into first or last page
        else if ( this.pagination.position == 1 || (this.isReversed(true) && this.pagination.position == pages || this.$parent.isWithoutParentRow) )
        {
          var rows = array.rows.concat( this.rows.data );

          if ( rows.length > this.pagination.limit )
            rows = rows.slice(0, this.pagination.limit);

          //Update buttons
          for ( var key in array.buttons )
            this.rows.buttons[key] = array.buttons[key];

          this.rows.data = rows;
          this.rows.count += array.rows.length;
        } else {
          this.loadRows();
        }
      },
      /*
       * When row is updated, then change data into table for changed rows
       */
      onUpdate(array){
        if ( array[0] != this.model.slug )
          return;

        //Update row in table rows
        var row = array[1];

        for ( var key in this.rows.data )
        {
          if ( this.rows.data[key].id == row.id )
          {
            for ( var k in row )
            {
              this.$parent.rows.data[key][k] = row[k];
            }
          }
        }

        //Reset history on update row
        this.$parent.closeHistory();
      },
    },

    watch: {
      progress(state){
        if ( state == true )
          this.destroyTimeout();
        else
          this.initTimeout(false);
      },
      langid(langid){
        if ( this.model.localization == true )
          this.setPosition(1);
      },
      activetab(value){
        if ( value == true )
          this.initTimeout(false);
      },
      search : {
        deep : true,
        handler : function(search){

          var query = search.query||search.query_to,
              was_searching = this.searching;

          this.searching = (query && (query.length >= 3 || (search.column && ((search.column in this.model.fields && ['select', 'option'].indexOf(this.model.fields[search.column].type) > -1) || $.isNumeric(query))))) ? true : false;

          this.search.used = true;

          //On first search query reset pagination
          if ( this.searching == true && was_searching == false ){
            this.setPosition(1, true);

          //If is normal searching, then search in every char, or if is turned searching from on to off state, then show normal rows
          } else if ( this.searching || ( this.searching == false && was_searching == true ) ) {
            this.loadRows(true);
          }
        },
      },
      //On change enabled columns, reload full table with newer data
      enabled_columns : {
        deep : true,
        handler : function(columns, old){
          if ( ! old || ! columns )
            return;

          this.loadRows(true);
        },
      }
    },

    computed: {
      availableButtons(){
        var buttons = {};

        for ( var row_key in this.rows.buttons )
          for ( var key in this.rows.buttons[row_key] )
            if ( ['action', 'both', 'multiple'].indexOf(this.rows.buttons[row_key][key].type) > -1 )
              buttons[key] = this.rows.buttons[row_key][key];

        return buttons;
      },
      hasButtons(){
        return Object.keys(this.availableButtons).length > 0;
      },
      title(){
        var title;

        if ( title = this.$root.getModelProperty(this.model, 'settings.title.rows') )
        {
          return title;
        }

        return this.trans('rows');
      },
      isPaginationEnabled(){
        return this.$root.getModelProperty(this.model, 'settings.pagination.enabled') !== false && !this.iswithoutparent;
      },
      rowsData(){
        var field = this.orderBy[0],
            is_numeric = this.isNumericValue( field ),
            is_date = this.isDateValue( field ),
            is_decoded = this.$root.getModelProperty(this.model, 'settings.columns.'+field+'.encode', true) !== true;

        //If is date field, then receive correct date format of this field
        if ( this.isDateValue( field ) && field in this.model.fields )
          var format = this.$root.fromPHPFormatToMoment(this.model.fields[field].date_format);

        return this.rows.data.slice(0).sort(function(a, b){
          //If is null value
          if ( ! a || ! b )
            return false;

          //Support for booleans
          if ( a[ field ] === true || a[ field ] === false )
            a[ field ] = a[ field ] === true ? 1 : 0;

          if ( b[ field ] === true || b[ field ] === false )
            b[ field ] = b[ field ] === true ? 1 : 0;

          a = this.getEncodedValue(a[ field ], is_decoded),
          b = this.getEncodedValue(b[ field ], is_decoded);

          //Sorting numbers
          if ( is_numeric )
          {
            if ( this.orderBy[1] == 1 )
              return b - a;

            return a - b;
          }

          else if ( is_date && format ){
            var c = moment(a, format),
                d = moment(b, format);

            if ( !c.isValid() || !d.isValid() )
              return 0;

            if ( this.orderBy[1] == 1 )
              return d - c;

            return c - d;
          }

          else {
            if ( this.orderBy[1] == 1 )
              return b.toLowerCase().localeCompare(a.toLowerCase(), 'sk');

            return a.toLowerCase().localeCompare(b.toLowerCase(), 'sk');
          }
        }.bind(this));
      },
      enabledColumnsList(){
        var allowed = [];

        for ( var key in this.enabled_columns||{} )
          if ( this.enabled_columns[key].enabled == true && this.default_columns.indexOf(key) == -1 )
            allowed.push(key);

        return allowed;
      }
    },

    methods: {
      getComponents(type){
        return this.$parent.getComponents(type);
      },
      getEncodedValue(value, is_decoded){
        return (is_decoded ? $('<div>'+value+'</div>').text() : value) + '';
      },
      columnName(key, name){
        return this.$parent.getSearchingColumnName(key);
      },
      canShowColumn(column, key){
        if ( ! column.name )
          return false;

        if ( key in this.model.fields && this.model.fields[key].type == 'password' )
          return false;

        return true;
      },
      trans(key){
        return this.$root.trans(key);
      },
      reloadRows(){
        this.row = this.$parent.emptyRowInstance();
        this.rows.data = [];
        this.rows.count = 0;
        this.rows.save_children = [];

        this.loadRows();

        return true;
      },
      changeLimit(value){
        localStorage.limit = this.pagination.limit;

        //Reset pagination to first page
        this.setPosition(1);
      },
      getParentRowId(){
        var row = this.$parent.parentrow;

        if ( !row || !( 'id' in row ) )
          return 0;

        return row.id;
      },
      loadRows(indicator){
        //On first time allow reload rows without parent, for field options...
        if ( (this.$parent.isWithoutParentRow || this.activetab === false) && indicator == false ){
          return false;
        }

        if ( indicator !== false )
          this.pagination.refreshing = true;

        // Remove last auto timeout
        this.destroyTimeout();

        var query = {
          model : this.model.slug,
          parent : this.$parent.getParentTableName(this.model.without_parent),
          subid : this.getParentRowId(),
          langid : this.model.localization === true ? this.langid : 0,
          limit : this.isPaginationEnabled ? this.pagination.limit : 0,
          page : this.pagination.position,
          count : this.refresh.count,
        };

        //If is enabled searching
        if ( this.searching == true ){
          query.query = this.search.query;

          if ( this.search.interval === true )
            query.query_to = this.search.query_to;

          query.column = this.search.column;
        }

        //Add additional columns
        if ( this.enabledColumnsList.length > 0 )
          query.enabled_columns = this.enabledColumnsList.join(';');

        //My error
        function customErrorAlert(response){
          var url = response.request.url;

          for ( var key in response.request.params )
            url = url.replace('{'+key+'}', response.request.params[key]);

          this.$root.openAlert(this.trans('warning'), 'Nastala nečakana chyba, skúste neskôr prosím.<br><br>Príčinu zlyhania požiadavky môžete zistiť na tejto adrese:<br> <a target="_blank" href="'+url+'">'+url+'</a>', 'error');
        }

        this.$http.get(this.$root.requests.rows, query).then(function(response){
          //If has been component destroyed, and request is delivered... and some conditions
          if ( this.dragging === true || this.progress === true || !this.$root ){
            return;
          }

          if ( typeof response.data == 'string' ){
            customErrorAlert.call(this, response);
            return;
          }

          //Disable loader
          this.pagination.refreshing = false;

          //Load rows into array
          this.updateRowsData(response.data.rows, this.enabledColumnsList.length == 0 ? null : 1);
          this.rows.count = response.data.count;

          //Bind additional buttons for rows
          this.rows.buttons = response.data.buttons;

          //Rows are successfully loaded
          this.$parent.rows.loaded = true;

          //If is reversed sorting in model, then set pagination into last page after first displaying table
          if ( this.isReversed() && this.refresh.count == 0 )
          {
            this.pagination.position = Math.ceil(this.rows.count / this.pagination.limit);
          }

          if ( this.refresh.count == 0 ){
            //Update field options
            this.updateFieldOptions(response.data.fields, response.data);

            //Render additional layouts
            this.$parent.layouts = response.data.layouts;
          }

          //Update refresh informations
          this.refresh.count++;
          this.refresh.refreshing = false;

          //Get new csrf token
          reloadCSRFToken(response.data.token);

          //Add next timeout
          this.initTimeout(false);
        })
        .catch(function(response){
          //If has been component destroyed, and request is delivered...
          if ( !this.$root )
            return;

          //Add next timeout
          this.initTimeout(false, true);

          //On first error
          if ( response.status == 500 && this.refresh.count == 0 && 'message' in response ){
            this.$root.errorResponseLayer(response, null);
          }

          //Show error alert at first request
          else if ( this.refresh.count == 0 && this.hasShowedError !== true || response.status == 401 ){
            this.hasShowedError = true;
            this.$root.errorResponseLayer(response, null);
          }
        });
      },
      destroyTimeout(){
        if ( this.updateTimeout )
          clearTimeout(this.updateTimeout);
      },
      initTimeout(indicator, force){
        this.destroyTimeout();

        var limit = this.isPaginationEnabled ? this.pagination.limit : 0;

        //Disable autorefreshing when is one row
        if ( (this.rows.count > 0 && this.model.maximum === 1 || this.rows.count > 50 && parseInt(limit) > 50) && force !== true )
          return;

        this.updateTimeout = setTimeout(function(){
          this.loadRows(indicator);
        }.bind(this), this.refresh.interval);
      },
      updateFieldOptions(fields, model){
          //Update fields from database, for dynamic selectbox values
          for ( var key in fields )
          {
            //Update filterBy for each model
            if ( 'filterBy' in (model.fields[key]||{}) && model.fields[key].filterBy ){
              this.$set('model.fields.'+key+'.filterBy', model.fields[key].filterBy);
            } else if ( 'filterBy' in this.model.fields[key]||{} ){
              delete this.model.fields[key].filterBy;
            }

            //Update options
            if (
              'options' in this.model.fields[key]
              && (
                typeof fields[key].options === 'string'
                || Object.keys(fields[key].options).length > 0
              )
            )
            {
              //Use options from different field
              if ( typeof fields[key].options === 'string' )
              {
                if ( fields[key].options.substr(0, 2) == '$.' )
                {
                  var from_key = fields[key].options.substr(2);

                  this.model.fields[key].options = fields[from_key].options;
                }
              }

              //Use own field options
              else {
                this.model.fields[key].options = fields[key].options;
              }

            }
          }

          //Update fields options in selectbar for choosenjs
          setTimeout(function(){
            if ( this.$parent && this.$parent.reloadSearchBarSelect )
              this.$parent.reloadSearchBarSelect();
          }.bind(this), 100);
      },
      isNumericValue(key){
        if ( ['id', '_order'].indexOf( key ) > -1)
          return true;

        if ( key in this.model.fields && ['integer', 'decimal', 'checkbox'].indexOf( this.model.fields[key].type ) > -1 )
          return true;

        if ( this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.type') == 'integer' )
          return true;

        return false;
      },
      isDateValue(key){
        if ( ['created_at', 'published_at', 'updated_at'].indexOf( key ) > -1)
          return true;

        if ( key in this.model.fields && ['date', 'datetime'].indexOf( this.model.fields[key].type ) > -1 )
          return true;

        return false;
      },
      /*
       * Sets default order after loading compoennt
       */
      setOrder(){
        //Set order by settings parameter
        if ( this.orderBy == null)
        {
          var orderBy = this.$root.getModelProperty(this.model, 'settings.orderBy');

          if ( orderBy )
          {
            var keys = Object.keys(orderBy);

            this.orderBy = [keys[0], parseFloat(orderBy[keys[0]].toLowerCase().replace('asc', 0).replace('desc', 1))];
            return;
          }
        }

        //Set order by field parameter
        for ( var key in this.model.fields )
        {
          var field = this.model.fields[key];

          if ( 'orderBy' in field )
          {
            var order = 1;

            this.orderBy = [key, field['orderBy'].toLowerCase().replace('asc', 0).replace('desc', 1)];
            return;
          }
        }

        //Add default order of rows
        this.orderBy = [this.model.orderBy[0], this.model.orderBy[1].toLowerCase().replace('asc', 0).replace('desc', 1)];
      },
      showLimit(i){
        var max = parseInt(this.rows.count / this.pagination.limit);

        if ( this.rows.count / this.pagination.limit === max )
          max--;

        //If is first or last page, then show it
        if ( i == 0 || i == max )
          return true;

        //Middle range
        var radius = 3,
            interval = [[100, 0.3], [100, 0.7], [1000, 0.1], [1000, 0.85]],
            in_middle_active = 0;

        for (var a = 0; a < interval.length; a++) {
          if ( max > interval[a][0] )
          {
            var level = parseInt(max * interval[a][1]);
            if ( i >= level && i <= level + radius )
              return true;

            in_middle_active++;
          }
        }

        var maxpages = this.pagination.maxpages - (in_middle_active * radius),
            maxpages = maxpages < 6 ? 6 : maxpages;

        var offset = this.pagination.position < (maxpages/2) ? (maxpages/2) - this.pagination.position : 0,
            offset = max - this.pagination.position < ( maxpages / 2 ) ? (maxpages/2) - (max - this.pagination.position) : offset;

        if ( this.pagination.position - offset >= i + (maxpages/2) || this.pagination.position <= i - (maxpages/2) - offset)
          return false;

        return true;
      },
      setPosition(position, indicator){
        this.pagination.position = position;

        //Load paginated rows...
        this.loadRows(indicator);
      },
      getRefreshInterval(){
        var interval = this.$root.getModelProperty(this.model, 'settings.refresh_interval', 10000);

        //Infinity interval
        if ( interval == false )
          interval = 3600 * 1000;

        return interval;
      },
      /*
       * Change updated rows in db
       */
      updateRowsData(data, update){
        //This update rows just in table, not in forms
        if ( update !== true && (this.rows.data.length != data.length || this.rows.data.length == 0 || this.rows.data[0].id != data[0].id || update === 1) )
        {
          this.rows.data = data;
          return;
        }

        //Update changed data in vue object
        for ( var i in this.rows.data )
        {
          for ( var k in data[i] )
          {
            var isArray = $.isArray(data[i][k]);

            //Compare also arrays
            if ( isArray && !_.isEqual(this.rows.data[i][k], data[i][k]) || !isArray )
            {
              this.rows.data[i][k] = data[i][k];
            }
          }
        }
      },
      removeRow(row)
      {
        var ids = row ? [ row.id ] : this.checked;

        var success = function (){
          var requestData = {
            model : this.model.slug,
            parent : this.$parent.getParentTableName(this.model.without_parent),
            id : ids,
            subid : this.getParentRowId(),
            limit : this.pagination.limit,
            page : this.pagination.position,
            _method : 'delete',
          };

          //Check if is enabled language
          if ( this.$root.language_id != null )
            requestData['language_id'] = parseInt(this.$root.language_id);

          this.$http.post( this.$root.requests.delete, requestData)
          .then(function(response){
            var data = response.data;

            if ( data && 'type' in data && data.type == 'error' )
            {
              return this.$root.openAlert(data.title, data.message, 'danger');
            }

            //Load rows into array
            if ( ! this.$parent.isWithoutParentRow ){
              this.updateRowsData(data.data.rows.rows);
              this.rows.count = data.data.rows.count;

              this.pagination.position = data.data.rows.page;
            } else {
              //Remove row
              var remove = [];
              for ( var key in this.rows.data )
                if ( ids.indexOf(this.rows.data[key].id) > -1 )
                  remove.push(key);

              //Remove deleted keys from rows objects. For correct working we need remove items from end to start
              for ( var i = 0; i < remove.sort((a, b) =>  (b - a)).length; i++ )
                this.rows.data.splice(remove[i], 1);
            }

            if ( this.row && ids.indexOf(this.row.id) > -1 )
              this.row = {};

            //Remove row from options
            if ( this.$parent.hasparentmodel !== true ){
              this.$parent.$parent.pushOption(requestData.id, 'delete');
            }

            //After remove reset checkbox
            if ( ! row )
              this.checked = [];
          })
          .catch(function(response){
            console.log(response);
            this.$root.errorResponseLayer(response);
          });
        }.bind(this);

        //Check if is row can be deleted
        if ( this.isReservedRow(ids) )
          return this.$root.openAlert(this.$root.trans('warning'), this.$root.trans(ids.length > 1 ? 'cannot-delete-multiple' : 'cannot-delete'), 'warning');

        this.$root.openAlert(this.$root.trans('warning'), this.$root.trans('delete-warning'), 'warning', success, true);
      },
      togglePublishedAt(row)
      {
        var ids = row ? [ row.id ] : this.checked;

        this.$http.post( this.$root.requests.togglePublishedAt, {
          model : this.model.slug,
          id : ids,
        })
        .then(function(response){
          var data = response.data;

          if ( data && 'type' in data )
            return this.$root.openAlert(data.title, data.message, 'danger');

          //Update multiple published at
          for ( var key in this.rows.data )
            if ( ids.indexOf(this.rows.data[key].id) > -1 )
              this.rows.data[key].published_at = data[this.rows.data[key].id];
        })
        .catch(function(response){
          this.$root.errorResponseLayer(response);
        });

        //Reset checkboxes after published
        if ( ! row )
          this.checked = [];
      },
      getButtonKey(id, key){
        return id + '-' + key;
      },
      buttonAction(key, button, row){
        var ids = row ? [ row.id ] : this.checked;
        var makeAction = function(ask, data){
          this.button_loading = row ? this.getButtonKey(row.id, key) : key;

          this.$http.post( this.$root.requests.buttonAction, _.merge(data||{}, {
            _button : {
              model : this.model.slug,
              parent : this.$parent.getParentTableName(),
              id : ids,
              multiple : row ? false : true,
              subid : this.getParentRowId(),
              limit : this.pagination.limit,
              page : this.pagination.position,
              language_id : this.model.localization === true ? this.langid : 0,
              button_id : key,
              ask : ask ? true : false,
            },
          })).then(function(response){
            this.button_loading = false;

            var data = response.data,
                hasData = 'data' in data,
                ask = hasData && data.data.ask == true,
                component = hasData && data.data.component ? data.data.component : null;

            //Load rows into array
            if ( 'data' in data && ! ask )
            {
              //Update received rows by button action
              if ( 'rows' in data.data )
                this.updateParentData(key, button, row, data);

              //Redirect on page
              if ( ('redirect' in data.data) && data.data.redirect )
                if ( data.data.open == true )
                  window.location.replace(data.data.redirect);
                else
                  window.open(data.data.redirect);

              //Uncheck all rows
              if ( ! row )
                this.checked = [];
            }

            //Alert message
            if ( data && 'type' in data )
            {
              var component_data = component ? {
                name : button.key,
                component : component,
                model : this.model,
                rows : this.rows.data.filter(item => ids.indexOf(item.id) > -1),
                row : row,
                request : {},
              } : null;

              var success_callback = function(){
                var data = {};

                if ( this.alert.component && this.alert.component.request )
                  data = _.clone(this.alert.component.request);

                makeAction(null, data);
              }

              return this.$root.openAlert(
                data.title,
                data.message,
                data.type,
                ask ? success_callback : null,
                ask ? true : null,
                component_data
              );
            }
          }).catch(function(response){
            this.button_loading = false;

            this.$root.errorResponseLayer(response);
          });
        }.bind(this);

        makeAction(true);
      },
      updateParentData(key, button, row, data){
        //Reload just one row which owns button
        if ( button.reloadAll == false ){

          for ( var k in data.data.rows.rows )
          {
            var row = data.data.rows.rows[k];

            if ( !(row.id in data.data.rows.buttons) ){
              this.rows.buttons[row.id] = [];
            } else {
              this.rows.buttons[row.id] = data.data.rows.buttons[row.id];
            }

            //Update just selected row
            for ( var i in this.rows.data )
            {
              if ( this.rows.data[i].id == row.id )
              {
                for ( var k in this.$parent.rows.data[i] )
                {
                  if ( this.$parent.rows.data[i][k] != row[k] )
                  {
                    this.$parent.rows.data[i][k] = row[k];
                  }
                }
              }
            }
          }
        }

        //Reload all rows
        else {
          this.updateRowsData(data.data.rows.rows, false);

          this.rows.count = data.data.rows.count;
          this.rows.buttons = data.data.rows.buttons;
        }
      },
      isReservedRow(id){
        //check multiple input
        if ( typeof id === 'object' && id.length && this.model.reserved )
        {
          for ( var i = 0; i < id.length; i++ )
          {
            if ( this.model.reserved.indexOf(id[i]) > -1 )
              return true;
          }

          return false;
        }

        //check one row
        if ( this.model.reserved && this.model.reserved.indexOf(id) > -1 )
          return true;

        return false;
      },
      /*
       * Return if model is in reversed mode
       * new rows will be added on the end
       */
      isReversed(except)
      {
        if ( except != true && ( !(2 in this.model.orderBy) || this.model.orderBy[2] != true ) )
          return false;

        return ['id', '_order'].indexOf(this.model.orderBy[0]) > -1 && this.model.orderBy[1].toLowerCase() == 'asc';
      }
    },
  }
</script>