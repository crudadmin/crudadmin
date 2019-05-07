require('jquery-form/jquery.form.js');

window.axios = require('axios');
window._ = require('lodash');
window.moment = require('moment');
window._ = require('lodash');
window.md5 = require('js-md5');

/**
 * Set axios admin root
 */
axios.defaults.baseURL = $('meta[name="root"]').attr('content');

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': token.content
        }
    });
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}