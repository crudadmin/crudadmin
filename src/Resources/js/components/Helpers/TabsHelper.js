var Tabs = (Model) => {
    /*
     * Hide tab
     */
    Model.prototype.showTab = function(key){
        var hidden_tabs = _.cloneDeep(this.hidden_tabs);
            hidden_tabs.splice(this.hidden_tabs.indexOf(key), 1);

        this.hidden_tabs = hidden_tabs;
    }

    /*
     * Show tab
     */
    Model.prototype.hideTab = function(key){
        if ( this.hidden_tabs.indexOf(key) === -1 )
            this.hidden_tabs = _.cloneDeep(this.hidden_tabs).concat(key);
    }

    /*
     * Set visibility tab
     */
    Model.prototype.setTabVisibility = function(key, visible){
        if ( visible === true )
            this.showTab(key);
        else
            this.hideTab(key);
    }
};

export default Tabs;