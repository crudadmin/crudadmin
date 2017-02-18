<script>
    import Sidebar from './Sidebar/Sidebar.vue';

    export default {
        init(layout){
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
                        requests: layout.requests,
                        user : layout.user,
                        models: layout.models,
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

                components: {
                    Sidebar,
                },

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
                        return this.user.avatar != null ? this.user.avatar : this.$http.options.root + '/../assets/admin/dist/img/avatar.png';
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

                        return 'Administrátor';
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

                        return this.alert;
                    },
                    closeAlert(callback){
                        for ( var key in this.alert )
                            this.alert[key] = null;

                        if ( typeof callback == 'function' )
                            callback.call(this);
                    },
                    arrorAlert(callback){
                        this.openAlert('Upozornenie', 'Nastala nečakana chyba, skúste neskôr prosím.', 'danger', null, callback ? callback : function(){});
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
                            return this.$root.openAlert('Upozornenie!', 'Záznam neexistuje, pravdepodobne už bol vymazaný.', 'warning');
                        }

                        //If has been client logged off
                        if ( response.status == 401 )
                        {
                            return this.$root.openAlert('Upozornenie!', 'Boli ste automatický odhlásený. Prosím, znova sa prihláste.', 'warning', null, function(){
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
                    getModelProperty(model, key){
                        var path = key.split('.');

                        if ( ! model )
                            return null;

                        for ( var i = 0; i < path.length; i++ )
                        {
                            if ( ! ( path[i] in model ) )
                            {
                                return null;
                            }

                            model = model[path[i]];
                        }

                        return model;
                    }
                }
            }
        }
    }
</script>