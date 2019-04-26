<template>
  <!-- Content Header (model header) -->
  <section class="content-header">
    <h1>
      {{ model.name }}
      <small>{{{ model.title }}}</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-dashboard"></i> {{ trans('admin') }}</a></li>
      <li v-if="getGroup">{{ getGroup.name }}</li>
      <li class="active"><a class="active"><i v-bind:class="['fa', model.icon]"></i> {{ model.name }}</a></li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
      <model-builder :model="model" :langid="langid"></model-builder>
  </section>
  <!-- /.content -->
</template>

<script>
  import ModelBuilder from '../Forms/ModelBuilder.vue';
  import ModelHelper from '../Model/ModelHelper.js';

  export default {
      name : 'base-page-view',

      data : function(){
        return {
          //Passing model data from parent
          model : ModelHelper(this.$parent.model),
        };
      },

      props : ['langid'],

      ready(){
        if ( typeof ga == 'function' )
          ga('send', 'pageview', 'auto');
      },

      computed: {
        getGroup(){
          if ( this.model.slug in this.$root.models )
            return false;

          for ( var key in this.$root.models )
          {
            if ( this.model.slug in this.$root.models[key].submenu )
              return this.$root.models[key];
          }

          return false;
        }
      },

      components : { ModelBuilder },

      methods : {
        trans(key){
          return this.$root.trans(key);
        },
      }
  }
</script>