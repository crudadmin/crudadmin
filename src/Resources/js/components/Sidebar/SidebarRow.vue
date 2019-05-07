<template>
    <fragment>
        <router-link tag="li" class="treeview" v-if="isActive && !isGroup" data-slug="row.slug" :to="{ name : 'admin-model', params: { model : row.slug }, activeClass : 'active' }">
          <a><i v-bind:class="['fa', row.icon]"></i> <span>{{ row.name }}</span> <i v-if="hasSubmenu" class="fa fa-angle-left pull-right"></i></a>
          <ul v-if="hasSubmenu" class="treeview-menu">
            <sidebar-row v-for="(subrow, key) in row.submenu" :key="key" :row="subrow" :parent="levels"></sidebar-row>
          </ul>
        </router-link>

        <li class="treeview" v-if="isActive && isGroup && hasChilds" data-slug="row.slug" >
          <a><i class="fa" :class="row.icon||'fa-folder-open-o'"></i> <span>{{ row.name }}</span> <i v-if="hasSubmenu" class="fa fa-angle-left pull-right"></i></a>
          <ul v-if="hasSubmenu" class="treeview-menu">
            <sidebar-row v-for="(subrow, key) in row.submenu" :key="key" :row="subrow" :parent="levels"></sidebar-row>
          </ul>
        </li>
    </fragment>
</template>

<script>
    import config from '../../config';

    export default {
        name : 'sidebar-row',
        props: ['row', 'parent'],

        data : function() {
            return {
                levels : [],
            };
        },
        created : function()
        {
            var levels = [];

            if ( this.parent )
            {
                for ( var i = 0; i < this.parent.length; i++ )
                {
                    levels.push( this.parent[i] );
                }
            }

            levels.push( this.row );

            return this.levels = levels;
        },

        computed: {
            hasSubmenu() {
                return this.row.submenu.length !== 0;
            },
            isGroup() {
                return this.row.slug.substr(0, config.groups_prefix.length) == config.groups_prefix;
            },
            hasChilds(){
                for ( var key in this.row.submenu )
                    return true;

                return false;
            },
            isActive(){
                return this.row.active !== false;
            }
        },
    }
</script>