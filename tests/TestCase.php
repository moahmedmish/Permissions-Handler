<?php

namespace PermissionsHandler\Tests;

use Monolog\Handler\TestHandler;
use PermissionsHandler\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use PermissionsHandler\Models\Permission;
use PermissionsHandler\Tests\Models\User;
use Orchestra\Testbench\TestCase as Orchestra;
use PermissionsHandler\PermissionsHandlerServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @var \PermissionsHandler\Tests\Models\User */
    protected $userModel;

    /** @var \PermissionsHandler\Models\Role */
    protected $userRole;

    /** @var \PermissionsHandler\Models\Role */
    protected $adminRole;

    /** @var \PermissionsHandler\Models\Permission */
    protected $userPermission;

    /** @var \PermissionsHandler\Models\Permission*/
    protected $adminPermission;


    const USER_ROLE = 'user';
    const ADMIN_ROLE = 'admin';

    const USER_PERMISSION = 'userPermission';
    const ADMIN_PERMISSION = 'adminPermission';

    public function setUp()
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
        $this->seedDatabase($this->app);
        $this->loadModels();

        $this->clearLogTestHandler();
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'permissions2',
            'username' => 'root',
            'password' => 'root',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.permissions.driver', 'local');
        $app['config']->set('filesystems.disks.permissions.root', base_path() . '/database/seeds/permissions-handler');

        $configs = [
            'user' => User::class,
            'redirectUrl' => null,
            'aggressiveMode' => false,
            'excludedRoutes' => ['login', 'register'],
            'cacheExpiration' => 60,
            'seeder' => true
        ];
        $app['config']->set('permissionsHandler', $configs);

        $app['config']->set('app.key', 'base64:L8lRK8Go1NWCvy03sjPInQb2pA74FXweFLX4N9MHP68=');
        // Use test User model for users provider
        $app['config']->set('auth.providers.users.model', User::class);
        $app['log']->getMonolog()->pushHandler(new TestHandler());
    }
    /**
     * Set up the database.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        if (!$app['db']->connection()->getSchemaBuilder()->hasTable('users'))
        {
            include_once __DIR__.'/Models/create_users_table.php';
            (new \CreateUsersTable())->up();
        }

        include_once __DIR__.'/../src/Migrations/migrations.php';

        (new \CreateUserPermissionsMigrations())->up();
    }

    public function seedDatabase($app)
    {
        // database seeding
        User::firstOrCreate(['name' => 'test user' , 'email' => 'user@permissions.com']);
        User::firstOrCreate(['name' =>  'test admin' ,'email' => 'admin@permissions.com']);

        $app[Role::class]->firstOrCreate(['name' => self::USER_ROLE]);
        $app[Role::class]->firstOrCreate(['name' => self::ADMIN_ROLE]);

        $app[Permission::class]->firstOrCreate(['name' => self::USER_PERMISSION]);
        $app[Permission::class]->firstOrCreate(['name' => self::ADMIN_PERMISSION]);
    }


    public function loadModels()
    {
        $this->userModel = User::whereEmail('user@permissions.com')->first();
        $this->adminModel = User::whereEmail('admin@permissions.com')->first();

        $this->userRoleModel = app(Role::class)->where('name', self::USER_ROLE)->first();
        $this->adminRoleModel = app(Role::class)->where('name', self::ADMIN_ROLE)->first();

        $this->userPermissionModel = app(Permission::class)->where('name', self::USER_PERMISSION)->first();
        $this->adminPermissionModel = app(Permission::class)->where('name', self::ADMIN_PERMISSION)->first();
    }

   
    protected function clearLogTestHandler()
    {
        collect($this->app['log']->getMonolog()->getHandlers())->filter(function ($handler) {
            return $handler instanceof TestHandler;
        })->first(function (TestHandler $handler) {
            $handler->clear();
        });
    }
    
    
    /**
     * @param $message
     * @param $level
     *
     * @return bool
     */
    protected function hasLog($message, $level)
    {
        return collect($this->app['log']->getMonolog()->getHandlers())->filter(function ($handler) use ($message, $level) {
                return $handler instanceof TestHandler
                    && $handler->hasRecordThatContains($message, $level);
            })->count() > 0;
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            PermissionsHandlerServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'PermissionsHandler' => \PermissionsHandler\Facades\PermissionsHandlerFacade::class
        ];
    }
}