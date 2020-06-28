<template>
  <div>
    <table v-bind:id="'table-'+model.slug" v-bind:class="['table', 'data-table', 'table-bordered', 'table-striped', { 'sortable' : model.sortable && orderby[0] == '_order' }]">
      <thead>
        <tr>
          <th @click="toggleAllCheckboxes()" v-if="multipleCheckbox">
            <i data-toggle="tooltip" :data-original-title="trans(isCheckedAll ? 'uncheck-all' : 'check-all')" :class="isCheckedAll ? 'fa-check-square-o' : 'fa-square-o'" class="fa"></i>
          </th>
          <th v-for="(field, name) in columns" v-bind:class="'th-'+field" v-on:click="toggleSorting(field)">
            <i class="arrow-sorting fa fa-arrow-up" v-if="orderby[0] == field && orderby[1] == 0"></i>
            <i class="arrow-sorting fa fa-arrow-down" v-if="orderby[0] == field && orderby[1] == 1"></i>
            {{ name }}
          </th>
          <th class="th-options-buttons"></th>
        </tr>
      </thead>
      <tbody data-model="{{ model.slug }}">
        <tr v-for="(key, item) in rowsdata" :data-index="item.id" v-drag-and-drop drag-start="beforeUpdateOrder" drag-end="endDraggind" drop="updateOrder">
          <td class="checkbox-td" v-if="multipleCheckbox">
            <div class="checkbox-box" @click="checkRow(item.id)">
              <input type="checkbox" :checked="checked.indexOf(item.id) > -1">
              <span class="checkmark"></span>
            </div>
          </td>

          <td v-for="(field, name) in columns" @click="checkRow(item.id, field)" v-bind:class="['td-'+field, { image_field : isImageField(field) } ]">
            <table-row-value
              :settings="getCachableColumnsSettings(field)"
              :columns="columns"
              :field="field"
              :name="name"
              :item="item"
              :model="model"
              :image="isImageField(field)">
            </table-row-value>
          </td>

          <td class="buttons-options" v-bind:class="[ 'additional-' + buttonsCount(item) ]">
            <div v-if="isEditable"><button type="button" v-on:click="selectRow(item)" v-bind:class="['btn', 'btn-sm', {'btn-success' : isActiveRow(item), 'btn-default' : !isActiveRow(item) }]" data-toggle="tooltip" title="" :data-original-title="trans('edit')"><i class="fa fa-pencil"></i></button></div>
            <div v-if="isEnabledHistory"><button type="button" v-on:click="showHistory(item)" class="btn btn-sm btn-default" v-bind:class="{ 'enabled-history' : isActiveRow(item) && history.history_id }" data-toggle="tooltip" title="" :data-original-title="trans('history.changes')"><i class="fa fa-history"></i></button></div>
            <div v-if="canShowGettext"><button type="button" v-on:click="openGettextEditor(item)" class="btn btn-sm btn-default" data-toggle="tooltip" title="" :data-original-title="trans('gettext-update')"><i class="fa fa-globe"></i></button></div>
            <div><button type="button" v-on:click="showInfo(item)" class="btn btn-sm btn-default" data-toggle="tooltip" title="" :data-original-title="trans('row-info')"><i class="fa fa-info"></i></button></div>
            <div v-for="(button_key, button) in getButtonsForRow(item)">
              <button type="button" v-on:click="buttonAction(button_key, button, item)" v-bind:class="['btn', 'btn-sm', button.class]" data-toggle="tooltip" title="" v-bind:data-original-title="button.name"><i v-bind:class="['fa', button_loading == getButtonKey(item.id, button_key) ? 'fa-refresh' : button.icon, { 'fa-spin' : button_loading == getButtonKey(item.id, button_key) }]"></i></button>
            </div>
            <div v-if="model.publishable"><button type="button" v-on:click="togglePublishedAt(item)" v-bind:class="['btn', 'btn-sm', { 'btn-info' : !item.published_at, 'btn-warning' : item.published_at}]" data-toggle="tooltip" title="" :data-original-title="item.published_at ? trans('hide') : trans('show')"><i v-bind:class="{ 'fa' : true, 'fa-eye' : item.published_at, 'fa-eye-slash' : !item.published_at }"></i></button></div>
            <div v-if="model.deletable && count > model.minimum"><button type="button" v-on:click="removeRow( item, key )" class="btn btn-danger btn-sm" :class="{ disabled : isReservedRow(item) }" data-toggle="tooltip" title="" :data-original-title="trans('delete')"><i class="fa fa-remove"></i></button></div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
  import TableRowValue from './TableRowValue.vue';

  export default {
      props : ['row', 'rows', 'rowsdata', 'buttons', 'count', 'field', 'gettext_editor', 'enabledcolumns', 'model', 'orderby', 'dragging', 'history', 'checked', 'button_loading'],

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

      events: {
        selectHistoryRow(history_data){
          if ( this.model.slug != history_data[0] )
            return;

          this.selectRow({ id : history_data[1] }, null, null, history_data[2], history_data[3]);
        }
      },

      computed: {
        multipleCheckbox(){
          return this.checked.length > 0;
        },
        defaultColumns(){
          var data = {},
              key;

          //Get columns from row
          for ( var i = 0; i < this.model.columns.length; i++ )
          {
            key = this.model.columns[i];

            //If is column hidden
            if (this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.hidden'))
              continue;

            if (
              this.hidden.indexOf( key ) == -1
              && this.avaliableColumns.indexOf( key ) > -1
              && (
                  !(key in this.model.fields)
                  || (
                    this.model.fields[key].hidden != true
                    && this.model.fields[key].invisible != true
                  )
              )
            )
            {
              data[ this.model.columns[i] ] = this.fieldName( this.model.columns[i] );
            }
          }

          var columns = this.$root.getModelProperty(this.model, 'settings.columns');

         /*
          * Check if can be added column after other column
          */
          var except = [];
          var addColumn = function(modifiedData, k, key, where, columns)
          {
            if ( where in columns[k] && (columns[k][where] == key || columns[k][where] + '_id' == key) )
            {
              var field_key = this.getColumnRightKey(k);

              //We can't add column which has been added, because we reorder array
              if ( except.indexOf(field_key) > -1 )
                return modifiedData;

              except.push(field_key);

              if ( k in modifiedData )
                delete modifiedData[k];

              if ( field_key in modifiedData )
                delete modifiedData[field_key];

              modifiedData[field_key] = columns[k].name||columns[k].title||this.model.fields[field_key].column_name||this.model.fields[field_key].name;
            }

            return modifiedData;
          }.bind(this);

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
                  modifiedData = addColumn(modifiedData, k, key, 'before', columns);

                modifiedData[key] = data[key];

                //Add custom column after actual column
                for ( var k in columns )
                  modifiedData = addColumn(modifiedData, k, key, 'after', columns);
              }

              data = modifiedData;
            }

            for ( var key in columns )
            {
              if ( !(key in data) && (columns[key].hidden != true && columns[key].invisible != true) )
              {
                var field_key = this.getColumnRightKey(key);

                data[key] = columns[key].name||columns[key].title||this.model.fields[field_key].column_name||this.model.fields[field_key].name;
              }
            }
          }

          //Remove increments
          if ( this.$root.getModelProperty(this.model, 'settings.increments') === false && 'id' in data )
            delete data['id'];

          this.$parent.default_columns = Object.keys(data);

          return data;
        },
        columns(){
          var columns = _.cloneDeep(this.defaultColumns);

          //If enabled columns has not been set yet
          if ( ! this.enabledcolumns )
            this.resetAllowedFields(columns)
          else {
            //Disable changed fields
            for ( var key in this.enabledcolumns )
              if ( this.enabledcolumns[key].enabled == false && key in columns )
                delete columns[key];
              else if ( this.enabledcolumns[key].enabled == true && !(key in columns) )
                columns[key] = this.fieldName(key);
          }

          return columns;
        },
        avaliableColumns(){
          return ['id'].concat( Object.keys( this.model.fields ) );
        },
        isEditable(){
          return this.model.editable || this.$parent.$parent.hasChilds() > 0;
        },
        isEnabledHistory(){
          return this.model.history == true;
        },
        canShowGettext(){
          if ( this.model.slug == 'languages' && this.$root.gettext == true )
            return true;

          return false;
        },
        formID(){
          return 'form-' + this.$parent.$parent.depth_level + '-' + this.model.slug;
        },
        availableButtons(){
          return this.$parent.availableButtons;
        },
        isCheckedAll(){
          var ids = this.rows.data.map(item => item.id);

          if ( this.checked.length == 0 )
            return false;

          return _.isEqual(ids, this.checked);
        },
      },

      methods: {
        /*
         * We need cache all settings for columns, for better performance
         */
        getCachableColumnsSettings(field){
            if ( ! this._cacheColumnSettings ) {
                this._cacheColumnSettings = {};
            }

            if ( field in this._cacheColumnSettings ){
                return this._cacheColumnSettings[field];
            }

            var settings = {
                encode : this.$root.getModelProperty(this.model, 'settings.columns.'+field+'.encode', true),
                add_before : this.$root.getModelProperty(this.model, 'settings.columns.'+field+'.add_before'),
                add_after : this.$root.getModelProperty(this.model, 'settings.columns.'+field+'.add_after'),
                field : this.model.fields[field],
                limit : this.$root.getModelProperty(this.model, 'settings.columns.'+field+'.limit'),
                default_slug : this.$root.languages[0].slug,
                models_list : this.$root.models_list,
            };

            return this._cacheColumnSettings[field] = settings;
        },
        toggleAllCheckboxes(){
          var ids = this.rows.data.map(item => item.id);

          this.checked = this.isCheckedAll ? [] : ids;
        },
        checkRow(id, field){
          var checked = this.checked.indexOf(id);

          //Disable checking on type of fields
          if ( field in this.model.fields && ['file'].indexOf(this.model.fields[field].type) > -1 )
            return;

          if ( checked == -1 )
            this.checked.push(id);
          else
            this.checked.splice(checked, 1);
        },
        resetAllowedFields(columns){
          var enabled = {};

          //Add allowed keys
          for ( var key in columns )
            enabled[key] = {
              name : columns[key],
              enabled : true,
            };

          //After allowed keys, add all hidden
          for ( var key in this.model.fields )
            if ( !(key in enabled) )
              enabled[key] = {
                name : this.fieldName( key ),
                enabled : false,
              };

          this.$parent.$set('enabled_columns', enabled);
        },
        isReservedRow(row){
          return this.$parent.isReservedRow(row.id);
        },
        buttonsCount(item){
          var buttons = this.getButtonsForRow(item),
              additional = 0;

          additional += this.isEnabledHistory ? 1 : 0;
          additional += this.canShowGettext ? 1 : 0;
          additional -= !this.model.publishable ? 1 : 0;

          return Object.keys(buttons||{}).length + additional;
        },
        getButtonsForRow(item){
          if ( ! this.rows.buttons || !(item.id in this.rows.buttons) )
            return {};

          var data = {},
              buttons = this.rows.buttons[item.id];

          for ( var key in buttons )
          {
            if ( ['button', 'both', 'multiple'].indexOf(buttons[key].type) > -1 )
              data[key] = buttons[key];
          }

          return data;
        },
        getButtonKey(id, key){
          return this.$parent.getButtonKey(id, key);
        },
        buttonAction(key, button, row){
          return this.$parent.buttonAction(key, button, row);
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
            return this.model.fields[key].column_name||this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.name')||this.model.fields[key].name;
          else {
            switch( key )
            {
              case 'id':
                return this.$root.trans('number');
                break;
              case 'created_at':
                return this.$root.trans('created');
                break;
              case 'updated_at':
                return this.$root.trans('updated');
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
        endDraggind(){
          //Disable sorting when is used sorting columns
          this.enableDraggind();
        },
        enableDraggind(){
          this.$parent.initTimeout(false);
          this.dragging = false;
        },
        updateOrder(dragged, dropped){
          //Disable sorting when is used sorting columns
          if ( this.orderby[0] != '_order' ) {
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
        getDateByField(row, key){
          if ( key in this.model.fields )
            return row[key];

          return moment(row[key]).format('DD.MM.Y HH:mm');
        },
        showInfo(row){
          var data = '';

          if ( row.created_at != null )
            data += this.$root.trans('created-at') + ': <strong>' + this.getDateByField(row, 'created_at') + '</strong><br>';

          if ( row.updated_at != null && this.model.editable != false )
            data += this.$root.trans('last-change') + ': <strong>' + this.getDateByField(row, 'updated_at') + '</strong><br>';

          if ( row.published_at != null )
            data += this.$root.trans('published-at') + ': <strong>' + this.getDateByField(row, 'published_at') + '</strong>';

          this.$root.openAlert(this.$root.trans('row-info-n') + ' ' + row.id, data, 'primary', null, function(){});
        },
        openGettextEditor(item){
          this.gettext_editor = item;
        },
        showHistory(row){
          this.$parent.$parent.showHistory(row);
        },
        selectRow(row, data, model, history_id, model_row){
          //If is selected same row
          if ( this.row && this.row.id == row.id && !history_id )
            return;

          //Recieve just messages between form and rows in one model component
          if (model && this.model.slug != model)
            return;

          //Resets form
          if ( row === true && data === null )
            return this.row = null;

          var render = function(response){
            for ( var key in response ){
              row[key] = response[key];
            }

            //Bind model data
            this.row = _.cloneDeep(row, true);

            //Fix for single model with history support
            if ( model_row ){
              for ( var key in model_row )
                model_row[key] = row[key];
            }

            this.$parent.$parent.closeHistory(history_id ? true : false);

            this.scrollToForm();
          }.bind(this);

          if ( data ) {
            render(data);
          } else {
            this.$http.get(this.$root.requests.show, { model : this.model.slug, id : row.id, subid : history_id })
            .then(function(response){
              render(response.data);
            })
            .catch(function(response){
              this.$root.errorResponseLayer(response);
            });
          }
        },
        removeRow(row){
          this.$parent.removeRow(row);
        },
        togglePublishedAt(row){
          this.$parent.togglePublishedAt(row);
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
        scrollToForm(){

          //Allow scroll form only on full width table
          if ( this.$parent.$parent.activeSize != 0 )
            return;

          setTimeout(function(){
            $('html, body').animate({
                scrollTop: $('#' + this.formID).offset().top - 10
            }, 500);
          }.bind(this), 25);
        },
        trans(key){
          return this.$root.trans(key);
        }
      },
  }
</script>