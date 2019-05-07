const Request = () => {};

Request.prototype.get = function(type, params){
    var url = this[type];

    if ( ! params )
        return url;

    for ( var key in params )
    {
        url = url.replace(':'+key, params[key]);
    }

    return url;
}

/*
 * Bind given model properties
 */
const RequestHelper = function(request){
    var core = new Request;

    //Copy all given model attributes
    for ( var key in request )
        core[key] = request[key];

    return core;
}

export default RequestHelper;