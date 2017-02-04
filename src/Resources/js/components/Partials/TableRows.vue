<template>
  <table v-bind:id="'table-'+model.slug" v-bind:class="['table', 'data-table', 'table-bordered', 'table-striped', { 'sortable' : model.sortable && orderby[0] == '_order' }]">
    <thead>
      <tr>
        <th v-for="(field, name) in columns" v-bind:class="'th-'+field" v-on:click="toggleSorting(field)">
          <i class="arrow-sorting fa fa-arrow-up" v-if="orderby[0] == field && orderby[1] == 0"></i>
          <i class="arrow-sorting fa fa-arrow-down" v-if="orderby[0] == field && orderby[1] == 1"></i>
          {{ name }}
        </th>
        <th class="th-options-buttons"></th>
      </tr>
    </thead>
    <tbody data-model="{{ model.slug }}">
      <tr v-for="(key, item) in rowsdata" data-index="{{ item.id }}" v-drag-and-drop drag-start="beforeUpdateOrder" drop="updateOrder">

        <td v-for="(field, name) in columns" v-bind:class="'td-'+field">
          <table-row-value :field="field" :name="name" :item="item" :model="model"></table-row-value>
        </td>

        <td class="buttons-options">
          <button type="button" v-if="model.editable" v-on:click="selectRow( item )" v-bind:class="['btn', 'btn-sm', {'btn-success' : isActiveRow(item), 'btn-default' : !isActiveRow(item) }]" data-toggle="tooltip" title="" data-original-title="Upraviť"><i class="fa fa-pencil"></i></button>
          <button type="button" v-on:click="showInfo( item )" class="btn btn-sm btn-default" data-toggle="tooltip" title="" data-original-title="Informácie o zázname"><i class="fa fa-info"></i></button>
          <button type="button" v-if="model.publishable" v-on:click="togglePublishedAt( item )" v-bind:class="['btn', 'btn-sm', { 'btn-info' : !item.published_at, 'btn-warning' : item.published_at}]" data-toggle="tooltip" title="" data-original-title="{{ item.published_at ? 'Skryť' : 'Zobraziť' }}"><i v-bind:class="{ 'fa' : true, 'fa-eye' : item.published_at, 'fa-eye-slash' : !item.published_at }"></i></button>
          <button type="button" v-if="model.deletable && count > model.minimum" v-on:click="removeRow( item, key )" class="btn btn-danger btn-sm" data-toggle="tooltip" title="" data-original-title="Vymazat"><i class="fa fa-remove"></i></button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
  import TableRowValue from './TableRowValue.vue';

  export default {
      props : ['row', 'rows', 'rowsdata', 'count', 'field', 'model', 'orderby', 'dragging'],

      components: { TableRowValue },

      data(){
        return {
          hidden: ['language_id', '_order', 'slug', 'published_at', 'updated_at', 'created_at'],
        };
      },

      created() {
        //If table has foreign column, will be hidden
        if ( this.model.foreign_column != null )
          this.hidden.push( this.model.foreign_column );
      },

      ready() {
        //Automaticaly choose size of tables
        this.$parent.$parent.checkActiveSize( this.columns );
      },

      computed: {
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
      },

      methods: {
        toggleSorting(key){
          var sortable = this.$root.getModelProperty(this.model, 'settings.sortable');

          //Disable sorting by columns
          if ( sortable === false )
            return;

          var order = this.orderby[0] == key ? (1 - this.orderby[1]) : 0;

          this.orderby = [key, order];
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
        isActiveRow(row){
          if ( !this.row )
            return false;

          if ( row.id == this.row.id )
            return true;

          return false;
        },
        findById(id){
          for ( var key in this.rowsdata )
            if ( this.rowsdata[key].id == id )
              return this.rowsdata[key];
        },
        beforeUpdateOrder(dragged){
          this.$parent.destroyTimeout();

          this.dragging = true;
        },
        enableDraggind(){
          this.$parent.initTimeout(false);
          this.dragging = false;
        },
        updateOrder(dragged, dropped){
          //Disable sorting when is used sorting columns
          if ( this.orderby[0] != '_order' )
          {
            this.enableDraggind();
            return;
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
            this.enableDraggind();
            return;
          }

          var dragged_id = $(dragged).attr('data-index'),
              dropped_id = $(dropped).attr('data-index'),
              dragged_order = this.findById(dragged_id)._order;

          this.findById(dragged_id)._order = this.findById(dropped_id)._order;
          this.findById(dropped_id)._order = dragged_order;

          this.$http.get(this.$root.requests.updateOrder, { model : this.model.slug, id : dragged_id, subid : dropped_id })
          .then(function(response){

            var data = response.data;

            if ( data && 'type' in data )
            {
              return this.$root.openAlert(data.title, data.message, 'danger');
            }

            this.enableDraggind();
          })
          .catch(function(response){
            this.$root.errorResponseLayer(response);

            this.enableDraggind();
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

          var render = function(response){
            for ( var key in response )
            {
              row[key] = response[key];
            }

            this.row = row;
          };

          if ( data ) {
            render(data);
          } else {
            this.$http.get(this.$root.requests.show, { model : this.model.slug, id : row.id })
            .then(function(response){
              render.call(this, response.data );
            })
            .catch(function(response){
              this.$root.errorResponseLayer(response);
            });
          }

        },
        removeRow(row){
          var success = function (){

            var data = {
              model : this.model.slug,
              id : row.id,
              subid : this.$parent.getParentRowId(),
              limit : this.$parent.pagination.limit,
              page : this.$parent.pagination.position,
              _method : 'delete',
            };

            //Check if is enabled language
            if ( this.$root.language_id != null )
              data['language_id'] = parseInt(this.$root.language_id);

            this.$http.post( this.$root.requests.delete, data)
            .then(function(response){
              var data = response.data;

              if ( data && 'type' in data && data.type == 'error' )
              {
                return this.$root.openAlert(data.title, data.message, 'danger');
              }

              //Load rows into array
              this.rows.data = data.data.rows.rows;
              this.rows.count = data.data.rows.count;
              this.$parent.pagination.position = data.data.rows.page;

              if ( this.row == row )
                this.row = null;
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
      },
  }
</script>