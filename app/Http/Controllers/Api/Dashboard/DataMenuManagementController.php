<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Models\{Menu, Roles, User};
use App\Events\EventNotification;
use Auth;

class DataMenuManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->route()->getActionMethod() === 'index') {
                return $next($request);
            }

            if (Gate::allows('data-sub-menu')) {
                return $next($request);
            }

            return response()->json([
                'error' => true,
                'message' => 'Anda tidak memiliki cukup hak akses'
            ]);
        });
    }
    public function index()
    {
        try {
            $userAuth = Auth::user();
            $role = $userAuth->role;
            $menus = Menu::whereNull('deleted_at')
            ->whereJsonContains('roles', $role)
            ->get();
            return response()->json([
                'success' => true,
                'message' => 'List of data menus 🗒️',
                'data' => count($menus) > 0 ? $menus : null,
                'user' => $userAuth
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
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
        try {
            $validator = Validator::make($request->all(), [
                'menu' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_already = Menu::whereMenu($request->menu)->get();
            if (count($check_already) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "{$request->menu}, sudah tersedia!"
                ]);
            }
            $menu = new Menu;
            $menu->menu = $request->menu;
            $menu->roles = $request->roles;
            $menu->icon = $request->icon;
            $menu->save();

            $data_event = [
                'routes' => 'menus',
                'type' => 'menu',
                'notif' => "{$menu->menu}, berhasil ditambahkan! 🥳",
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => 'Success Added new menu 🥳',
                'data' => $menu
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
