<template>
    <div>
        <ul class="sidebar-menu">
            <li class="header">
                {{ hasLanguages && isActive ? trans('language-mutation') : trans('navigation') }}
                <div v-if="hasLanguages && isActive" class="form-group language_select" data-toggle="tooltip" title="" :data-original-title="trans('change-language')">
                    <select v-model="langid" class="form-control">
                        <option v-for="language in languages" :value="language.id">{{ getLangName(language) }}</option>
                    </select>
                </div>
            </li>
        </ul>

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <sidebar-row v-for="(row, key) in groups" :key="key" :row="row"></sidebar-row>
        </ul>
        <!-- /.sidebar-menu -->
    </div>
</template>

<script>
    import SidebarRow from './SidebarRow.vue';

    export default {
        props: ['rows', 'languages', 'langid'],

        components: { SidebarRow },

        watch: {
            rows(rows){
                this.$nextTick(() => {
                    this.addActiveTreeClasses();
                });
            },
        },

        computed : {
            groups(){
                var groups = this.rows;

                for ( var key in groups )
                {
                    //Is allowed module
                    if ( groups[key].active === true )
                        continue;

                    if ( groups[key].active === false || ! this.hasActiveModule(groups[key].submenu) )
                        delete groups[key];
                }

                return groups;
            },
            hasLanguages(){
                return this.languages.length > 0;
            },
            isActive(){
                return this.$root.languages_active == true ? 1 : 0;
            }
        },

        methods: {
            addActiveTreeClasses(){
                var owner = $('.sidebar li[data-slug="'+this.$router.currentRoute.params.model+'"]');

                owner.parent().addClass('menu-open').css('display', 'block').parents('.treeview').addClass('active');

                $('.sidebar .treeview-menu a').click(function(){
                    $(this).parent().siblings('.active').removeClass('active').find('.menu-open').slideUp();
                });
            },
            getLangName(lang){
                return this.$root.getLangName(lang);
            },
            hasActiveModule(modules){
                for ( var key in modules )
                {
                    if ( modules[key].active === true )
                        return true;

                    if ( modules[key].submenu && this.hasActiveModule(modules[key].submenu) ){
                        return true;
                    }
                }

                return false;
            },
            trans(key){
              return this.$root.trans(key);
            }
        }
    }
</script>