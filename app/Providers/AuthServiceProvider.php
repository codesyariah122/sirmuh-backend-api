<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Auth;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\WebFeatureHelpers;
use App\Models\{User, Login, Menu};

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];
    protected $helpers = [];
    /**
     * Register any authentication / authorization services.
     * @author puji ermanto<pujiermanto@gmail.com>
     * @return void
     */

    public function __contstruct($data)
    {
        $this->helpers = $data;
    }
    public function set_data()
    {
        $gate_data = [
            'data-menu',
            'data-sub-menu',
            'data-role-management'
            // 'data-perusahaan'
        ];
        self::__contstruct($gate_data);
    }

    public function boot(Request $request)
    {
        $this->registerPolicies();

        Passport::routes();

        self::set_data();

        $gates = new WebFeatureHelpers($this->helpers);

        $gates->GatesAccess();
    }
}
