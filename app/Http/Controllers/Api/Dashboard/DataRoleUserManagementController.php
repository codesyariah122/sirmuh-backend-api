<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Events\EventNotification;
use App\Models\{User, Karyawan, Roles, Menu};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Helpers\{WebFeatureHelpers};


class DataRoleUserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        // $this->middleware(function ($request, $next) {
        //     if ($request->route()->getActionMethod() === 'index') {
        //         return $next($request);
        //     }

        //     if (Gate::allows('data-role-management')) {
        //         return $next($request);
        //     }

        //     return response()->json([
        //         'error' => true,
        //         'message' => 'Anda tidak memiliki cukup hak akses'
        //     ]);
        // });
    }
    
    public function index(Request $request)
    {
        try {
            $roles = Roles::with(['users' => function ($query) {
                $query->select('users.id as total_user');
            }])
            ->select('roles.id', 'roles.name') 
            ->paginate(10);

            return new ResponseDataCollect($roles);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($name)
    {
        try {
            $userRoles = Roles::whereName(strtoupper($name))
            // ->with(['users' => function ($query) use ($role) {
            //     $query->whereRole($role->id)
            //     ->with(['roles', 'karyawans']);
            // }])
            ->get();

            $menus = Menu::whereJsonContains('roles', $userRoles[0]->id)
            ->with([
                'sub_menus' => function ($query) use ($userRoles) {
                    $query->whereJsonContains('roles', $userRoles[0]->id)
                    ->with('child_sub_menus');
                }])
            ->get();
            $responseData = ['roles' => $userRoles, 'menus' => $menus];
            return new ResponseDataCollect($responseData);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
