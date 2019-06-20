//Router
import VueRouter from 'vue-router';

//Layouts
import DashboardView from './components/Views/DashBoardView.vue';
import BasePageView from './components/Views/BasePageView.vue';

var Router = new VueRouter({
    routes : [
        {
            path : '*',
            component: DashboardView,
        },
        {
            path : '/page/:model',
            name : 'admin-model',
            component: BasePageView,
        }
    ]
});

export default Router;