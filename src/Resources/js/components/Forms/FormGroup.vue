<template>
    <div class="fields-group" :group-id="group.id" :class="getGroupClass(group)" :data-fields="visibleFields.length">
        <div :class="{ 'nav-tabs-custom' : canShowGroupName(group) }">
            <h4 v-if="canShowGroupName(group)">
                <i v-if="group.icon" :class="['fa', group.icon]"></i>
                <span v-html="group.name"></span>
            </h4>

            <div class="tab-content">
                <div class="row">
                    <div v-if="hasTabs(group.fields)" class="col-lg-12">
                        <form-tabs-builder
                            :tabs="tabsFields"
                            :model="model"
                            :row="row"
                            :hasparentmodel="hasparentmodel"
                            :history="history"
                            :depth_level="depth_level">
                        </form-tabs-builder>
                    </div>
                    <div v-else-if="canShowGroupName(group) && visibleFields.length == 0">
                        <div class="col-md-12">
                            <p class="empty-group-separator">...</p>
                        </div>
                    </div>

                    <div v-for="(item, index) in group.fields">
                        <div
                            v-if="isField(item) && canRenderField(model.fields[item])"
                            v-for="langslug in getFieldLangs(model.fields[item])"
                            v-show="canShowField(model.fields[item]) && canShowLanguageField(model.fields[item], langslug, inputlang)"
                            class="col-lg-12">
                            <form-input-builder
                                :key="item"
                                :history="history"
                                :model="model"
                                :langid="langid"
                                :inputlang="inputlang"
                                :langslug="langslug"
                                :row="row"
                                :index="index"
                                :hasparentmodel="hasparentmodel"
                                :field_key="item"
                                :field="model.fields[item]"
                                :depth_level="depth_level">
                            </form-input-builder>
                        </div>

                        <form-group
                            v-if="isGroup(item) && !isTab(item)"
                            :group="item"
                            :model="model"
                            :row="row"
                            :hasparentmodel="hasparentmodel"
                            :history="history"
                            :depth_level="depth_level">
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

    props : ['model', 'row', 'history', 'group', 'langid', 'inputlang', 'hasparentmodel', 'depth_level'],

    components : { FormInputBuilder, FormTabsBuilder },

    created(){
        /*
         * Fir for double recursion in VueJS
         */
        this.$options.components['form-tabs-builder'] = Vue.extend(FormTabsBuilder);
    },

    computed: {
        tabsFields(){
            return this.group.fields.filter(item => {
                return this.isTab(item);
            });
        },
        visibleFields(){
            var fields = this.group.fields.filter(item => {
                var field = this.model.fields[item];

                return typeof item !== 'string' || !(
                    field.invisible && field.invisible == true
                    || field.removeFromForm && field.removeFromForm == true
                    || ! this.canShowField(field)
                );
            });

            return fields;
        },
        isOpenedRow(){
            return this.row && 'id' in this.row;
        },
    },

    methods: {
        canRenderField(field){
            return !('removeFromForm' in field && field.removeFromForm == true)
                    && !('invisible' in field && field.invisible == true);
        },
        canShowField(field){
            if ( 'hideFromForm' in field && field.hideFromForm == true )
                return false;

            if ( (field.ifExists === true || field.hideOnCreate === true) && ! this.isOpenedRow )
                return false;

            if ( (field.ifDoesntExists === true || field.hideOnUpdate === true) && this.isOpenedRow )
                return false;

            return true;
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