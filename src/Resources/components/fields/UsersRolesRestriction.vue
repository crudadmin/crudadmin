<template>
    <div class="form-group userRolesFormGroup">
        <input type="hidden" :name="field_key" :value="field.value" class="form-control">
        <table class="table">
            <thead>
                <th>{{ trans('roles-module') }}</th>
                <th v-for="(permission, permission_key) in getRolesPermissions">{{ permission.name }}</th>
                <th>{{ trans('roles-all') }}</th>
            </thead>
            <tbody>
                <tr v-for="row in modelTree">
                    <td>
                        <span :style="{ marginLeft : (row.depth * 20)+'px' }">{{ row.name }}</span>
                    </td>
                    <td v-for="(permission, permission_key) in getRolesPermissions">
                        <label class="checkbox" v-if="hasModelPermission(row, permission_key)" data-toggle="tooltip" :title="permissionTitle(row, permission_key)">
                            <input type="checkbox" @change="togglePermissionValue(row, permission_key)" class="ios-switch" :class="checkboxColor(row, permission_key)" :checked="hasTurnedPermission(row.key, permission_key)">
                            <div><div></div></div>
                        </label>
                    </td>
                    <td>
                        <label class="checkbox" data-toggle="tooltip" :title="permissionTitle(row, 'all')">
                            <input type="checkbox" @click="toggleAll(row)" :checked="hasAllPermissionsTurnedOn(row)" class="ios-switch green" value="1">
                            <div><div></div></div>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['field_key', 'field', 'row', 'model'],

    computed: {
        permissions(){
            return JSON.parse(this.field.value||'{}');
        },
        getRolesPermissions(){
            var tree = this.modelTree,
                permissions = {};

            for ( var i = 0; i < tree.length; i++ ) {
                for ( var key in this.exceptGroups(tree[i].permissions) ) {
                    permissions[key] = tree[i].permissions[key];
                }
            }

            return permissions;
        },
        modelTree(){
            var tree = this.model.admin_tree,
                array = [];

            for ( var key in tree ) {
                var row = tree[key];

                if ( row.tree.length == 0 ) {
                    array.push({
                        name : row.name,
                        key : key,
                        depth : 0,
                        tree : row.tree,
                        permissions : row.permissions,
                    });
                }

                else {
                    var arrayLength = array.length,
                        newArray = _.cloneDeep(array);

                    for ( var i = 0; i < arrayLength; i++ ) {
                        if ( array[i].key == row.tree[row.tree.length - 1] ) {
                            newArray.splice(i + 1, 0, {
                                name : row.name,
                                key : key,
                                depth : row.tree.length,
                                tree : row.tree,
                                permissions : row.permissions,
                            });
                        }
                    }

                    array = newArray;
                }
            }

            return array;
        },
    },

    methods: {
        checkboxColor(row, permission_key){
            if ( row.permissions[permission_key].danger == true ) {
                return ['red'];
            }

            return ['green'];
        },
        exceptGroups(groups){
            groups = _.cloneDeep(groups);

            if ( groups.all ) {
                delete groups.all;
            }

            return groups;
        },
        permissionTitle(row, permission_key){
            var obj = row.permissions[permission_key];

            if ( ! obj ){
                return;
            }

            return (obj.name ? obj.name : '') + (obj.title ? ((obj.name ? ' - ' : '') + obj.title) : '');
        },
        hasModelPermission(row, permission_key){
            return permission_key in row.permissions;
        },
        setPermissions(permissions){
            this.field.value = JSON.stringify(permissions);
        },
        togglePermissionValue(row, permission_key){
            var permissions = this.permissions;

            //If model table is missing
            if ( !(row.key in permissions) ) {
                permissions[row.key] = {};
            }

            permissions[row.key][permission_key] = permissions[row.key][permission_key] ? false : true;

            this.setPermissions(permissions);
        },
        hasAllPermissionsTurnedOn(row){
            var permissions = this.permissions;

            for ( var key in this.exceptGroups(row.permissions) ) {
                if ( ! this.hasTurnedPermission(row.key, key) ){
                    return false;
                }
            }

            return true;
        },
        toggleAll(row){
            var permissions = this.permissions,
                hasAll = this.hasAllPermissionsTurnedOn(row);

            if ( !(row.key in permissions) ) {
                permissions[row.key] = {};
            }

            for ( var key in row.permissions ) {
                permissions[row.key][key] = !hasAll;
            }

            this.setPermissions(permissions);
        },
        hasTurnedPermission(key, permission_key){
            var permissions = this.permissions;

            return !(
                !(key in permissions)
                || !(permission_key in permissions[key])
                || permissions[key][permission_key] == false
            );
        }
    }
}
</script>