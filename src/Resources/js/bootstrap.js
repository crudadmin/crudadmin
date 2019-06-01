window.$ = window.jQuery = require('jquery');

require('jquery-form/jquery.form.js');
require('bootstrap-sass');
require('jquery-datetimepicker');

require('../admin/plugins/lightbox/lightbox.min.js');
require('../admin/plugins/chosen/chosen.jquery.min.js');
require('../admin/plugins/chosen-order/chosen.order.jquery.min.js');
require('../admin/dist/js/app.min.js');
require('../admin/dist/js/main.js');

window.axios = require('axios');
window._ = require('lodash');
window.moment = require('moment');
window.md5 = require('js-md5');

/**
 * Set axios admin root
 */
axios.defaults.baseURL = $('meta[name="root"]').attr('content');