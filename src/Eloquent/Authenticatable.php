<?php

namespace Admin\Eloquent;

use Admin;
use Admin\Admin\Rules\OnAdminUpdateRule;
use Admin\Eloquent\Concerns\Auth\HasAdminRoles;
use Admin\Eloquent\Concerns\Auth\HasAutoLogoutTrait;
use Admin\Eloquent\Concerns\Auth\HasLoginVerificator;
use Admin\Eloquent\Modules\VerificationModule;

class Authenticatable extends BaseAuthenticatable
{
    use HasAdminRoles, HasAutoLogoutTrait, HasLoginVerificator;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->modules[] = VerificationModule::class;
    }

    public function setRulesProperty($rules = [])
    {
        return array_merge($rules ?: [], [
            OnAdminUpdateRule::class,
        ]);
    }

    public function mutateFields($fields)
    {
        parent::mutateFields($fields);

        //If has logout user feature
        if ( $this->hasAutoLogoutSupport() ) {
            $fields->push([
                'logout_date' => 'name:Odhlásiť od dátumu|type:datetime|inaccessible',
            ]);
        }

        /*
         * If is enabled admin groups
         */
        if ($this->hasAdminRoles()) {
            $fields->push([
                'permissions' => 'name:admin::admin.super-admin|type:checkbox|default:0|tooltip:'.$this->getFullAccessMessage().'|hasNotAccess:full_access,invisible',
                'roles' => 'name:admin::admin.admin-group|hideFromFormIf:permissions,1|belongsToMany:admins_roles,name|canAdd|hasNotAccess:roles,invisible',
            ]);
        }

        /*
         * Added user language preference
         */
        if ( Admin::isEnabledAdminLocalization() ){
            $fields->push([
                'language' => 'name:Predvolený jazyk|belongsTo:admin_languages,name|invisible'
            ]);
        }

        $fields->push([
            'remember_token' => 'name:Remember token|max:100|removeFromForm|inaccessible',
        ]);
    }

    /*
     * Checks if is enabled user
     */
    public function isEnabled()
    {
        //If field enabled is present.
        if ( $this->hasEnabledSupport() ){
            return $this->enabled === true;
        }

        return true;
    }

    public function hasEnabledSupport()
    {
        return $this->getField('enabled') ? true : false;
    }

    public function getAvatarThumbnailAttribute()
    {
        $image = $this->avatar ?: $this->photo ?: $this->image;

        return $image ? $image->resize(100, 100)->url : null;
    }

    public function setAuthResponse()
    {
        $this->append([
            'avatarThumbnail',
        ]);

        if ($this->hasAdminRoles()) {
            $this->load('roles');
        }

        return $this;
    }
}
