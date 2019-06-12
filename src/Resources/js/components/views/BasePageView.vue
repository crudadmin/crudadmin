<template>
  <div>
    <!-- Content Header (model header) -->
    <section class="content-header" v-if="model">
      <h1>
        {{ model.name }}
        <small v-html="model.title"></small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> {{ trans('admin') }}</a></li>
        <li v-if="getGroup">{{ getGroup.name }}</li>
        <li class="active">
          <a class="active"><i v-bind:class="['fa', model.icon]"></i> {{ model.name }}</a>
        </li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content" v-if="model">
        <model-builder :key="model.slug" :model="model" :langid="langid" dusk="model-builder"></model-builder>
    </section>
    <!-- /.content -->
  </div>
</template>

<script>
  import ModelBuilder from '../Forms/ModelBuilder.vue';
  import ModelHelper from '../Model/ModelHelper.js';

  export default {
      name : 'base-page-view',

      data : function(){
        return {

        };
      },

      props : ['langid'],

      mounted(){
        if ( typeof ga == 'function' )
          ga('send', 'pageview', 'auto');
      },

      computed: {
        /*
         * Return model from actual page
         */
        model(){
          var model = this.$root.models[this.$route.params.model];

          return model ? ModelHelper(model) : null;
        },
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