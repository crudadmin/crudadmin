<template>
  <div class="nav-tabs-custom" v-bind:class="{ default : hasNoTabs }">
    <ul class="nav nav-tabs">
      <li v-for="tab in getTabs" v-bind:class="{ active : activetab == $index }" @click="activetab = $index">
        <a data-toggle="tab" aria-expanded="true"><i v-if="tab.icon" :class="['fa', tab.icon]"></i> {{ tab.name||trans('general-tab') }}</a>
      </li>
    </ul>
    <div class="tab-content">
      <div v-for="tab in getTabs" class="tab-pane" v-bind:class="{ active : activetab == $index }">
        <div class="row">
          <div v-if="hasTabs(tab.fields) || isModel(tab)" :class="{ model : tab.type == 'model' }" class="col-lg-12">
            <form-tabs-builder
              v-if="hasTabs(tab.fields)"
              :tabs="tab.fields | tabs"
              :model="model"
              :row="row"
              :hasparentmodel="hasparentmodel"
              :history="history">
            </form-tabs-builder>

            <model-builder
              v-if="isModel(tab)"
              :langid="langid"
              :ischild="true"
              :model="tab.model"
              :activetab="activetab == $index"
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

    props : ['model', 'row', 'history', 'group', 'tabs', 'childs', 'langid', 'cansave', 'hasparentmodel'],

    components : { FormGroup },

    data(){
      return {
        activetab : 0,
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
      });
    },

    ready(){

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

        if ( this.childs == true )
        {
          for ( var key in this.model.childs )
          {
            if ( this.model.childs[key].in_tab == true && ( this.model.childs[key].without_parent == true || this.isOpenedRow ) )
            {
              tabs.push({
                name : this.model.childs[key].name,
                fields : [],
                type : 'model',
                model : _.clone(this.model.childs[key]),
                icon : this.model.childs[key].icon
              });
            }
          }
        }

        return tabs;
      },
      hasNoTabs(){
        return this.getTabs.length == 1 && this.getTabs[0].default === true;
      },
      isOpenedRow(){
        return this.row && 'id' in this.row;
      },
    },

    methods: {
      trans(key){
        return this.$root.trans(key);
      },
      isModel(tab){
        return tab.type == 'model' && (this.isOpenedRow || tab.model.without_parent == true);
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
    }
  }
</script>