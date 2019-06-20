require('./bootstrap');

window.Vue = require('vue');
window.eventHub = new Vue();

import VueRouter from 'vue-router';
import VueResource from 'vue-resource'
import Fragment from 'vue-fragment'

//Uses
Vue.use(VueResource);
Vue.use(Fragment.Plugin);
Vue.use(VueRouter);

// Components
import BaseComponent from './components/BaseComponent.js';

//Router
import Router from './router.js';

//Create base VueApp instance
window.VueApp = new Vue(
    BaseComponent(Router)
);