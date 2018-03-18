<template>
  <div class="nav-tabs-custom" v-bind:class="{ default : hasNoTabs }">
    <ul class="nav nav-tabs">
      <li v-for="tab in getTabs" v-if="isTab(tab) && !tab.model || isModel(tab)" v-bind:class="{ active : activetab == $index, 'model-tab' : isModel(tab) }" @click="activetab = $index">
        <a data-toggle="tab" aria-expanded="true"><i v-if="getTabIcon(tab)" :class="['fa', getTabIcon(tab)]"></i> {{ getTabName(tab)||trans('general-tab') }}</a>
      </li>
    </ul>
    <div class="tab-content">
      <div v-for="tab in getTabs" class="tab-pane" v-bind:class="{ active : activetab == $index }">
        <div class="row">
          <div v-if="hasTabs(tab.fields) || isModel(tab)" :class="{ model : isModel(tab) }" class="col-lg-12">
            <form-tabs-builder
              v-if="hasTabs(tab.fields)"
              :tabs="tab.fields | tabs"
              :model="model"
              :row="row"
              :langid="langid"
              :inputlang="inputlang"
              :hasparentmodel="hasparentmodel"
              :history="history">
            </form-tabs-builder>

            <model-builder
              v-if="isModel(tab)"
              :langid="langid"
              :ischild="true"
              :model="getModel(tab.model)"
              :activetab="isLoadedModel(getModel(tab.model), activetab == $index)"
              :parentrow="row">
            </model-builder>
          </div>

          <form-group
            v-for="item in chunkGroups(tab.fields)"
            v-if="isGroup(item) && !isTab(item)"
            :group="item"
            :model="model"
            :hasparentmodel="hasparentmodel"
            :langid="langid"
            :inputlang="inputlang"
            :row="row"
            :history="history"
          ></form-group>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import FormGroup from './FormGroup.vue';
  import ModelBuilder from './ModelBuilder.vue';

  export default {
    name : 'form-tabs-builder',

    props : ['model', 'row', 'history', 'group', 'tabs', 'childs', 'langid', 'inputlang', 'cansave', 'hasparentmodel'],

    components : { FormGroup },

    data(){
      return {
        activetab : 0,

        //Which child models has been loaded
        loaded_models : [],
      };
    },

    created()
    {
      /*
       * Fir for double recursion in VueJS
       */
      this.$options.components['model-builder'] = Vue.extend(ModelBuilder);

      //Reset tabs on change id
      this.$watch('row.id', function(){
        this.activetab = 0;

        this.loaded_models = [];
      });
    },

    watch: {
      activetab(tabid){
        this.cansave = this.getTabs[tabid].type != 'model';
      },
    },

    filters: {
      tabs(items){
        return items.filter(function(item){
          return this.isTab(item);
        }.bind(this));
      },
    },

    computed: {
      getTabs(){
        var model_fields = this.model.fields_groups.length == 1 && this.model.fields_groups[0].type == 'default' ? this.model.fields_groups[0].fields : this.model.fields_groups,
            items = this.tabs||(this.group ? this.group.fields : model_fields),
            tabs = items.filter(function(group) {
              return this.isTab(group);
            }.bind(this));

        if ( tabs.length == 0 || tabs.length > 0 && tabs.length != items.length ){
          items = items.filter(function(group) {
            return ! this.isTab(group);
          }.bind(this));

          tabs = [{
            name : this.group ? this.group.name : this.trans('general-tab'),
            icon : this.group ? this.group.icon : this.model.icon,
            fields : items,
            type : 'tab',
            default : true,
          }].concat(tabs);
        }

        //Add models into tabs if neccesary
        if ( this.childs == true )
          for ( var key in this.model.childs )
          {
            if ( this.model.childs[key].in_tab == true )
            {
              //Check if model is not in fields group
              if ( ! this.isModelInFields(model_fields, this.model.childs[key].slug) )
                tabs.push({
                  fields : [],
                  type : 'tab',
                  model : this.model.childs[key].slug,
                });
            }
          }

        return tabs;
      },
      hasNoTabs(){
        return this.getTabs.filter(function(item){
          if ( ! this.isTab(item) )
            return false;

          if ( item.model && ! this.isModel(item) )
            return false;

          return true;
        }.bind(this)).length == 1 && this.getTabs[0].default === true;
      },
      isOpenedRow(){
        return this.row && 'id' in this.row;
      },
    },

    methods: {
      /*
       * Return model from childs by model table
       */
      getModel(model){
        return this.model.childs[model];
      },
      /*
       * Return tab name
       */
      getTabName(tab){
        if ( this.isModel(tab) )
          return tab.name||this.getModel(tab.model).name;

        return tab.name;
      },
      /*
       * Return tab icon
       */
      getTabIcon(tab){
        if ( this.isModel(tab) )
          return tab.icon||this.getModel(tab.model).icon;

        return tab.icon;
      },
      /*
       * Check if can be model child added into table list
       * if child model is in other tab or group, then we cant add model into end of tabs.
       */
      isModelInFields(childs, model){
        for ( var i = 0 ; i < childs.length; i++ )
        {
          //Check if group field is tab
          if ( this.isTab(childs[i]) )
          {
            //If tab is needed model
            if ( childs[i].model == model ){
              return true;
            }

            //If tab has other childs, then check recursive
            if ( childs[i].fields.length > 0 )
              if ( this.isModelInFields(childs[i].fields, model) )
                return true;
          }
        }

        return false;
      },
      /*
       * Check if tabs is model type
       */
      isModel(tab){
        if ( !(tab.type == 'tab' && tab.model && this.getModel(tab.model).active == true) )
          return false;

        return this.isOpenedRow || this.getModel(tab.model).without_parent == true;
      },
      trans(key){
        return this.$root.trans(key);
      },
      isField(field){
        return typeof field == 'string' && field in this.model.fields;
      },
      isGroup(group){
        return typeof group == 'object' && 'type' in group;
      },
      isTab(group){
        return this.isGroup(group) && group.type == 'tab';
      },
      canShowField(field){
        return !('removeFromForm' in field);
      },
      hasTabs(fields){
        return fields.filter(function(group) {
          return this.isTab(group);
        }.bind(this)).length > 0;
      },
      //Return group class
      getGroupClass(group){
        return this.$parent.getGroupClass(group);
      },
      canShowGroupName(group){
        return group.name;
      },
      chunkGroups(fields){
        var chunkSize = 2,
            chunk = 0,
            data = [];

        for ( var i = 0; i < fields.length; i++ )
        {
          if ( i > 0 && this.isGroup(data[data.length - 1]) && this.isField(fields[i]) || i == 0 && this.isField(fields[i]) )
            data.push([]);

          if ( this.isField(fields[i]) )
            data[data.length - 1].push(fields[i]);
          else {
            data.push(fields[i]);
          }
        }

        var items = data.map(function(item){
          if ( this.isGroup(item) )
            return item;

          return {
            type : 'default',
            fields : item,
            name : this.group ? this.group.name : null,
          }
        }.bind(this));

        return items;
      },
      isLoadedModel(model, active){
        if ( active === true && this.loaded_models.indexOf(model.slug) === -1 )
          this.loaded_models.push(model.slug);

        return this.loaded_models.indexOf(model.slug) > -1;
      }
    }
  }
</script>