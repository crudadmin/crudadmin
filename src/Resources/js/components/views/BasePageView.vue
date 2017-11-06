<template>
  <!-- Content Header (model header) -->
  <section class="content-header">
    <h1>
      {{ model.name }}
      <small>{{{ model.title }}}</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-dashboard"></i> Administrácia</a></li>
      <li v-if="getGroup">{{ getGroup.name}}</li>
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

  export default {
      data : function(){
        return {
          //Passing model data from parent
          model : this.$parent.model,
        };
      },

      props : ['langid'],

      ready(){
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

      components : { ModelBuilder }
  }
</script>