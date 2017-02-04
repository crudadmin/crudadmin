<template>
  <div class="box">
    <div class="box-header box-limit">
      <h3 class="box-title">{{ title }} <small>({{ totalRowsCount }})</small></h3>

      <div class="form-group pull-right" title="Zobraziť na stránke">
        <select class="form-control" v-model="limit">
          <option v-for="count in limits">{{ count }}</option>
        </select>
      </div>
    </div>

    <div class="box-body box-table-body">
      <table v-bind:id="'table-'+model.slug" v-bind:class="['table', 'data-table', 'table-bordered', 'table-striped', { 'sortable' : model.sortable && this.orderBy[0] == '_order' }]">
        <thead>
        <tr>
          <th v-for="(field, name) in columns" v-bind:class="'th-'+field" v-on:click="toggleSorting(field)">
            <i class="arrow-sorting fa fa-arrow-up" v-if="orderBy[0] == field && orderBy[1] == 0"></i>
            <i class="arrow-sorting fa fa-arrow-down" v-if="orderBy[0] == field && orderBy[1] == 1"></i>
            {{ name }}
          </th>
          <th class="th-options-buttons"></th>
        </tr>
        </thead>
        <tbody data-model="{{ model.slug }}">
        <tr v-for="(key, $row) in paginatedRows | relationship | sortable" data-index="{{ $row.id }}" v-drag-and-drop drop="updateOrder">
          <td v-for="(field, name) in columns" v-bind:class="'td-'+field">
            <div v-if="fieldValue($row, field) != null">

              <!-- File -->
              <div v-if="isFile(field)" class="filesList">
                <div v-for="file in getFiles($row, field)">
                  <file :uploadpath="model.slug + '/' + field + '/' + file"></file> <span>, </span>
                </div>
              </div>

              <!-- Table value -->
              <span v-else data-toggle="{{ fieldValue($row, field).length > 20 ? 'tooltip' : '' }}" data-original-title="{{ fieldValue($row, field) }}">{{{ fieldValue($row, field) | stringLimit field | encodeValue field }}}</span>

            </div>
          </td>
          <td class="buttons-options">
            <button type="button" v-if="model.editable" v-on:click="selectRow( $row )" v-bind:class="{'btn' : true, 'btn-success' : isActiveRow($row), 'btn-default' : !isActiveRow($row), 'btn-sm' : true}" data-toggle="tooltip" title="" data-original-title="Upraviť"><i class="fa fa-pencil"></i></button>
            <button type="button" v-on:click="showInfo( $row )" class="btn btn-sm btn-default" data-toggle="tooltip" title="" data-original-title="Informácie o zázname"><i class="fa fa-info"></i></button>
            <button type="button" v-if="model.publishable" v-on:click="togglePublishedAt( $row )" v-bind:class="{ 'btn' : true, 'btn-info' : !$row.published_at, 'btn-warning' : $row.published_at, 'btn-sm' : true}" data-toggle="tooltip" title="" data-original-title="{{ $row.published_at ? 'Skryť' : 'Zobraziť' }}"><i v-bind:class="{ 'fa' : true, 'fa-eye' : $row.published_at, 'fa-eye-slash' : !$row.published_at }"></i></button>
            <button type="button" v-if="model.deletable && totalRowsCount > model.minimum" v-on:click="removeRow( $row, key )" class="btn btn-danger btn-sm" data-toggle="tooltip" title="" data-original-title="Vymazat"><i class="fa fa-remove"></i></button>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <div class="box-header" v-if="isPaginationEnabled && totalRowsCount>limit">
      <ul class="pagination pagination-sm no-margin pull-right">
        <li v-if="position>1"><a v-on:click.prevent="updatePosition(position - 1)" href="#">«</a></li>
        <li v-bind:class="{ active : position == i + 1 }" v-if="showLimit(i)" v-for="i in totalRowsCount / limit"><a href="#" @click.prevent="updatePosition(i + 1)">{{ i + 1 }}</a></li>
        <li v-if="position<totalRowsCount/limit"><a v-on:click.prevent="updatePosition(position + 1)" href="#">»</a></li>
      </ul>
    </div>

    <refreshing v-if="refreshing || paginationRefresh"></refreshing>
  </div>
  <!-- /.box -->
</template>

<script>
  import File from '../Partials/File.vue';
  import Refreshing from '../Partials/Refreshing.vue';

  export default {
    props : ['model', 'row', 'progress'],

    components : { File, Refreshing },

    data : function(){

      //Load pagination limit from localStorage
      var limit = 'limit' in localStorage ? localStorage.limit : 10;

      return {
        table : null,

        //Sorting
        chunks : [],
        paginationRefresh : false,
        oldPosition : 1,
        position: 1,
        limits : [ 5, 10, 20, 30, 50, 100, 200 ],
        limit : limit,
        maxpages : 15,


        orderBy : null,

        hidden: ['language_id', '_order', 'slug', 'published_at', 'updated_at', 'created_at'],

        refreshing : true,
        canTurnOffPaginateRefresh : false,
        refreshCount : 0,
        refreshInterval : 3000,
      };
    },

    created() {
      //For file paths
      this.root = this.$root.$http.options.root;

      //If table has foreign column, will be hidden
      if ( this.model.foreign_column != null )
        this.hidden.push( this.model.foreign_column );

      if ( this.orderBy == null)
      {
        var orderBy = this.$root.getModelProperty(this.model, 'settings.orderBy');
        if ( orderBy )
        {
          var keys = Object.keys(orderBy);

          orderBy = [keys[0], orderBy[keys[0]].toLowerCase().replace('asc', 0).replace('desc', 1)]
        } else {
          orderBy = ['id', 1];
        }

        this.orderBy = orderBy;
      }

      this.setOrder();

      //Refresh rows refreshInterval
      this.refreshRows();
    },

    ready() {
      //Automaticaly choose size of tables
      this.$parent.checkActiveSize( this.columns );
    },

    destroyed() {
      if ( this.updateTimeout )
        clearTimeout(this.updateTimeout);
    },

    watch: {
      limit : function(value){
        localStorage.limit = value;

        //Reset pagination to first page
        this.position = 1;
      },
      progress(state){
        if ( state == true && this.updateTimeout )
          clearTimeout(this.updateTimeout);
        else
          this.updateTimeout = setTimeout(function(){
            this.refreshRows.call(this);
          }.bind(this), this.refreshInterval);
      },
    },

    events: {
      onCreate(params){
        //After create row, will selected added row
        this.selectRow(params[0], params[1], params[2]);
      },
      updateBufferRows(){
        if ( this.paginationRefresh === false )
        {
          this.loadRows();
        } else if ( this.canTurnOffPaginateRefresh ){
          this.paginationRefresh = false;
        }
      }
    },

    filters : {
      sortable(array){
          return array.sort(function(a, b){
            //Sorting numbers
            if ( this.isNumericValue( this.orderBy[0] ) )
            {
              if ( this.orderBy[1] == 1 )
                return b[ this.orderBy[0] ] - a[ this.orderBy[0] ];

              return a[ this.orderBy[0] ] - b[ this.orderBy[0] ];
            } else {
              //If is null value
              if ( ! a[ this.orderBy[0] ] || ! b[ this.orderBy[0] ] )
                return false;

              if ( this.orderBy[1] == 1 )
                return b[ this.orderBy[0] ].toLowerCase().localeCompare(a[ this.orderBy[0] ].toLowerCase(), 'sk');

              return a[ this.orderBy[0] ].toLowerCase().localeCompare(b[ this.orderBy[0] ].toLowerCase(), 'sk');
            }
          }.bind(this));
      },
      stringLimit(string, key){
        if ( !( key in this.model.fields ) )
          return string;

        var field = this.model.fields[key],
            limit = 'limit' in field ? field.limit : 20;

        if ( limit != 0 && string.length > limit )
          return string.substr(0, limit) + '...';

        return string;
      },
      encodeValue(string, key){
        if ( !( key in this.model.fields ) )
          return string;

        var string = $(document.createElement('div')).text(string).html();

        if ( this.model.fields[key].type == 'text' && parseInt(this.model.fields[key].limit) === 0)
        {
          return string.replace(/\n/g, '<br>');
        }

        return string;
      },
      relationship(array){
        var data = array.filter(function(row){
          //If is not child model
          if ( this.model.foreign_column == null )
            return true;

          return row[this.model.foreign_column] == this.$parent.$parent.row.id;
        }.bind(this));

        return data;
      }
    },

    computed: {
      title(){
        var title;

        if ( title = this.$root.getModelProperty(this.model, 'settings.title.rows') )
        {
          return title;
        }

        return 'Záznamy';
      },
      paginatedRows(){
        var chunks = this.chunks;

        if ( !chunks || chunks.length == 0 )
          return [];

        var index = this.position - 1,
            stack = this.getChunkByIndex(chunks, index);

        //If requested page does not exists in chunks
        if ( stack === null || (stack !== null && stack.length != this.limit && this.tryDownloadAll != true) )
        {
          if ( stack && stack.length != this.limit )
            this.tryDownloadAll = true;

          this.paginationRefresh = true;
          this.canTurnOffPaginateRefresh = false;

          this.$http.get(this.$root.requests.paginate, { model : this.model.slug, id : this.model.count, paginate : this.limit, page : this.position })
          .then(function(response){
            this.chunks.push({ index : index, rows : response.data.rows, dynamic : true });

            this.canTurnOffPaginateRefresh = true;
          })
          .catch(function(response){
            this.paginationRefresh = false;

            this.$root.errorResponseLayer(response, null, function(){});
          });

          //Return last page while downloading chunk data
          stack = this.getChunkByIndex(chunks, this.oldPosition - 1);
        }

        return stack||[];
      },
      totalRowsCount(){
        return this.model.count;
      },
      getLastRowsId(){
        var lastid = 0;

        //Get last id
        for (var key in this.model.rows)
          if ( this.model.rows[key].id > lastid )
            lastid = this.model.rows[key].id;

        return lastid;
      },
      columns(){
        var data = {},
            key;

        //Get columns from row
        for ( var i = 0; i < this.model.columns.length; i++ )
        {
          key = this.model.columns[i];

          //If is column hidden
          if (this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.hidden'))
            continue;

          if ( this.hidden.indexOf( key ) == -1 && this.avaliableColumns.indexOf( key ) > -1 )
          {
            data[ this.model.columns[i] ] = this.fieldName( this.model.columns[i] );
          }
        }

        var columns = this.$root.getModelProperty(this.model, 'settings.columns');

        //Add before and after column values
        if ( columns )
        {
          for ( var i in columns )
          {
            var modifiedData = {};

            for ( var key in data )
            {
              //Add custom column before actual column
              if ( columns )
                for ( var k in columns )
                {
                  if ( 'before' in columns[k] && columns[k].before == key )
                    modifiedData[k] = columns[k].title;
                }

              modifiedData[key] = data[key];

              //Add custom column after actual column
              if ( columns )
                for ( var k in columns )
                {
                  if ( 'after' in columns[k] && columns[k].after == key )
                    modifiedData[k] = columns[k].title;
                }
            }

            data = modifiedData;
          }
        }

        return data;
      },
      avaliableColumns(){
        return ['id'].concat( Object.keys( this.model.fields ) );
      },
      isPaginationEnabled(){
        return this.$root.getModelProperty(this.model, 'settings.pagination') !== false;
      },
    },

    methods: {
      getChunkByIndex(chunks, index, full){
        var stack = null;

        for ( var i = chunks.length - 1; i >= 0; i-- )
        {
          if ( chunks[i].index === index )
          {
            stack = chunks[i].rows;

            if ( full === true )
              return chunks[i];

            break;
          }
        }

        return stack;
      },
      chunk: function(array, chunkSize) {
          return [].concat.apply([],
              array.map(function(elem,i) {
                  return i%chunkSize ? [] : [array.slice(i,i+chunkSize)];
              })
          );
      },
      loadRows(){
        var rows = this.$parent.buffer.rows||[];

        if ( ! this.isPaginationEnabled )
          return rows;

        this.chunks = [];

        var chunks = this.chunk(rows, parseInt(this.limit));

        for ( var i = 0; i < chunks.length; i++ )
          this.chunks.push({ index : i, rows : chunks[i]});
      },
      refreshRows(){
        var t = this;

        this.$http.get(this.$root.requests.refresh, { model : this.model.slug, id : this.model.lastid, subid : this.refreshCount })
        .then(function(response){

          // Auto timeout
          // this.updateTimeout = setTimeout(function(){
          //   this.refreshRows.call(this);
          // }.bind(this), this.refreshInterval);

          this.refreshCount++;
          this.refreshing = false;

          //Add new rows from database
          if ( response.data.rows.length > 0 )
          {
            for ( var key in response.data.rows )
            {
              this.model.rows.push(response.data.rows[key]);
            }
          }

          //Update fields from database, for dynamic selectbox values
          for ( var key in response.data.fields )
          {
            if ( 'options' in this.model.fields[ key ] && Object.keys(response.data.fields[ key ].options).length > 0 )
            {
              this.model.fields[ key ].options = response.data.fields[ key ].options;
            }
          }
        })
        .catch(function(response){
          //Clear auto update interval
          if ( this.updateTimeout )
            clearTimeout(this.updateTimeout);

          this.$root.errorResponseLayer(response, null, function(){});
        });
      },
      updateOrder(dragged, dropped){

        //Disable sorting when is used sorting columns
        if ( this.orderBy[0] != '_order' )
        {
          return;
        }

        var findById = function(_this, id){
          for ( var key in _this.model.rows )
            if ( _this.model.rows[key].id == id )
              return _this.model.rows[key];
        }

        //Get owner of dragged row
        while ( $(dropped).attr('data-index') == null )
        {
          dropped = $(dropped).parent()[0];
        }

        //Checks if dropped column is in same table
        if ( $(dragged).parent().attr('data-model') != $(dropped).parent().attr('data-model') )
        {
          this.$root.openAlert('Upozornenie', 'Medzi rozličnými tabuľkami nie je možné presúvať riadky.', 'warning', null, function(){});
          return;
        }

        var dragged_id = $(dragged).attr('data-index'),
            dropped_id = $(dropped).attr('data-index'),
            dragged_order = findById(this, dragged_id)._order;

        findById(this, dragged_id)._order = findById(this, dropped_id)._order;
        findById(this, dropped_id)._order = dragged_order;

        this.$root.$http.get(this.$root.requests.updateOrder, { model : this.model.slug, id : dragged_id, subid : dropped_id })
        .then(function(response){

          var data = response.data;

          if ( data && 'type' in data )
          {
            return this.$root.openAlert(data.title, data.message, 'danger');
          }

        })
        .catch(function(response){
          this.$root.errorResponseLayer(response);
        });
      },
      showInfo(row){
        var data = '';

        if ( row.created_at != null )
          data += 'Vytvorené dňa: <strong>' + this.$root.timeFormat( row.created_at ) + '</strong><br>';

        if ( row.updated_at != null && this.model.editable != false )
          data += 'Posledná zmena: <strong>' + this.$root.timeFormat( row.updated_at ) + '</strong><br>';

        if ( row.published_at != null )
          data += 'Publikované dňa: <strong>' + this.$root.timeFormat( row.published_at ) + '</strong>';

        this.$root.openAlert('Informácie o zázname č. ' + row.id, data, 'primary', null, function(){});
      },
      selectRow(row, data, model){
        //If is selected same row
        if ( this.row && this.row.id == row.id )
        {
          return;
        }

        //Recieve just messages between form and rows in one model component
        if (model && this.model.slug != model)
        {
          return;
        }

        //Resets form
        if ( row === true && data === null )
        {
          return this.row = null;
        }

        var _this = this,
            render = function(response){
              for ( var key in response )
              {
                row[key] = response[key];
              }

              _this.row = row;
            };

        if ( data ) {
          render(data);
        } else {
          this.$root.$http.get(this.$root.requests.show, { model : this.model.slug, id : row.id })
          .then(function(response){
            render( response.data );
          })
          .catch(function(response){
            this.$root.errorResponseLayer(response);
          });
        }

      },
      isActiveRow(row){
        if ( !this.row )
          return false;

        if ( row.id == this.row.id )
          return true;

        return false;
      },
      fieldName(key){
        if ( key in this.model.fields )
          return this.model.fields[key].name;
        else {
          switch( key )
          {
            case 'id':
              return 'Č.';
              break;
            case 'created_at':
              return 'Vytvorené';
              break;
            default:
              return key;
              break;
          }
        }
      },
      fieldValue(row, field)
      {
        //Get select original value
        if ( ( field in this.model.fields ) )
        {
          if ( this.model.fields[field].type == 'select' )
          {
            if ( 'multiple' in this.model.fields[field] && this.model.fields[field].multiple == true && $.isArray(row[field]))
            {
              var values = [],
                  rows = row[field];

              for ( var i = 0; i < rows.length; i++ )
              {
                if ( rows[i] in this.languageOptions( this.model.fields[field].options ) )
                  values.push( this.languageOptions( this.model.fields[field].options )[ rows[i] ] );
              }

              return values.join(', ');
            } else {
              if ( row[field] in this.languageOptions( this.model.fields[field].options ) )
                return this.languageOptions( this.model.fields[field].options )[ row[field] ];
            }
          }

          if ( this.model.fields[field].type == 'date' && row[field] )
          {
            return this.$parent.dateValue(row[field], this.model.fields[field]);
          }

          if ( this.model.fields[field].type == 'checkbox' )
          {
            return row[field] == 1 ? 'Áno' : 'Nie';
          }
        }

        return row[field];
      },
      isFile(field){

        if ( !(field in this.model.fields) )
          return false;

        if ( this.model.fields[field].type == 'file' )
          return true;

        return false;

      },
      removeRow(row){
        var success = function (){

          var data = {
            model : this.model.slug,
            id : row.id,
            _method : 'delete',
          };

          //Check if is enabled language
          if ( this.$root.language_id != null )
            data['language_id'] = this.$root.language_id;

          this.$http.post( this.$root.requests.delete, data)
          .then(function(response){
            var data = response.data;

            if ( data && 'type' in data )
            {
              return this.$root.openAlert(data.title, data.message, 'danger');
            }

            this.model.rows.$remove( row );

            this.model.count--;

            if ( this.row == row )
              this.row = null;

            var chunk = this.getChunkByIndex(this.chunks, this.position - 1, true);

            if ( chunk.dynamic === true )
            {
              chunk.rows.$remove( row );
              this.loadRows();
            }
          })
          .catch(function(response){
            this.$root.errorResponseLayer(response);
          });
        }.bind(this);

        this.$root.openAlert('Upozornenie', 'Naozaj chcete vymazať dany záznam?', 'warning', success, true);
      },
      togglePublishedAt(row){
        var _this = this;

        this.$root.$http.post( this.$root.requests.togglePublishedAt, {
          model : this.model.slug,
          id : row.id,
        })
        .then(function(response){

          var data = response.data;

          if ( data && 'type' in data )
          {
            return this.$root.openAlert(data.title, data.message, 'danger');
          }

          row.published_at = data.published_at;
        })
        .catch(function(response){
          this.$root.errorResponseLayer(response);
        });
      },
      toggleSorting(key){
        var sortable = this.$root.getModelProperty(this.model, 'settings.sortable');

        //Disable sorting by columns
        if ( sortable === false )
          return;

        var order = this.orderBy[0] == key ? (1 - this.orderBy[1]) : 0;

        this.orderBy = [key, order];
      },
      isNumericValue(key){
        if ( ['id', '_order'].indexOf( key ) > -1)
          return true;

        if ( ['integer', 'decimal', 'checkbox'].indexOf( this.model.fields[ key ].type ) > -1 )
          return true;

        return false;
      },
      setOrder(){
        for ( var key in this.model.fields )
        {
          var field = this.model.fields[key];

          if ( 'orderBy' in field )
          {
            var order = 1;

            if ( field['orderBy'] == 'desc' || parseInt(field['orderBy']) == 1 )
              order = 1;
            else
              order = 0;

            this.orderBy = [key, order];
            return;
          }
        }

        //Add default sorting by order
        if ( this.model.sortable == 1 )
          this.orderBy = ['_order', 1];
      },
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
      },
      showLimit(i){
        var max = parseInt(this.totalRowsCount / this.limit);

        //If is first or last page, then show it
        if ( i == 0 || i == max )
          return true;

        var offset = this.position < (this.maxpages/2) ? (this.maxpages/2) - this.position : 0,
            offset = max - this.position < ( this.maxpages / 2 ) ? (this.maxpages/2) - (max - this.position) : offset;

        if ( this.position - offset >= i + (this.maxpages/2) || this.position <= i - (this.maxpages/2) - offset)
          return false;

        return true;
      },
      getFiles(row, field){
        var value = this.fieldValue(row, field);

        if ( ! value )
          return [];

        if ( $.isArray(value) )
          return value;

        return [ value ];
      },
      updatePosition(position){
        this.oldPosition = this.position;

        this.position = position;
      }
    },
  }
</script>