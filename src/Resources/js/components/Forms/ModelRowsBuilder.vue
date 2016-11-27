<template>
  <div class="box">
    <div class="box-header box-limit">
      <h3 class="box-title">Záznamy <small>({{ pagination_rows.length }})</small></h3>

      <div class="form-group pull-right" title="Zobraziť na stránke">
        <select class="form-control" v-model="limit">
          <option v-for="count in limits">{{ count }}</option>
        </select>
      </div>
    </div>

    <div class="box-body box-table-body">
      <table id="{{ model.slug }}-table" v-bind:class="['table', 'data-table', 'table-bordered', 'table-striped', { 'sortable' : model.sortable && this.orderby[0] == '_order' }]">
        <thead>
        <tr>
          <th v-for="field in columns" v-on:click="toggleSorting(field)">
            <i class="arrow-sorting fa fa-arrow-up" v-if="orderby[0] == field && orderby[1] == 0"></i>
            <i class="arrow-sorting fa fa-arrow-down" v-if="orderby[0] == field && orderby[1] == 1"></i>
            {{ fieldName(field) }}
          </th>
          <th></th>
        </tr>
        </thead>
        <tbody data-model="{{ model.slug }}">
        <tr v-for="(key, $row) in model.rows | languages | relationship | saveRows | sortable | limit" data-index="{{ $row.id }}" v-drag-and-drop drop="updateOrder">
          <td v-for="field in columns">
            <div v-if="fieldValue($row, field)">
              <file v-if="isFile(field)" :uploadpath="model.slug + '/' + field + '/' + fieldValue($row, field)"></file>
              <span v-else data-toggle="{{ fieldValue($row, field).length > 20 ? 'tooltip' : '' }}" data-original-title="{{ fieldValue($row, field) }}">{{ fieldValue($row, field) | stringLimit 20 }}</span>
            </div>
          </td>
          <td class="buttons-options">
            <button type="button" v-if="model.editable" v-on:click="selectRow( $row )" v-bind:class="{'btn' : true, 'btn-success' : isActiveRow($row), 'btn-default' : !isActiveRow($row), 'btn-sm' : true}" data-toggle="tooltip" title="" data-original-title="Upraviť"><i class="fa fa-pencil"></i></button>
            <button type="button" v-on:click="showInfo( $row )" class="btn btn-sm btn-default" data-toggle="tooltip" title="" data-original-title="Informácie o zázname"><i class="fa fa-info"></i></button>
            <button type="button" v-if="model.publishable" v-on:click="togglePublishedAt( $row )" v-bind:class="{ 'btn' : true, 'btn-info' : !$row.published_at, 'btn-warning' : $row.published_at, 'btn-sm' : true}" data-toggle="tooltip" title="" data-original-title="{{ $row.published_at ? 'Skryť' : 'Zobraziť' }}"><i v-bind:class="{ 'fa' : true, 'fa-eye' : $row.published_at, 'fa-eye-slash' : !$row.published_at }"></i></button>
            <button type="button" v-if="model.deletable && pagination_rows.length > model.minimum" v-on:click="removeRow( $row, key )" class="btn btn-danger btn-sm" data-toggle="tooltip" title="" data-original-title="Vymazat"><i class="fa fa-remove"></i></button>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <div class="box-header" v-if="pagination_rows.length>limit">
      <ul class="pagination pagination-sm no-margin pull-right">
        <li v-if="position>1"><a v-on:click.prevent="position--" href="#">«</a></li>
        <li v-bind:class="{ active : position == i + 1 }" v-if="showLimit(i)" v-for="i in pagination_rows.length / limit"><a href="#" v-on:click.prevent="position = (i + 1)">{{ i + 1 }}</a></li>
        <li v-if="position<pagination_rows.length/limit"><a v-on:click.prevent="position++" href="#">»</a></li>
      </ul>
    </div>
  </div>
  <!-- /.box -->
</template>

<script>
  import File from '../Partials/File.vue';

  export default {
    props : ['model', 'row'],

    data : function(){
      return {
        table : null,

        pagination_rows : {},

        //Sorting
        position: 1,
        limits : [ 5, 10, 20, 30, 50, 100, 200 ],
        limit : 10,
        maxpages : 15,

        orderby : ['id', 1],

        hidden: ['language_id', '_order', 'published_at', 'updated_at', 'created_at'],
      };
    },

    created() {
      //For file paths
      this.root = this.$root.$http.options.root;

      this.pagination_rows = this.model.rows;

      //If table has foreign column, will be hidden
      if ( this.model.foreign_column != null )
        this.hidden.push( this.model.foreign_column );

      this.setOrder();
    },

    ready() {
      //Load pagination limit from localStorage
      if ( 'limit' in localStorage )
        this.limit = localStorage.limit;

      //Automaticaly choose size of tables
      this.$parent.checkActiveSize( this.columns );
    },

    watch: {
      limit : function(value){
        localStorage.limit = value;
      },
    },

    events: {
      onCreate(params){
        //After create row, will selected added row
        this.selectRow(params[0], params[1], params[2]);
      }
    },

    filters : {
      sortable(array){
          return array.sort(function(a, b){
            //Sorting numbers
            if ( this.isNumericValue( this.orderby[0] ) )
            {
              if ( this.orderby[1] == 1 )
                return b[ this.orderby[0] ] - a[ this.orderby[0] ];

              return a[ this.orderby[0] ] - b[ this.orderby[0] ];
            } else {
              if ( this.orderby[1] == 1 )
                return b[ this.orderby[0] ].toLowerCase().localeCompare(a[ this.orderby[0] ].toLowerCase(), 'sk');

              return a[ this.orderby[0] ].toLowerCase().localeCompare(b[ this.orderby[0] ].toLowerCase(), 'sk');
            }
          }.bind(this));
      },
      limit(array) {
        return array.slice( (this.position * this.limit) - this.limit , this.position * this.limit);
      },
      stringLimit(string, length){

        if ( string.length > length )
          return string.substr(0, length) + '...';

        return string;
      },
      languages(array){
        return this.$parent.buffer.rows||[];
      },
      relationship(array){
        var data = array.filter(function(row){
          //If is not child model
          if ( this.model.foreign_column == null )
            return true;

          return row[this.model.foreign_column] == this.$parent.$parent.row.id;
        }.bind(this));

        //Remaining rows on actual page
        var remains = ( this.limit - ( (this.limit * this.position) - data.length ) );

        //If does not remaining rows on actual page after delete, or switching model, then goes to previous page
        if ( remains <= 0 && this.position > 1 )
          this.position--;

        return data;
      },
      saveRows(array){
        return this.pagination_rows = array;
      }
    },

    computed: {
      columns(){
        var data = [];

        for ( var i = 0; i < this.model.columns.length; i++ )
        {
          if ( this.hidden.indexOf( this.model.columns[i] ) > -1 )
            continue;

          data.push( this.model.columns[i] );
        }

        return data;
      },
    },

    methods: {
      updateOrder(dragged, dropped){

        //Disable sorting when is used sorting columns
        if ( this.orderby[0] != '_order' )
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
        .catch(function(){
          this.$root.arrorAlert();
        });
      },
      showInfo(row){
        var data = '';

        if ( row.created_at != null )
          data += 'Vytvorené dňa: <strong>' + this.$root.timeFormat( row.created_at ) + '</strong><br>';

        if ( row.updated_at != null )
          data += 'Posledná zmena: <strong>' + this.$root.timeFormat( row.updated_at ) + '</strong><br>';

        if ( row.published_at != null )
          data += 'Publikované dňa: <strong>' + this.$root.timeFormat( row.published_at ) + '</strong>';

        this.$root.openAlert('Informácie o zázname č. ' + row.id, data, 'primary', null, function(){});
      },
      selectRow(row, data, model){
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
          .catch(function(){
            this.arrorAlert();
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

            if ( this.row == row )
              this.row = null;
          })
          .catch(function(){
            this.$root.arrorAlert();
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
        .catch(function(){
          this.$root.arrorAlert();
        });
      },
      toggleSorting(key){
        var order = this.orderby[0] == key ? (1 - this.orderby[1]) : 0;

        this.orderby = [key, order];
      },
      isNumericValue(key){
        if ( ['id', '_order'].indexOf( key ) > -1)
          return true;

        if ( ['integer', 'decimal'].indexOf( this.model.fields[ key ].type ) > -1 )
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

            this.orderby = [key, order];
            return;
          }
        }

        //Add default sorting by order
        if ( this.model.sortable == 1 )
          this.orderby = ['_order', 1];
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

        var max = parseInt(this.pagination_rows.length / this.limit);

        //If is first or last page, then show it
        if ( i == 0 || i == max )
          return true;

        var offset = this.position < (this.maxpages/2) ? (this.maxpages/2) - this.position : 0,
            offset = max - this.position < ( this.maxpages / 2 ) ? (this.maxpages/2) - (max - this.position) : offset;

        if ( this.position - offset >= i + (this.maxpages/2) || this.position <= i - (this.maxpages/2) - offset)
          return false;

        return true;
      }
    },

    components : { File }
  }
</script>