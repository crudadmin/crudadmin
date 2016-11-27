<template>
    <li class="treeview" v-if="!isGroup && isActive" data-slug="{{ row.slug }}" v-link="{ name : row.slug, params: { pageName : row.slug }, activeClass : 'active' }">
      <a><i class="fa fa-link"></i> <span>{{ row.name }}</span> <i v-if="hasSubmenu" class="fa fa-angle-left pull-right"></i></a>
      <ul v-if="hasSubmenu" class="treeview-menu">
        <sidebar-row v-for="subrow in row.submenu" :row="subrow" :parent="levels"></sidebar-row>
      </ul>
    </li>

    <li class="treeview" v-if="isGroup && hasChilds" data-slug="{{ row.slug }}" >
      <a><i class="fa fa-link"></i> <span>{{ row.name }}</span> <i v-if="hasSubmenu" class="fa fa-angle-left pull-right"></i></a>
      <ul v-if="hasSubmenu" class="treeview-menu">
        <sidebar-row v-for="subrow in row.submenu" :row="subrow" :parent="levels"></sidebar-row>
      </ul>
    </li>
</template>

<script>
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
                return this.row.slug.substr(0, 1) == '$';
            },
            isActive() {
                return this.row.active == true;
            },
            hasChilds(){
                for ( var key in this.row.submenu )
                    if ( this.row.submenu[ key ].active == true )
                        return true;

                return false;
            }
        },
    }
</script>