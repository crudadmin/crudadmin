<template>
<div class="fields-group" v-bind:class="getGroupClass(group)">
    <div :class="{ 'nav-tabs-custom' : canShowGroupName(group) }">
        <h4 v-if="canShowGroupName(group)"><i v-if="group.icon" :class="['fa', group.icon]"></i> {{{ group.name }}}</h4>

        <div class="tab-content">
            <div class="row">
              <div v-if="hasTabs(group.fields)" class="col-lg-12">
                <form-tabs-builder
                    :tabs="group.fields | tabs"
                    :model="model"
                    :row="row"
                    :hasparentmodel="hasparentmodel"
                    :history="history">
                </form-tabs-builder>
              </div>

                <div v-for="(index, item) in group.fields">
                    <div :data-field="item" v-if="isField(item) && canShowField(model.fields[item])" v-for="langslug in getFieldLangs(model.fields[item])" v-show="canShowLanguageField(this.model.fields[item], langslug, inputlang)" class="col-lg-12 field-wrapper">
                        <form-input-builder
                          :history="history"
                          :model="model"
                          :langid="langid"
                          :langslug="langslug"
                          :row="row"
                          :index="index"
                          :key="item"
                          :hasparentmodel="hasparentmodel"
                          :field="model.fields[item]">
                        </form-input-builder>
                    </div>

                    <form-group
                        v-if="isGroup(item) && !isTab(item)"
                        :group="item"
                        :model="model"
                        :row="row"
                        :hasparentmodel="hasparentmodel"
                        :history="history">
                    </form-group>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
import FormTabsBuilder from './FormTabsBuilder.vue';
import FormInputBuilder from './FormInputBuilder.vue';

export default {
    name : 'form-group',

    props : ['model', 'row', 'history', 'group', 'langid', 'inputlang', 'hasparentmodel'],

    components : { FormInputBuilder, FormTabsBuilder },

    created(){
        /*
         * Fir for double recursion in VueJS
         */
        this.$options.components['form-tabs-builder'] = Vue.extend(FormTabsBuilder);
    },

    filters: {
      tabs(items){
        var tabs = items.filter(function(item){
          return this.isTab(item);
        }.bind(this));

        return tabs;
      },
    },

    methods: {
      canShowField(field){
        return !('removeFromForm' in field);
      },
      //Return group class
      getGroupClass(group){
        if ( group.width == 'half' )
          return 'col-md-6';

        if ( $.isNumeric(group.width) )
          return 'col-md-' + group.width;

        return 'col-md-12';
      },
      canShowGroupName(group){
        return group.name;
      },
      isField(field){
        return this.$parent.isField(field);
      },
      isGroup(group){
        return this.$parent.isGroup(group);
      },
      isTab(tab){
        return this.$parent.isTab(tab);
      },
      hasTabs(fields){
        return this.$parent.hasTabs(fields);
      },
      getFieldLangs(field){
        if ( !('locale' in field) )
          return 1;

        return _.map(this.$root.languages, 'slug');
      },
      canShowLanguageField(field, slug, inputlang){
        if ( !('locale' in field) )
          return true;

        return inputlang.slug == slug;
      },
    },
}
</script>