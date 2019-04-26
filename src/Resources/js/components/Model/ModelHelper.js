import Tabs from './Tabs.js';
import Fields from './Fields.js';

const Model = () => {};

var extensions = [
    Tabs,
    Fields,
];

/*
 * Bind given model properties
 */
const ModelHelper = function(data){
    var core = new Model;

    //Copy all given model attributes
    for ( var key in data )
        core[key] = data[key];

    //Install all extensions
    for ( var key in extensions )
        extensions[key](Model);

    return core;
}

export default ModelHelper;