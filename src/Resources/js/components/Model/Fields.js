var Fields = (Model) => {
    /*
     * Hide input
     */
    Model.prototype.hideFromForm = function(key, value){
        this.fields[key].hideFromForm = value;
    }

    /*
     * Remove input
     */
    Model.prototype.removeFromForm = function(key, value){
        this.fields[key].removeFromForm = value;
    }

};

export default Fields;