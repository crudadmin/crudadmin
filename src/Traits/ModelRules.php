<?php

namespace Admin\Traits;

use Admin;

trait ModelRules
{
    /*
     * Model backup
     */
    private $backup_exists = null;

    /*
     * Backup of original fields
     */
    private $backup_original = null;

    /*
     * Returns cached admin rule class
     */
    protected function getCachedAdminRuleClass($class)
    {
        return Admin::cache($this->getTable() . $class, function() use ( $class ) {
            return new $class($this);
        });
    }

    /*
     * Return and fire admin rules
     */
    public function getAdminRules($callback)
    {
        if ( $this->rules && is_array($this->rules) )
        {
            foreach ($this->rules as $class)
            {
                $rule = $this->getCachedAdminRuleClass($class);

                $callback($rule);
            }
        }

        return null;
    }

    /*
     * Check if rule can be initialized in interface types
     */
    private function canRunRule($rule, $saved = false)
    {
        //If is not admin interface allowed, skip rules
        if ( Admin::isAdmin() && property_exists($rule, 'admin') && $rule->admin === false )
            return false;

        //If is not frontend interface allowed, skip rules
        if ( Admin::isFrontend() && (!property_exists($rule, 'frontend') || $rule->frontend === false) )
            return false;

        return true;
    }

    private function isDeletingRow()
    {
        return $this->exists && $this->deleted_at && ! $this->getOriginal('deleted_at');
    }

    /*
     * Firing methods before save/create method state
     * good for validation, rules. Method need's to support
     * reverse versions of naming for old conventions of rules.
     */
    private function beforeSaveMethods($rule, $rules)
    {
        if ( method_exists($rule, 'fire') )
            $rule->fire($this);

        if ( in_array('creating', $rules) )
            foreach (['create', 'creating'] as $method)
                if ( method_exists($rule, $method) && ! $this->exists )
                    $rule->{$method}($this);

        if ( in_array('updating', $rules) )
            foreach (['update', 'updating'] as $method)
                if ( method_exists($rule, $method) && $this->exists )
                    $rule->{$method}($this);

        if ( in_array('deleting', $rules) )
            foreach (['delete', 'deleting'] as $method)
                if ( method_exists($rule, $method) && $this->isDeletingRow() )
                    $rule->{$method}($this);
    }

    /*
     * Firing methods after save method
     * good for receiving increment of id
     */
    private function afterSaveMethods($rule, $rules, $exists)
    {
        if ( method_exists($rule, 'fired') )
            $rule->fired($this);

        if ( in_array('created', $rules) )
            if ( method_exists($rule, 'created') && ! $exists )
                $rule->created($this);

        if ( in_array('updated', $rules) )
            if ( method_exists($rule, 'updated') && $exists )
                $rule->updated($this);

        if ( in_array('deleted', $rules) )
            if ( method_exists($rule, 'deleted') )
                $rule->deleted($this);
    }

    /*
     * Clone and save original exist state for correct choosting of created and updated state
     */
    protected function backupExistsProperty()
    {
        $this->backup_exists = $this->exists;
    }

    /*
     * Clone and save original fields for restoring them in the future
     */
    public function backupOriginalAttributes()
    {
        return $this->backup_original = $this->getOriginal() ?: [];
    }

    /*
     * Restore saved original backup
     */
    public function restoreOriginalAttributes($original = null)
    {
        return $this->original = is_array($original)
                                    ? $original
                                    : ($this->backup_original ?: []);
    }

    /*
     * Validate admin rules, on update, delete, create
     */
    public function checkForModelRules($rules = [], $saved = false)
    {
        //We ned save first backup state, because multiple rules can modify this value during state
        //for example when some of rule call save method, then backuped value will be resetted
        $exists = $this->backup_exists;

        //Get backup original, which will not be modified after saving state in rules
        $original = $this->backup_original ?: [];

        $this->getAdminRules(function($rule) use($rules, $saved, $exists, $original) {
            //Check if rule can be initialized
            if ( ! $this->canRunRule($rule, $saved) )
                return;

            //Methods after saved
            if ( $saved === true ) {
                //When need restore original attributes values from state before save,
                //because sometimes in rules we want know old value. When we are creating new row
                //then we need reset original value, because no previous state does not exist.
                $this->restoreOriginalAttributes($original);

                //Run rules
                $this->afterSaveMethods($rule, $rules, $exists);

                //Sync original values
                $this->syncOriginal();
            } else {
                $this->beforeSaveMethods($rule, $rules);
            }
        });
    }
}