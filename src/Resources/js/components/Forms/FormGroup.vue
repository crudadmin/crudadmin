<template>
<div class="fields-group" :group-id="group.id" v-bind:class="getGroupClass(group)" :data-fields="visibleFields.length">
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
                    <div
                      :data-field="item"
                      :data-lang="langslug"
                      v-if="isField(item) && canShowField(model.fields[item])"
                      v-for="langslug in getFieldLangs(model.fields[item])"
                      v-show="canShowLanguageField(this.model.fields[item], langslug, inputlang)"
                      class="col-lg-12 field-wrapper">
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

    computed: {
      visibleFields(){
        var fields = this.group.fields.filter(item => {
          var field = this.model.fields[item];

          return typeof item !== 'string' || !(field.invisible||field.removeFromForm);
        });

        return fields;
      },
    },

    methods: {
      canShowField(field){
        return !('removeFromForm' in field);
      },
      //Return group class
      getGroupClass(group){
        var width = (group.width+'').split('-');

        if ( width[0] == 'half' )
          width[0] = 6;
        else if ( width[0] == 'full' )
          width[0] = 12;
        else if ( width[0] == 'third' )
          width[0] = 4;

        if ( width.length == 2 && width[1] == 'inline' )
          return 'col-md-'+width[0]+' inline-col';

        if ( $.isNumeric(width[0]) )
          return 'col-md-' + width[0];

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
        if ( ! field || !('locale' in field) )
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