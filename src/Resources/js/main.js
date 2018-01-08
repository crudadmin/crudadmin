//Requires
var Vue = require('vue');
var VueRouter = require('vue-router');

require('jquery-form/jquery.form.js');

window._ = require('lodash');
window.moment = require('moment');
window._ = require('lodash');

//Uses
Vue.use(require('vue-resource'));
Vue.use(require('vue-drag-and-drop'));
Vue.use(VueRouter);

// Components
import BaseComponent from './components/BaseComponent.vue';

//Layouts
import DashboardView from './components/views/DashboardView.vue';
import BasePageView from './components/views/BasePageView.vue';

/*
 * App root
 */
var router = new VueRouter();

/*
 * Requests settings
 */
(function(){
    Vue.http.options.root = $('meta[name="root"]').attr('content');

    window.reloadCSRFToken = function(token)
    {
        Vue.http.options.headers = {
            'X-CSRF-TOKEN' : token,
        };

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': token,
            }
        });
    }

    reloadCSRFToken($('meta[name="csrf-token"]').attr('content'));
})();

/*
 * Initial request for administration data
 */
Vue.http.get( 'api/layout' ).then(function(response){

    var routes = {},
        layout = response.data;

    for ( var key in layout.models )
    {
        var models = [];

        //Ges all models, also from group
        if ( key.substr(0, 1) == '$' )
        {
            for ( var subkey in layout.models[key].submenu )
            {
                models.push( layout.models[key].submenu[subkey] );
            }
        } else {
            models.push( layout.models[key] );
        }

        //Register models
        for ( var i = 0; i < models.length; i++ ){

            if ( models[i].active == false )
                continue;

            //Prida sa adresa modela do route systemu
            routes[ '/' + models[i].slug ] = {
                name : models[i].slug,
                component: {
                    props : ['langid'],

                    'template' : '<base-page-view :langid="langid"></base-page-view>',

                    data : function(row){
                        return function(){
                            return {
                                model : row,
                            };
                        };
                    }(models[i]),

                    //Subkomponenta
                    components : { BasePageView },
                },
            }
        }
    }

    routes['*'] = {
        component: DashboardView,
    };

    //Mapping routes
    router.map( routes );

    //Initialize custom componenets
    var app = Vue.extend( BaseComponent.init( layout ) );

    //Init app
    router.start(app, '#app');

}).catch(function(e){
    alert("app error, maybe forgot\nphp artisan admin:update ?");

    console.log(e);
});