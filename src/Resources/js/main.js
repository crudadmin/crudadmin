//Requires
window.Vue = require('vue');
window.VueRouter = require('vue-router');

require('jquery-form/jquery.form.js');

window._ = require('lodash');
window.moment = require('moment');
window._ = require('lodash');
window.md5 = require('js-md5');

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
        layout = response.data,
        models_list = [],
        groups_prefix = '#$_';

    //Ges all models, also from groups
    var getRecursiveModels = function(key, model){
        var models = [];

        if ( key.substr(0, groups_prefix.length) == groups_prefix )
        {
            for ( var subkey in model.submenu )
            {
                if ( subkey.substr(0, groups_prefix.length) == groups_prefix )
                    models = models.concat(getRecursiveModels(subkey, model.submenu[subkey]));
                else
                    models.push( model.submenu[subkey] );
            }
        } else {
            models.push( model );
        }

        return models;
    }

    for ( var key in layout.models )
    {
        var models = getRecursiveModels(key, layout.models[key]);

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

        models_list = models_list.concat(models);
    }

    routes['*'] = {
        component: DashboardView,
    };

    //Mapping routes
    router.map( routes );

    //Initialize custom componenets
    var app = Vue.extend( BaseComponent.init( layout, models_list, groups_prefix ) );

    //Init app
    router.start(app, '#app');

}).catch(function(e){
    if ( window.crudadmin.dev === true )
        alert("app error, maybe forgot\nphp artisan admin:update ?");

    console.log(e);
});