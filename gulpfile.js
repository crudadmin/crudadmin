var elixir = require('laravel-elixir');

//Elixir ueify
require('laravel-elixir-vue-2');

//Where sould be compiled assets
var config = require('./config.js');

//Gulp watch for browserify
// elixir.config.js.browserify.watchify.options.poll = true;

//Set production for no config paths
if ( elixir.config.production === false && config.paths.length == 0 )
    elixir.config.production = true;

elixir(function(mix) {

    if ( elixir.config.production === true )
    {
        mix.browserify('src/Resources/js/main.js', 'src/Resources/admin/js', './')
    } else {
        for ( var key in config.paths )
        {
            mix = mix.browserify('src/Resources/js/main.js', config.paths[key], './')
        }
    }

});