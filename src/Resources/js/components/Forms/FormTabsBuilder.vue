<template>
  <div class="nav-tabs-custom" v-bind:class="{ default : hasNoTabs }">
    <ul class="nav nav-tabs">
      <li v-for="tab in getTabs" v-bind:class="{ active : activetab == $index }" @click="activetab = $index">
        <a data-toggle="tab" aria-expanded="true">{{ tab.name }}</a>
      </li>
    </ul>
    <div class="tab-content">
      <div v-for="tab in getTabs" class="tab-pane" v-bind:class="{ active : activetab == $index }">
        <div class="row">
          <div v-if="hasTabs(tab.fields)" class="col-lg-12">
            <form-tabs-builder
              :tabs="tab.fields | tabs"
              :model="model"
              :row="row"
              :history="history">
            </form-tabs-builder>
          </div>

          <form-group
            v-for="item in chunkGroups(tab.fields)"
            v-if="isGroup(item) && !isTab(item)"
            :group="item"
            :model="model"
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

  export default {
    name : 'form-tabs-builder',

    props : ['model', 'row', 'history', 'group', 'tabs'],

    data(){
      return {
        activetab : 0,
      };
    },

    ready()
    {

    },


    watch: {

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
        var model_fields = this.model.fields_groups.length > 0 ? this.model.fields_groups : Object.keys(this.model.fields),
            items = this.tabs||(this.group ? this.group.fields : model_fields),
            tabs = items.filter(function(group) {
              return this.isTab(group);
            }.bind(this));

        if ( tabs.length == 0 ){
          return [{
            name : this.group ? this.group.name : 'Default',
            fields : items,
            type : 'tab',
            default : true,
          }];
        }

        return tabs;
      },
      hasNoTabs(){
        return this.getTabs.length == 1 && this.getTabs[0].default === true;
      },
    },

    methods: {
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
    },

    components : { FormGroup }
  }
</script>