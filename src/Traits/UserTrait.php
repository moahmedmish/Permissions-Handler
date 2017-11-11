<?php

namespace PermissionsHandler\Traits;

use PermissionsHandler;
use Illuminate\Support\Facades\DB;
use PermissionsHandler\Models\Role;
use Illuminate\Support\Facades\Cache;
use PermissionsHandler\Models\Permission;

trait UserTrait
{


    /**
     * many to many relation with PermissionsHandler\Models\Role
     */
    public function roles()
    {
        return $this->belongsToMany(\PermissionsHandler\Models\Role::class);
    }

    /**
     * Get user cached roles
     *
     * @return array
     */
    public function cachedRoles()
    {
        return  Cache::remember(
            $this->getCachePrefix().'_roles',
            config('permissionsHandler.cacheExpiration'),
            function () {
                return  $this->roles->pluck('name', 'id')->toArray();
            }
        );
    }

    /**
     * Get user cached permissions
     *
     * @return array
     */
    public function cachedPermissions()
    {
        $roles = $this->cachedRoles();
        return  Cache::remember(
            $this->getCachePrefix().'_permissions',
            config('permissionsHandler.cacheExpiration'),
            function () use ($roles) {
                return Permission::whereHas(
                    'roles', function ($query) use ($roles) {
                        return $query->whereIn(DB::raw('roles.id'), array_keys($roles));
                    }
                )->pluck('name', 'id')->toArray();
            }
        );
    }

    /**
     * Check if the user has a role
     *
     * @param string $role
     * @return boolean
     */
    public function hasRole($role)
    {
        return in_array($role, $this->cachedRoles());
    }

    /**
     * Check if the user has a permission
     *
     * @param string $permission
     * @return boolean
     */
    public function hasPermission($permission){
        return in_array($permission, $this->cachedPermissions());
    }

    /**
     * Check if the user has a permission, is alias for hasPermission
     *
     * @param string $permission
     * @return boolean
     */
    public function canDo($permission){
        return in_array($permission, $this->cachedPermissions());
    }

    /**
     * Clear user cached roles
     *
     * @return void
     */
    public function clearCachedRoles(){
        Cache::forget($this->getCachePrefix().'_roles');
    }

    /**
     * Clear user cached permissions
     *
     * @return void
     */
    public function clearCachedPermissions(){
        Cache::forget($this->getCachePrefix().'_permissions');
    }

    /**
     * Get the cache prefix, used for caching keys
     *
     * @return string
     */
    public function getCachePrefix()
    {
        return $this->getTable().'_'.$this->id;
    }
}
