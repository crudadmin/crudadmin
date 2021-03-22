<template>
    <div class="form-group userRolesFormGroup">
        <input type="hidden" :name="field_key" :value="field.value" class="form-control">
        <table class="table">
            <thead>
                <th>{{ trans('roles-module') }}</th>
                <th v-for="(permission, permission_key) in getBasePermissions">{{ permission.name }}</th>
                <th>{{ _('Ostatné') }}</th>
                <th>{{ trans('roles-all') }}</th>
            </thead>
            <tbody>
                <tr v-for="row in modelTree">
                    <td>
                        <span :style="{ marginLeft : (row.depth * 20)+'px' }">{{ row.name }}</span>
                    </td>
                    <td v-for="(permission, permission_key) in getBasePermissions">
                        <label class="checkbox" v-if="hasModelPermission(row, permission_key)" data-toggle="tooltip" :title="permissionTitle(row, permission_key)">
                            <input type="checkbox" @change="togglePermissionValue(row, permission_key)" class="ios-switch" :class="checkboxColor(row, permission_key)" :checked="hasTurnedPermission(row.key, permission_key)">
                            <div><div></div></div>
                        </label>
                    </td>
                    <td>
                        <div class="userRolesFormGroup__other" v-if="hasOtherPermissions(row)">
                            <i class="fas fa-ellipsis-h" :class="{ active : hasActiveOneOtherRoles(row), activeAll : hasActiveAllOtherRoles(row) }"></i>

                            <div class="userRolesFormGroup__other__wrapper">
                                <ul>
                                    <li v-for="(permission, permission_key) in getOtherPermissions(row)">
                                        <span @click="togglePermissionValue(row, permission_key)">{{ permission.name }}</span>
                                        <label class="checkbox" v-if="hasModelPermission(row, permission_key)" data-toggle="tooltip" :title="permissionTitle(row, permission_key)">
                                            <input type="checkbox" @change="togglePermissionValue(row, permission_key)" class="ios-switch" :class="checkboxColor(row, permission_key)" :checked="hasTurnedPermission(row.key, permission_key)">
                                            <div><div></div></div>
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>
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

    data(){
        return {
            baseFields : ['read', 'insert', 'update', 'publishable', 'delete'],
            staticFields : ['all'],
        }
    },

    computed: {
        permissions(){
            let permissions = JSON.parse(this.field.value||'{}');

            return _.isArray(permissions) ? {} : permissions;
        },
        getBasePermissions(){
            var tree = this.modelTree,
                permissions = {};

            for ( var i = 0; i < tree.length; i++ ) {
                for ( var key in tree[i].permissions ) {
                    //Allow only base roles
                    if ( this.baseFields.indexOf(key) > -1 && tree[i].permissions[key].name ){
                        permissions[key] = tree[i].permissions[key];
                    }
                }
            }

            return permissions;
        },
        modelTree(){
            var tree = this.model.getData('admin_tree'),
                array = [],
                assigned = [];

            //We need loop 2 times, because some modules may not assign when migration_date is in wrong order
            for ( var a = 0; a <= 2; a++ ) {
                for ( var key in tree ) {
                    if ( assigned.indexOf(key) > -1 ){
                        continue;
                    }

                    var row = tree[key];

                    if ( row.tree.length == 0 ) {
                        array.push({
                            name : row.name,
                            key : key,
                            depth : 0,
                            tree : row.tree,
                            permissions : row.permissions,
                        });

                        assigned.push(key);
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

                                assigned.push(key);
                            }
                        }

                        array = newArray;
                    }
                }
            }

            return array;
        },
    },

    methods: {
        getOtherPermissions(row){
            var other = {};

            for ( var key in row.permissions ){
                if ( this.baseFields.indexOf(key) === -1 && this.staticFields.indexOf(key) == -1 ){
                    other[key] = row.permissions[key];
                }
            }

            return other;
        },
        hasOtherPermissions(row){
            return Object.keys(this.getOtherPermissions(row)).length > 0;
        },
        checkboxColor(row, permission_key){
            if ( row.permissions[permission_key].danger == true ) {
                return ['red'];
            }

            return ['green'];
        },
        exceptStatic(groups){
            groups = _.cloneDeep(groups);

            for ( var i = 0; i < this.staticFields.length; i++ ){
                if ( groups[this.staticFields[i]] ) {
                    delete groups[this.staticFields[i]];
                }
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
        hasAllPermissionsTurnedOn(row, otherPermissions){
            var permissions = this.permissions,
                onCheck = this.exceptStatic(otherPermissions||row.permissions);

            for ( var key in onCheck ) {
                if ( ! this.hasTurnedPermission(row.key, key) ){
                    return false;
                }
            }

            return true;
        },
        hasActiveOneOtherRoles(row){
            var otherPermissions = this.getOtherPermissions(row);

            //If is checked at least one permissions
            for ( var key in otherPermissions ){
                if ( this.hasTurnedPermission(row.key, key) ) {
                    return true;
                }
            }

            return false;
        },
        hasActiveAllOtherRoles(row){
            var otherPermissions = this.getOtherPermissions(row);

            return this.hasAllPermissionsTurnedOn(row, otherPermissions);
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