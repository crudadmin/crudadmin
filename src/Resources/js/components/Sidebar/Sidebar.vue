<template>
    <ul class="sidebar-menu">
        <li class="header">
            {{ hasLanguages && isActive ? 'Jazykova verzia' : 'Navigácia' }}
            <div v-if="hasLanguages && isActive" class="form-group language_select" data-toggle="tooltip" title="" data-original-title="Zmena jazykovej mutácie. Pozor! Zmena jazyka môže zahodiť Vaše aktuálne rozpísane zmeny.">
                <select v-model="langid" class="form-control">
                    <option v-for="language in languages" value="{{ language.id }}">{{ language.name }}</option>
                </select>
            </div>
        </li>
    </ul>

    <!-- Sidebar Menu -->
    <ul class="sidebar-menu">
        <sidebar-row v-for="row in rows | groups" :row="row"></sidebar-row>
    </ul>

      <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</template>

<script>
    import SidebarRow from './SidebarRow.vue';

    export default {
        props: ['rows', 'languages', 'langid'],

        components: { SidebarRow },

        filters: {
            groups(array){
                return array;
            }
        },

        computed : {
            hasLanguages(){
                return this.languages.length > 0;
            },
            isActive(){
                return this.$root.languages_active == true ? 1 : 0;
            }
        },

        ready(){
            var owner = $('.sidebar li[data-slug="'+this.$router._currentTransition.to.name+'"]').parent().parent();

            if ( owner.hasClass('treeview') )
                owner.addClass('active');
        }
    }
</script>