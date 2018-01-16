<script>
    import Sidebar from './Sidebar/Sidebar.vue';
    import License from './Partials/License.vue';
    import CheckAssetsVersion from './Partials/CheckAssetsVersion.vue';

    export default {
        init(layout, models_list){
            //Replace requests paths
            var replace = ['model', 'parent', 'id', 'subid', 'limit', 'page', 'langid', 'count'];

            for (var key in layout.requests)
            {
                for ( var i = 0; i < replace.length; i++ )
                    layout.requests[key] = layout.requests[key].replace(':'+replace[i], '{'+replace[i]+'}');
            }

            return {
                data : function(){
                    return {
                        version : layout.version,
                        version_assets : layout.version_assets,
                        license_key : layout.license_key,
                        requests: layout.requests,
                        user : layout.user,
                        models: layout.models,
                        models_list : this.getRecursiveModels(models_list),
                        localization: layout.localization,
                        languages: layout.languages,
                        language_id : null,
                        languages_active : false,
                        alert: {
                            type : null, // success,danger,warning...
                            title : null,
                            message : null,
                            success: null,
                            close: null,
                        }
                    }
                },

                watch : {
                    language_id : function(id){
                        localStorage.language_id = id;
                    }
                },

                components: { Sidebar, License, CheckAssetsVersion },

                created(){
                    this.bootLanguages();
                },

                ready(){
                    this.checkAlertEvents();
                },

                computed : {
                    canShowAlert(){
                        return this.alert.title != null && this.alert.message != null;
                    },
                    getAvatar()
                    {
                        return this.user.avatar != null ? this.user.avatar : this.$http.options.root + '/../'+window.crudadmin.path+'/dist/img/avatar5.png';
                    },
                    getPermissions()
                    {
                        if ( 'admins_groups' in this.user )
                        {
                            var permissions = [];

                            for (var i = 0; i < this.user.admins_groups.length; i++){
                                permissions.push( this.user.admins_groups[i].name );
                            }

                            if ( permissions.length > 0 )
                                return permissions.join(', ');
                        }

                        return this.$root.trans('admin-user');
                    }
                },

                methods : {
                    openAlert(title, message, type, success, close){

                        if ( !type )
                            type = 'success';

                        if ( type == 'error' )
                            type = 'danger';

                        this.alert.type = type;
                        this.alert.title = title;
                        this.alert.message = message;
                        this.alert.success = success;
                        this.alert.close = close;

                        //After opening alert focus close button of this alert for disabling sending form...
                        setTimeout(function(){
                            $('.modal .modal-footer button:last-child').focus();
                        }, 100);

                        return this.alert;
                    },
                    closeAlert(callback){
                        for ( var key in this.alert )
                            this.alert[key] = null;

                        if ( typeof callback == 'function' )
                            callback.call(this);
                    },
                    arrorAlert(callback){
                        this.openAlert(this.$root.trans('warning'), this.$root.trans('unknown-error'), 'danger', null, callback ? callback : function(){});
                    },
                    checkAlertEvents(){
                        var _this = this;

                        $(window).keyup(function(e){

                            //If is opened alert
                            if ( _this.canShowAlert !== true )
                                return;

                            if ( e.keyCode == 13 )
                                _this.closeAlert( _this.alert.success || _this.alert.close );

                            if ( e.keyCode == 27 )
                                _this.closeAlert( _this.alert.close );

                        })
                    },
                    timeFormat(time){
                        var t = time.split(/[- :]/);

                        // Apply each element to the Date function
                        var d = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);

                        var pad = function pad(d) {
                            return (d < 10) ? '0' + d.toString() : d.toString();
                        };

                        return pad( d.getDate() ) + '.' + pad ( d.getMonth() ) + '.' + pad( d.getFullYear() ) + ' ' + pad( d.getHours() ) + ':' + pad( d.getMinutes() ) + ':' + pad( d.getSeconds() );
                    },
                    bootLanguages(){
                        if ( this.languages.length == 0 )
                            return;

                        if ( ! ('language_id' in localStorage) || !$.isNumeric(localStorage.language_id) )
                            localStorage.language_id = this.languages[0].id;

                        this.language_id = localStorage.language_id;
                    },
                    //Check for all error response in all requests
                    errorResponseLayer(response, code, callback)
                    {
                        //Fix for jquery response
                        if ( 'responseJSON' in response )
                            response.data = response.responseJSON;

                        //If error response comes with some message information, then display it
                        if ( response.data && response.data.message && response.data.title && response.data.type )
                        {
                            return this.$root.openAlert(response.data.title, response.data.message, response.data.type, null, function(){
                                if ( response.status == 401 )
                                {
                                    window.location.reload();
                                }
                            });
                        }

                        if ( response.status == 404 )
                        {
                            return this.$root.openAlert(this.$root.trans('warning'), this.$root.trans('row-error'), 'warning');
                        }

                        //If has been client logged off
                        if ( response.status == 401 )
                        {
                            return this.$root.openAlert(this.$root.trans('warning'), this.$root.trans('auto-logout'), 'warning', null, function(){
                                window.location.reload();
                            });
                        }

                        //Callback on code
                        if ( callback && (code === response.status || code === null) )
                            return callback(response);

                        //Unknown error
                        this.$root.arrorAlert();
                    },
                    //Check specifics property in model
                    getModelProperty(model, key, value){
                        var path = key.split('.');

                        if ( ! model )
                            return null;

                        for ( var i = 0; i < path.length; i++ )
                        {
                            if ( ! ( path[i] in model ) )
                            {
                                return value ? value : null;
                            }

                            model = model[path[i]];
                        }

                        return model;
                    },
                    /*
                     * Get translates
                     */
                    trans(key){
                        if ( key in this.localization )
                            return this.localization[key];

                        return key;
                    },
                    /*
                    * Returns correct values into multilangual select
                    */
                    languageOptions(array, field, filter){
                        var key,
                            relation,
                            field_key,
                            matched_keys,
                            items = [],
                            hasFilter = filter && Object.keys(filter).length > 0;

                        if ( field && (relation = field['belongsTo']||field['belongsToMany']) && (field_key = relation.split(',')[1]) ){
                          matched_keys = field_key.replace(/\\:/g, '').match(new RegExp(/[\:^]([0-9,a-z,A-Z$_]+)+/, 'g'));
                        }

                        loop1:
                        for ( var key in array )
                        {
                          //If select has filters
                          if ( hasFilter )
                            for ( var k in filter ){
                              if ( array[key][1][k] != filter[k] || array[key][1][k] == null )
                                continue loop1;
                            }

                          //Build value from multiple columns
                          if ( matched_keys )
                          {
                            var value = field_key.replace(/\\:/g, ':');

                            for ( var i = 0; i < matched_keys.length; i++ )
                              value = value.replace(new RegExp(matched_keys[i], 'g'), array[key][1][matched_keys[i].substr(1)]||'');
                          }

                          //Simple value by one column
                          else {
                            //Get value of multiarray or simple array
                            var value = typeof array[key][1] == 'object' && array[key][1]!==null ? array[key][1][field_key] : array[key][1];
                          }

                          items.push([array[key][0], value]);
                        }

                        return items;
                    },
                    getRecursiveModels(models){
                        var data = {};

                        for ( var key in models )
                        {
                            data[models[key].slug] = models[key];

                            if ( Object.keys(models[key].childs).length > 0 )
                                data = _.merge(data, this.getRecursiveModels(models[key].childs));
                        }

                        return data;
                    }
                }
            }
        }
    }
</script>