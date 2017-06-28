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

        <td v-for="(field, name) in columns" v-bind:class="['td-'+field, { image_field : isImageField(field) } ]">
          <table-row-value :field="field" :name="name" :item="item" :model="model" :image="isImageField(field)"></table-row-value>
        </td>

        <td class="buttons-options">
          <button type="button" v-if="isEditable" v-on:click="selectRow(item)" v-bind:class="['btn', 'btn-sm', {'btn-success' : isActiveRow(item), 'btn-default' : !isActiveRow(item) }]" data-toggle="tooltip" title="" data-original-title="Upraviť"><i class="fa fa-pencil"></i></button>
          <button type="button" v-on:click="showInfo(item)" class="btn btn-sm btn-default" data-toggle="tooltip" title="" data-original-title="Informácie o zázname"><i class="fa fa-info"></i></button>

          <button type="button" v-for="(button_key, button) in getButtonsForRow(item)" v-on:click="buttonAction(button_key, button, item)" v-bind:class="['btn', 'btn-sm', button.class]" data-toggle="tooltip" title="" v-bind:data-original-title="button.name"><i v-bind:class="['fa', button.icon]"></i></button>

          <button type="button" v-if="model.publishable" v-on:click="togglePublishedAt(item)" v-bind:class="['btn', 'btn-sm', { 'btn-info' : !item.published_at, 'btn-warning' : item.published_at}]" data-toggle="tooltip" title="" data-original-title="{{ item.published_at ? 'Skryť' : 'Zobraziť' }}"><i v-bind:class="{ 'fa' : true, 'fa-eye' : item.published_at, 'fa-eye-slash' : !item.published_at }"></i></button>
          <button type="button" v-if="model.deletable && count > model.minimum" v-on:click="removeRow( item, key )" class="btn btn-danger btn-sm" data-toggle="tooltip" title="" data-original-title="Vymazat"><i class="fa fa-remove"></i></button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
  import TableRowValue from './TableRowValue.vue';

  export default {
      props : ['row', 'rows', 'rowsdata', 'buttons', 'count', 'field', 'model', 'orderby', 'dragging'],

      components: { TableRowValue },

      data(){
        return {
          hidden: ['language_id', '_order', 'slug', 'published_at', 'updated_at', 'created_at'],
          autoSize : false,
        };
      },

      created() {
        //If table has foreign column, will be hidden
        if ( this.model.foreign_column != null )
          this.hidden.push( this.model.foreign_column );

        //Automatically set columns
        if ( this.autoSize == false )
        {
          //Automaticaly choose size of tables
          this.$parent.$parent.checkActiveSize( this.columns );
        }
      },

      ready() {

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
                for ( var k in columns )
                  modifiedData = this.addColumn(modifiedData, k, key, 'before', columns);

                modifiedData[key] = data[key];

                //Add custom column after actual column
                for ( var k in columns )
                  modifiedData = this.addColumn(modifiedData, k, key, 'after', columns);
              }

              data = modifiedData;
            }

            for ( var key in columns )
            {
              if ( !(key in data) && columns[key].hidden != true )
              {
                var field_key = this.getColumnRightKey(key);

                data[key] = columns[key].name||columns[key].title||this.model.fields[field_key].name;
              }
            }
          }

          return data;
        },
        avaliableColumns(){
          return ['id'].concat( Object.keys( this.model.fields ) );
        },
        isEditable(){
          return this.model.editable || this.$parent.$parent.hasChilds() > 0;
        },
      },

      methods: {
        getButtonsForRow(item){
          if ( ! this.rows.buttons )
            return [];

          if ( item.id in this.rows.buttons )
          {
            return this.rows.buttons[item.id];
          }
        },
        buttonAction(key, button, row){
          var _this = this;

          this.$http.post( this.$root.requests.buttonAction, {
              model : this.model.slug,
              parent : this.$parent.$parent.getParentTableName(),
              id : row.id,
              subid : this.$parent.getParentRowId(),
              limit : this.$parent.pagination.limit,
              page : this.$parent.pagination.position,
              language_id : this.model.localization === true ? this.$parent.langid : 0,
              button_id : key,
          }).then(function(response){
            var data = response.data;

            //Load rows into array
            if ( 'data' in data )
            {
              if ( 'rows' in data.data )
              {
                this.$parent.updateRowsData(data.data.rows.rows, true);

                //Reload just one row which owns button
                if ( button.reloadAll == false ){
                  if ( !(row.id in data.data.rows.buttons) )
                  {
                    this.rows.buttons[row.id] = [];
                  } else {
                    this.rows.buttons[row.id] = data.data.rows.buttons[row.id];
                  }
                }

                //Reload all rows
                else {
                  this.rows.count = data.data.rows.count;
                  this.rows.buttons = data.data.rows.buttons;
                }

              }

              //Redirect on page
              if ( ('redirect' in data.data) && data.data.redirect )
                window.location.replace(data.data.redirect);
            }

            if ( data && 'type' in data )
            {
              return this.$root.openAlert(data.title, data.message, data.type);
            }
          }).catch(function(response){
            console.log(response);
            this.$root.errorResponseLayer(response);
          });
        },
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
            this.enableDraggind();
            return;
          }

          var dragged_id = $(dragged).attr('data-index'),
              dropped_id = $(dropped).attr('data-index'),
              dragged_order = this.findById(dragged_id)._order,
              dropped_order = this.findById(dropped_id)._order,
              rows = {},
              changed_ids = [];

          //Sort all rows between sorted rows
          for ( var i = this.$parent.$parent.rows.data.length - 1; i >= 0; i-- )
          {
            var row = this.$parent.$parent.rows.data[i];

            //From top to bottom
            if ( row.id == dragged_id ){
              row._order = dropped_order;
              rows[ row.id ] = row._order;
            } else if ( dragged_order > dropped_order && row._order >= dropped_order && row._order <= dragged_order ){
              row._order += 1;
              rows[ row.id ] = row._order;
            //From bottom to top
            } else if ( dragged_order < dropped_order && row._order <= dropped_order && row._order > dragged_order) {
              row._order -= 1;
              rows[ row.id ] = row._order;
            }
          }

          this.$http.post(this.$root.requests.updateOrder, { model : this.model.slug, rows : rows })
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
              parent : this.$parent.$parent.getParentTableName(),
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
              this.$parent.updateRowsData(data.data.rows.rows);
              this.rows.count = data.data.rows.count;

              this.$parent.pagination.position = data.data.rows.page;

              if ( this.row && this.row.id == row.id )
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
        isImageField(field){
          if ( field in this.model.fields )
          {
            var field = this.model.fields[field];

            if ( 'image' in field )
              return true;
          }

          return false;
        },
        /*
         * Returns varians of column names
         */
        getColumnRightKey(k){
          if ( !(k in this.model.fields) && ((k + '_id') in this.model.fields) )
            return k + '_id';

          return k;
        },
        /*
         * Check if can be added column after other column
         */
        addColumn(modifiedData, k, key, where, columns)
        {
          if ( where in columns[k] && (columns[k][where] == key || columns[k][where] + '_id' == key) )
          {
            var field_key = this.getColumnRightKey(k);

            if ( k in modifiedData )
              delete modifiedData[k];

            if ( field_key in modifiedData )
              delete modifiedData[field_key];

            modifiedData[field_key] = columns[k].name||columns[k].title||this.model.fields[field_key].name;
          }

          return modifiedData;
        }
      },
  }
</script>