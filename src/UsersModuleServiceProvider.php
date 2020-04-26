<?php

namespace Anomaly\UsersModule;

use Anomaly\UsersModule\Role\RoleModel;
use Anomaly\UsersModule\User\UserModel;
use Anomaly\UsersModule\Support\Authorizer;
use Anomaly\UsersModule\Role\RoleRepository;
use Anomaly\UsersModule\User\UserRepository;
use Anomaly\UsersModule\Console\UsersCleanup;
use Anomaly\Streams\Platform\Security\Policy;
use Anomaly\UsersModule\User\Event\UserWasLoggedIn;
use Anomaly\UsersModule\User\Contract\UserInterface;
use Anomaly\UsersModule\User\Login\LoginFormBuilder;
use Anomaly\UsersModule\User\Event\UserHasRegistered;
use Anomaly\UsersModule\User\Listener\TouchLastLogin;
use Anomaly\UsersModule\Http\Middleware\CheckSecurity;
use Anomaly\Streams\Platform\Addon\AddonServiceProvider;
use Anomaly\UsersModule\User\Register\RegisterFormBuilder;
use Anomaly\UsersModule\Http\Middleware\AuthorizeRouteRoles;
use Anomaly\UsersModule\Http\Middleware\AuthorizeControlPanel;
use Anomaly\UsersModule\Http\Middleware\AuthorizeModuleAccess;
use Anomaly\UsersModule\Role\Contract\RoleRepositoryInterface;
use Anomaly\UsersModule\User\Contract\UserRepositoryInterface;
use Anomaly\UsersModule\User\Listener\SendNewUserNotifications;
use Anomaly\UsersModule\User\Password\ResetPasswordFormBuilder;
use Anomaly\UsersModule\User\Password\ForgotPasswordFormBuilder;
use Anomaly\UsersModule\Http\Middleware\AuthorizeRoutePermission;
use Anomaly\UsersModule\Support\Facades\Authorizer as AuthorizerFacade;

/**
 * Class UsersModuleServiceProvider
 *
 * @link   http://pyrocms.com/
 * @author Ryan Thompson <ryan@pyrocms.com>
 */
class UsersModuleServiceProvider extends AddonServiceProvider
{

    /**
     * The addon streams.
     *
     * @var array
     */
    public $streams = [
        'users' => UserModel::class,
        'roles' => RoleModel::class,
    ];

    /**
     * The addon commands.
     *
     * @var array
     */
    public $commands = [
        UsersCleanup::class,
    ];

    /**
     * The addon schedules.
     *
     * @var array
     */
    public $schedules = [
        'daily' => [
            UsersCleanup::class,
        ],
    ];

    /**
     * The module middleware.
     *
     * @var array
     */
    public $middleware = [
        CheckSecurity::class,
        AuthorizeRouteRoles::class,
        AuthorizeModuleAccess::class,
        AuthorizeControlPanel::class,
        AuthorizeRoutePermission::class,
    ];

    /**
     * The addon event listeners.
     *
     * @var array
     */
    public $listeners = [
        UserWasLoggedIn::class      => [
            TouchLastLogin::class,
        ],
        UserHasRegistered::class    => [
            SendNewUserNotifications::class,
        ],
    ];

    /**
     * The addon bindings.
     *
     * @var array
     */
    public $bindings = [
        'login'                     => LoginFormBuilder::class,
        'register'                  => RegisterFormBuilder::class,
        'reset_password'            => ResetPasswordFormBuilder::class,
        'forgot_password'           => ForgotPasswordFormBuilder::class,
    ];

    /**
     * The singleton bindings.
     *
     * @var array
     */
    public $singletons = [
        'authorizer' => Authorizer::class,
        UserRepositoryInterface::class => UserRepository::class,
        RoleRepositoryInterface::class => RoleRepository::class,
    ];

    /**
     * The addon routes.
     *
     * @var array
     */
    public $routes = [
        'users/self'            => [
            'ttl'  => 0,
            'as'   => 'anomaly.module.users::self',
            'uses' => 'Anomaly\UsersModule\Http\Controller\UsersController@self',
        ],
        '@{username}'           => [
            'as'   => 'anomaly.module.users::users.view',
            'uses' => 'Anomaly\UsersModule\Http\Controller\UsersController@view',
        ],
        'login'                 => [
            'ttl'  => 0,
            'as'   => 'anomaly.module.users::login',
            'uses' => 'Anomaly\UsersModule\Http\Controller\LoginController@login',
        ],
        'logout'                => [
            'ttl'  => 0,
            'as'   => 'anomaly.module.users::logout',
            'uses' => 'Anomaly\UsersModule\Http\Controller\LoginController@logout',
        ],
        'register'              => [
            'ttl'  => 0,
            'as'   => 'anomaly.module.users::register',
            'uses' => 'Anomaly\UsersModule\Http\Controller\RegisterController@register',
        ],
        'users/activate'        => [
            'ttl'  => 0,
            'as'   => 'anomaly.module.users::users.activate',
            'uses' => 'Anomaly\UsersModule\Http\Controller\RegisterController@activate',
        ],
        'users/password/reset'  => [
            'ttl'  => 0,
            'as'   => 'anomaly.module.users::users.reset',
            'uses' => 'Anomaly\UsersModule\Http\Controller\PasswordController@reset',
        ],
        'users/password/forgot' => [
            'ttl'  => 0,
            'as'   => 'anomaly.module.users::password.forgot',
            'uses' => 'Anomaly\UsersModule\Http\Controller\PasswordController@forgot',
        ],
        'admin'                 => [
            'ttl'  => 0,
            'uses' => 'Anomaly\UsersModule\Http\Controller\Admin\HomeController@index',
        ],
        'auth/login'            => [
            'ttl'  => 0,
            'uses' => 'Anomaly\UsersModule\Http\Controller\Admin\LoginController@logout',
        ],
        'auth/logout'           => [
            'ttl'  => 0,
            'uses' => 'Anomaly\UsersModule\Http\Controller\Admin\LoginController@logout',
        ],
        'admin/login'           => [
            'ttl'  => 0,
            'as'   => 'login',
            'uses' => 'Anomaly\UsersModule\Http\Controller\Admin\LoginController@login',
        ],
        'admin/logout'          => [
            'ttl'  => 0,
            'as'   => 'logout',
            'uses' => 'Anomaly\UsersModule\Http\Controller\Admin\LoginController@logout',
        ],
    ];

    /**
     * Boot the addon.
     */
    public function boot()
    {
        Policy::macro('viewAny', function (UserInterface $user, $model) {
            return AuthorizerFacade::authorize(
                $model->stream->location(
                    $model->stream->slug . '.view_any'
                ),
                $user
            );
        });

        Policy::macro('view', function (UserInterface $user, $model) {
            return AuthorizerFacade::authorize(
                $model->stream->location(
                    $model->stream->slug . '.view'
                ),
                $user
            );
        });

        Policy::macro('create', function (UserInterface $user, $model) {
            return AuthorizerFacade::authorize(
                $model->stream->location(
                    $model->stream->slug . '.create'
                ),
                $user
            );
        });

        Policy::macro('update', function (UserInterface $user, $model) {
            return AuthorizerFacade::authorize(
                $model->stream->location(
                    $model->stream->slug . '.update'
                ),
                $user
            );
        });

        Policy::macro('delete', function (UserInterface $user, $model) {
            return AuthorizerFacade::authorize(
                $model->stream->location(
                    $model->stream->slug . '.delete'
                ),
                $user
            );
        });

        Policy::macro('force_delete', function (UserInterface $user, $model) {
            return AuthorizerFacade::authorize(
                $model->stream->location(
                    $model->stream->slug . '.force_update'
                ),
                $user
            );
        });

        Policy::macro('restore', function (UserInterface $user, $model) {
            return AuthorizerFacade::authorize(
                $model->stream->location(
                    $model->stream->slug . '.restore'
                ),
                $user
            );
        });
    }
}
