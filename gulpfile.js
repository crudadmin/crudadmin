var elixir = require('laravel-elixir');

//Elixir ueify
require('laravel-elixir-vueify');

//Where sould be compiled assets
var config = require('./config.js');

//Gulp watch for browserify
elixir.config.js.browserify.watchify.options.poll = true;
elixir.config.production = config.production;

elixir(function(mix) {

    if ( config.production === true )
    {
        mix.browserify('src/Resources/js/main.js', 'src/Resources/admin/js', './')
    } else {
        for ( var key in config.paths )
        {
            mix = mix.browserify('src/Resources/js/main.js', config.paths[key], './')
        }
    }

});