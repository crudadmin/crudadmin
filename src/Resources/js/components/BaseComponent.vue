<script>
    import Sidebar from './Sidebar/Sidebar.vue';

    export default {
        init(layout){
            //Replace requests paths
            for (var key in layout.requests)
            {
                layout.requests[key] = layout.requests[key].replace(':model', '{model}').replace(':id', '{id}').replace(':subid', '{subid}');
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
                    }
                }
            }
        }
    }
</script>