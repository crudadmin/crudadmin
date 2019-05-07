require('./bootstrap');

window.Vue = require('vue');

import VueRouter from 'vue-router';
import Fragment from 'vue-fragment'

//Uses
Vue.use(require('vue-resource'));
Vue.use(require('vue-drag-and-drop'));
Vue.use(Fragment.Plugin);
Vue.use(VueRouter);

// Components
import BaseComponent from './components/BaseComponent.vue';

//Layouts
import DashboardView from './components/views/DashBoardView.vue';
import BasePageView from './components/views/BasePageView.vue';

var routes = [
    {
        path : '*',
        component: DashboardView,
    },
    {
        path : '/page/:model',
        name : 'admin-model',
        component: BasePageView,
    }
];

var router = new VueRouter({
    routes,
});

//Create base VueApp instance
var BaseApp = BaseComponent.init(router);

//Initialize custom componenets
window.VueApp = new Vue(BaseApp);