<?php

namespace Admin\Eloquent\Concerns;

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
     * Disable all admin rules
     */
    private $disableAllAdminRules = false;

    /*
     * Which rules are being performed
     */
    private $performingRuleMethods = [];

    /*
     * Returns cached admin rule class
     */
    protected function getCachedAdminRuleClass($class)
    {
        return Admin::cache($this->getTable().$class, function () use ($class) {
            return new $class($this);
        });
    }

    /*
     * Return and fire admin rules
     */
    public function getAdminRules($callback)
    {
        $rules = $this->getProperty('rules');

        if ($rules && is_array($rules)) {
            foreach ($rules as $class) {
                $rule = $this->getCachedAdminRuleClass($class);

                $callback($rule);
            }
        }
    }

    /**
     * Disable all admin rules
     *
     * @param  bool  $state
     * @return this
     */
    public function disableAllAdminRules($state = true)
    {
        $this->disableAllAdminRules = $state;

        return $this;
    }

    /*
     * Check if rule can be initialized in interface types
     */
    private function canRunRule($rule)
    {
        //If rules are disabled
        if ( $this->disableAllAdminRules === true ) {
            return false;
        }

        //If is not admin interface allowed, skip rules
        if (Admin::isAdmin() && property_exists($rule, 'admin') && $rule->admin === true) {
            return true;
        }

        //If is not frontend interface allowed, skip rules
        if (Admin::isFrontend() && property_exists($rule, 'frontend') && $rule->frontend === true) {
            return true;
        }

        return false;
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
        if (method_exists($rule, $method = 'fire')) {
            $this->runRuleMethod($rule, $method);
        }

        if (in_array('creating', $rules)) {
            foreach (['create', 'creating'] as $method) {
                if (method_exists($rule, $method) && ! $this->exists) {
                    $this->runRuleMethod($rule, $method);
                }
            }
        }

        if (in_array('updating', $rules)) {
            foreach (['update', 'updating'] as $method) {
                if (method_exists($rule, $method) && $this->exists) {
                    $this->runRuleMethod($rule, $method);
                }
            }
        }

        if (in_array('deleting', $rules)) {
            foreach (['delete', 'deleting'] as $method) {
                if (method_exists($rule, $method) && $this->isDeletingRow()) {
                    $this->runRuleMethod($rule, $method);
                }
            }
        }

        if (in_array($method = 'unpublishing', $rules)) {
            if (method_exists($rule, $method)) {
                $this->runRuleMethod($rule, $method);
            }
        }

        if (in_array($method = 'publishing', $rules)) {
            if (method_exists($rule, $method)) {
                $this->runRuleMethod($rule, $method);
            }
        }
    }

    /*
     * Firing methods after save method
     * good for receiving increment of id
     */
    private function afterSaveMethods($rule, $rules, $exists)
    {
        if (method_exists($rule, $method = 'fired')) {
            $this->runRuleMethod($rule, $method);
        }

        if (in_array($method = 'created', $rules)) {
            if (method_exists($rule, $method) && ! $exists) {
                $this->runRuleMethod($rule, $method);
            }
        }

        if (in_array($method = 'updated', $rules)) {
            if (method_exists($rule, $method) && $exists) {
                $this->runRuleMethod($rule, $method);
            }
        }

        if (in_array($method = 'deleted', $rules)) {
            if (method_exists($rule, $method)) {
                $this->runRuleMethod($rule, $method);
            }
        }

        if (in_array($method = 'unpublished', $rules)) {
            if (method_exists($rule, $method)) {
                $this->runRuleMethod($rule, $method);
            }
        }

        if (in_array($method = 'published', $rules)) {
            if (method_exists($rule, $method)) {
                $this->runRuleMethod($rule, $method);
            }
        }
    }

    private function runRuleMethod($rule, $method)
    {
        $this->performingRuleMethods[] = $method;

        $rule->{$method}($this);

        $this->performingRuleMethods = array_diff($this->performingRuleMethods, [$method]);
    }

    public function isRuleMethodPerforming($methods)
    {
        return count(array_intersect($this->performingRuleMethods, $methods)) > 0;
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
        $this->backup_original = $this->getRawOriginal() ?: [];

        return $this->getOriginal();
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

        $this->getAdminRules(function ($rule) use ($rules, $saved, $exists, $original) {
            //Check if rule can be initialized
            if (! $this->canRunRule($rule, $saved)) {
                return;
            }

            //Methods after saved
            if ($saved === true) {
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
