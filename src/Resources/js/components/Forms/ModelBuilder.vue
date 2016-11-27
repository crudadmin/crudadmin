<template>

  <div class="box">
    <div class="box-header">
      <h3 class="box-title">{{ model.name }}</h3> <span class="model-info" v-if="model.title && ischild">{{{ model.title }}}</span>

      <ul class="pagination pull-right pagination-sm no-margin">
        <li v-for="size in sizes" v-bind:class="{ 'active' : size.active, 'disabled' : size.disabled }"><a href="#" @click.prevent="changeSize(size)" title="">{{ size.name }}</a></li>
      </ul>
    </div>

    <div class="box-body">

      <div v-bind:class="{ 'row' : true, 'flex-table' : activeSize == 0 }">

        <!-- left column -->
        <div class="col col-lg-{{ 12 - activeSize }} col-md-12 col-sm-12" v-show="canShowForm">
          <form-builder :model="model" :canaddrow="canAddRow" :row.sync="row"></form-builder>
        </div>
        <!--/.col (left) -->

        <!-- right column -->
        <div class="col col-lg-{{ 12 - ( 12 - activeSize ) }} col-md-12 col-sm-12" v-show="hasRows && canShowRows">
          <model-rows-builder :model="model" :row.sync="row"></model-rows-builder>
        </div>
        <!--/.col (right) -->

      </div>

      <model-builder v-if="row" :langid="langid" v-for="child in model.childs" :ischild="true" :model="child"></model-builder>
    </div>
  </div>

</template>

<script>
  import FormBuilder from './FormBuilder.vue';
  import ModelRowsBuilder from './ModelRowsBuilder.vue';

  export default {
    props : ['model', 'langid', 'ischild'],
    name : 'model-builder',
    data : function(){
      return {
        sizes : [
          { size : 8, name : 'Small', active : false, disabled : false },
          { size : 6, name : 'Medium', active : false, disabled : false },
          { size : 3, name : 'Big', active : false, disabled : false },
          { size : 0, name : 'Full width', active : false, disabled : false },
        ],

        activeSize : null,

        row : null,

        buffer : {
          rows : null,
        },

        language_id : null,

        refreshInterval : 3000,
      };
    },

    created() {
      //Passing model data from parent
      if ( ! this.model )
        this.model = this.$parent.model;

      //For file paths
      this.root = this.$root.$http.$options.root;
    },

    ready() {
      this.checkIfCanShowLanguages();

      //Refresh rows refreshInterval
      this.refreshRows();
    },

    destroyed() {
      if ( this.updateTimeout )
        clearTimeout(this.updateTimeout);
    },

    watch : {
      sizes : {
        deep: true,
        handler(data){
          var _this = this;

          this.activeSize = data.filter(function(row){

            if ( row.active == true )
            {
              var rows = _this.getStorage();
              rows[ _this.model.slug ] = row.size;
              localStorage.sizes = JSON.stringify( rows );
            }

            return row.active == true;
          })[0].size;

          this.activeSize;
        }
      },
      langid(){

        //resets form after language change
        if ( this.hasChilds() == 0 )
        {
          this.$broadcast('onCreate', [true, null, this.model.slug]);
        }

      },
    },

    events : {

      //Receive event and send into child components
      proxy(name, param){
        this.$broadcast(name, param);
      },

    },

    methods : {
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

        var data = this.getStorage();

        //Full screen
        if ( ! this.canShowForm )
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
        }

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
        this.buffer['rows'] = rows;

        return rows;
      },

      filterLanguage(array){
        if ( ! array )
          return this.saveLanguageFilter(array);

        if ( this.$root.languages.length == 0 )
          return this.saveLanguageFilter(array);

        //If is languages table
        if ( this.model.slug == 'languages' )
          return this.saveLanguageFilter(array);

        return this.saveLanguageFilter(array.filter(function(row){

          if ( ! ('language_id' in row) )
            return true;

          return row.language_id == this.$root.language_id;
        }.bind(this)));
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
      dateValue(value, field){
        if ( !value )
          return;

        value = value.substr(0, 10);

        if ( ! ('date_format' in field) )
          return value;

        value = value.split('-');

        return field.date_format.toLowerCase().replace('y', value[0]).replace('m', value[1]).replace('d', value[2]);
      },
      refreshRows(){
        var t = this;
        this.$root.$http.get(this.$root.requests.refresh, { model : this.model.slug, id : this.getLastRowsId })
        .then(function(response){

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
            if ( 'options' in this.model.fields[ key ] )
              this.model.fields[ key ].options = response.data.fields[ key ].options;
          }

          this.updateTimeout = setTimeout(function(){
            this.refreshRows.call(this);
          }.bind(this), this.refreshInterval);
        }.bind(this))
        .catch(function(e){
          //If has been client logged off
          if ( e.status == 401 )
          {
            if ( this.updateTimeout )
              clearTimeout(this.updateTimeout);

            this.$root.openAlert('Upozornenie!', 'Boli ste automatický odhlásený. Prosím, znova sa prihláste.', 'warning', null, function(){
              window.location.reload();
            });
          }
        });
      },
    },

    computed: {
      canShowRows(){
        if ( this.model.minimum == 1 && this.model.maximum == 1)
        {
          this.$broadcast('onCreate', [this.getRows[0], null, this.model.slug]);

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

        if ( this.model.maximum <= this.getRows.length )
          return false;

        return true;
      },
      canShowForm(){

        if ( !this.row && !this.canAddRow || ( this.row && this.model.editable == false && !this.canAddRow ))
        {
          return false;
        }

        return true;

      },
      getRows(){
        //Check if is form belongs to other form
        if ( this.$parent.row == null || !( 'id' in this.$parent.row ) ){
          return this.filterLanguage(this.model.rows);
        } else {
          if ( ! this.model.rows )
            return false;

          var _this = this;

          return this.filterLanguage(this.model.rows).filter(function(row){
            return row[_this.model.foreign_column] == _this.$parent.row.id;
          });
        }
      },
      hasRows(){
        var hasRows = this.getRows.length > 0;

        //Resets opened form
        if ( hasRows == false )
        {
          this.$broadcast('onCreate', [true, null, this.model.slug]);
        }

        return hasRows;
      },
      getLastRowsId(){
        var lastid = 0;

        //Get last id
        for (var key in this.model.rows)
          if ( this.model.rows[key].id > lastid )
            lastid = this.model.rows[key].id;

        return lastid;
      }
    },

    components : { FormBuilder, ModelRowsBuilder }
  }
</script>