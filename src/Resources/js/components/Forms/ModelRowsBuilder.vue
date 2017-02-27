<template>
  <div class="box">
    <div class="box-header box-limit">
      <h3 class="box-title">{{ title }} <small>({{ rows.count }})</small></h3>

      <div class="form-group pull-right" title="Zobraziť na stránke">
        <select @change="changeLimit" class="form-control" v-model="pagination.limit">
          <option v-for="count in pagination.limits">{{ count }}</option>
        </select>
      </div>
    </div>

    <div class="box-body box-table-body">
      <table-rows :model="model" :row.sync="row" :count="rows.count" :rows="rows" :rowsdata.sync="rowsData" :item="$row" :dragging.sync="dragging" :orderby.sync="orderBy"></table-row>
    </div>

    <div class="box-header" v-if="isPaginationEnabled && rows.count>pagination.limit">
      <ul class="pagination pagination-sm no-margin pull-right">
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
    props : ['model', 'row', 'rows', 'langid', 'progress'],

    components : { Refreshing, TableRows },

    data : function(){

      //Load pagination limit from localStorage
      var limit = 'limit' in localStorage ? localStorage.limit : 10;

      return {
        table : null,

        //Sorting
        pagination: {
          position: 1,
          limit : limit,
          limits : [ 5, 10, 20, 30, 50, 100, 200 ],
          refreshing : false,
          maxpages : 15,
        },

        dragging : false,
        orderBy : null,

        refresh : {
          refreshing : true,
          count : 0,
          interval : 5000,
        },
      };
    },

    created() {
      //For file paths
      this.root = this.$root.$http.options.root;

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
      this.loadRows();
    },

    destroyed() {
      this.destroyTimeout();
    },

    events: {
      onCreate(array){
        if ( this.pagination.position == 1 )
        {
          var rows = array.concat( this.rows.data );

          if ( rows.length > this.pagination.limit )
            rows = rows.slice(0, this.pagination.limit);

          this.rows.data = rows;
          this.rows.count += array.length;
        } else {
          this.loadRows();
        }
      },
      onUpdate(array){
        if ( array[0] != this.model.slug )
          return;

        //Update row in table rows
        var data = this.rows.data.slice(0),
            row = array[1];

        for ( var key in data )
        {
          if ( data[key].id == row.id )
          {
            data[key] = row;
          }
        }

        this.rows.data = data;
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
        this.setPosition(1);
      },
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
      isPaginationEnabled(){
        return this.$root.getModelProperty(this.model, 'settings.pagination') !== false;
      },
      rowsData(){
        return this.rows.data.sort(function(a, b){
          //If is null value
          if ( ! a || ! b )
            return false;

          a = a[ this.orderBy[0] ]+'',
          b = b[ this.orderBy[0] ]+'';

          //Sorting numbers
          if ( this.isNumericValue( this.orderBy[0] ) )
          {
            if ( this.orderBy[1] == 1 )
              return b - a;

            return a - b;
          } else {
            if ( this.orderBy[1] == 1 )
              return b.toLowerCase().localeCompare(a.toLowerCase(), 'sk');

            return a.toLowerCase().localeCompare(b.toLowerCase(), 'sk');
          }
        }.bind(this));
      },
    },

    methods: {
      reloadRows(){
        this.row = null;
        this.loadRows();

        return true;
      },
      changeLimit(value){
        localStorage.limit = this.pagination.limit;

        //Reset pagination to first page
        this.setPosition(1);
      },
      getParentRowId(){
        var row = this.$parent.$parent.row;

        if ( !row || !( 'id' in row ))
          return 0;

        return row.id;
      },
      loadRows(indicator){
        if ( indicator !== false )
          this.pagination.refreshing = true;

        // Remove last auto timeout
        this.destroyTimeout();

        this.$http.get(this.$root.requests.rows, {
          model : this.model.slug,
          parent : this.$parent.getParentTableName(),
          subid : this.getParentRowId(),
          langid : this.model.localization === true ? this.langid : 0,
          limit : this.isPaginationEnabled ? this.pagination.limit : 0,
          page : this.pagination.position,
          count : this.refresh.count,
        }).then(function(response){
          //If has been component destroyed, and request is delivered... and some conditions
          if ( this.dragging === true || this.progress === true || !this.$root ){
            return;
          }

          //Disable loader
          this.pagination.refreshing = false;

          //Load rows into array
          this.rows.data = response.data.rows;
          this.rows.count = response.data.count;
          this.$parent.rows.loaded = true;


          //Update field options
          if ( this.refresh.count == 0 ){
            this.updateFieldOptions(response.data.fields);
          }

          //Update refresh informations
          this.refresh.count++;
          this.refresh.refreshing = false;

          //Add next timeout
          if ( !(this.rows.count > 0 && this.model.maximum === 1) )
          {
            this.initTimeout(false);
          }
        })
        .catch(function(response){
          //If has been component destroyed, and request is delivered...
          if ( !this.$root )
            return;

          //Add next timeout
          this.initTimeout(false);

          //Show error alert at first request
          if ( this.refresh.count == 0 && this.hasShowedError !== true ){
            this.hasShowedError = true;
            this.$root.errorResponseLayer(response, null);
          }
        });
      },
      destroyTimeout(){
        if ( this.updateTimeout )
          clearTimeout(this.updateTimeout);
      },
      initTimeout(indicator){
        this.destroyTimeout();

        //Disable autorefreshing when is one row
        if ( this.rows.count > 0 && this.model.maximum === 1 )
          return;

        this.updateTimeout = setTimeout(function(){
          this.loadRows(indicator);
        }.bind(this), this.refresh.interval);
      },
      updateFieldOptions(fields){
          //Update fields from database, for dynamic selectbox values
          for ( var key in fields )
          {
            if ( 'options' in this.model.fields[ key ] && Object.keys(fields[ key ].options).length > 0 )
            {
              this.model.fields[ key ].options = fields[ key ].options;
            }
          }
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
      showLimit(i){
        var max = parseInt(this.rows.count / this.pagination.limit);

        if ( this.rows.count / this.pagination.limit === max )
          max--;

        //If is first or last page, then show it
        if ( i == 0 || i == max )
          return true;

        var offset = this.pagination.position < (this.pagination.maxpages/2) ? (this.pagination.maxpages/2) - this.pagination.position : 0,
            offset = max - this.pagination.position < ( this.pagination.maxpages / 2 ) ? (this.pagination.maxpages/2) - (max - this.pagination.position) : offset;

        if ( this.pagination.position - offset >= i + (this.pagination.maxpages/2) || this.pagination.position <= i - (this.pagination.maxpages/2) - offset)
          return false;

        return true;
      },
      setPosition(position){
        this.pagination.position = position;

        //Load paginated rows...
        this.loadRows();
      }
    },
  }
</script>