<script>
    import Sidebar from './Sidebar/Sidebar.vue';
    import License from './Partials/License.vue';
    import CheckAssetsVersion from './Partials/CheckAssetsVersion.vue';

    export default {
        init(layout, models_list, groups_prefix){
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
                        gettext : layout.gettext,
                        locale : layout.locale,
                        dashboard : layout.dashboard,
                        license_key : layout.license_key,
                        requests: layout.requests,
                        user : layout.user,
                        models: layout.models,
                        models_list : this.getRecursiveModels(models_list),
                        localization: layout.localization,
                        languages: layout.languages,
                        language_id : null,
                        languages_active : false,
                        groups_prefix : groups_prefix,
                        alert: {
                            type : null, // success,danger,warning...
                            title : null,
                            message : null,
                            success: null,
                            close: null,
                            component: null,
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

                    //Set datepickers language
                    jQuery.datetimepicker.setLocale(this.locale);
                },

                ready(){
                    this.checkAlertEvents();
                },

                computed : {
                    canShowAlert(){
                        return this.alert.title != null && this.alert.message != null || this.alert.component;
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
                    openAlert(title, message, type, success, close, component){

                        if ( !type )
                            type = 'success';

                        if ( type == 'error' )
                            type = 'danger';

                        this.alert.type = type;
                        this.alert.title = title;
                        this.alert.message = message;
                        this.alert.success = success;
                        this.alert.close = close;

                        this.bindAlertComponent(component);

                        //After opening alert focus close button of this alert for disabling sending form...
                        setTimeout(function(){
                            $('.modal .modal-footer button:last-child').focus();
                        }, 100);

                        return this.alert;
                    },
                    getComponentName(name){
                        return name + 'Alert';
                    },
                    bindAlertComponent(component){
                        this.alert.component = component;

                        if ( component ){
                            var obj;

                            try {
                                obj = (new Function('return '+component.component))();

                                Vue.component(this.getComponentName(component.name), obj);

                                console.log('component registred', this.getComponentName(component.name), component);
                            } catch(error){
                                console.error('Syntax error in component button component.' + "\n", error);

                                this.alert.component = null;
                            }
                        }
                    },
                    closeAlert(callback){
                        if ( typeof callback == 'function' )
                            callback.call(this);

                        for ( var key in this.alert )
                            this.alert[key] = null;
                    },
                    arrorAlert(callback){
                        this.openAlert(this.$root.trans('warning'), this.$root.trans('unknown-error'), 'danger', null, callback ? callback : function(){});
                    },
                    checkAlertEvents(){
                        $(window).keyup(function(e){

                            //If is opened alert
                            if ( this.canShowAlert !== true )
                                return;

                            if ( e.keyCode == 13 )
                                this.closeAlert( this.alert.success || this.alert.close );

                            if ( e.keyCode == 27 )
                                this.closeAlert( this.alert.close );

                        }.bind(this))
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
                            related_field,
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
                            {
                                var related_field = this.models_list[relation.split(',')[0]].fields[matched_keys[i].substr(1)],
                                    option_value = this.getLangValue(array[key][1][matched_keys[i].substr(1)], related_field);

                                value = value.replace(new RegExp(matched_keys[i], 'g'), !option_value && option_value !== 0 ? '' : option_value);
                            }
                          }

                          //Simple value by one column
                          else {
                            if ( field_key )
                                related_field = this.models_list[relation.split(',')[0]].fields[field_key];

                            //Get value of multiarray or simple array
                            var value = typeof array[key][1] == 'object' && array[key][1]!==null ? this.getLangValue(array[key][1][field_key], related_field) : array[key][1];
                          }

                          items.push([array[key][0], value]);
                        }

                        return items;
                    },
                    getLangValue(value, field){
                        if ( field && value && typeof value == 'object' && 'locale' in field )
                        {
                            if ( this.languages[0].slug in value && (value[this.languages[0].slug] || value[this.languages[0].slug] == 0) )
                                return value[this.languages[0].slug];

                            for ( var key in value )
                            {
                                if ( value[key] || value[key] == 0 )
                                    return value[key];
                            }

                            return null;
                        }

                        return value;
                    },
                    getRecursiveModels(models){
                        var data = {};

                        for ( var key in models )
                        {
                            if ( typeof models[key] != 'object' )
                                continue;

                            data[models[key].slug] = models[key];

                            if ( Object.keys(models[key].childs).length > 0 )
                                data = _.merge(data, this.getRecursiveModels(models[key].childs));
                        }

                        return data;
                    },
                    /*
                     * Replace datetime format from PHP to momentjs
                     */
                    fromPHPFormatToMoment(format){
                        var mapObj = { 'd' : 'DD', 'D' : 'ddd', 'j' : 'D', 'l' : 'dddd', 'N' : 'E', 'S' : 'o', 'w' : 'e', 'z' : 'DDD', 'W' : 'W', 'F' : 'MMMM', 'm' : 'MM', 'M' : 'MMM', 'n' : 'M', 't' : '', 'L' : '', 'o' : 'YYYY', 'Y' : 'YYYY', 'y' : 'YY', 'a' : 'a', 'A' : 'A', 'B' : '', 'g' : 'h', 'G' : 'H', 'h' : 'hh', 'H' : 'HH', 'i' : 'mm', 's' : 'ss', 'u' : 'SSS', 'e' : 'zz', 'I' : '', 'O' : '', 'P' : '', 'T' : '', 'Z' : '', 'c' : '', 'r' : '', 'U' : 'X' };

                        var re = new RegExp(Object.keys(mapObj).join("|"),"gi");

                        return format.replace(re, function(match){
                            if ( match in mapObj )
                                return mapObj[match];

                            return match;
                        });
                    },
                    runInlineScripts(layout)
                    {
                        $('<div>'+layout+'</div>').find('script').each(function(){
                            //Run external js
                            if ( $(this).attr('src') ){
                                var js = document.createElement('script');
                                    js.src = $(this).attr('src');
                                    js.type = 'text/javascript';

                                $('body').append(js);
                            }

                            //Run inline javascripts
                            else {
                                try {
                                    var func = new Function($(this).html());

                                    func.call(Vue);
                                } catch(e){
                                    console.error(e);
                                }
                            }
                        });
                    },
                    getLangName(lang){
                        //If language table is also translatable
                        if ( typeof lang.name == 'object' ){
                            return lang.name[Object.keys(lang.name)[0]];
                        }

                        return lang.name;
                    }
                }
            }
        }
    }
</script>