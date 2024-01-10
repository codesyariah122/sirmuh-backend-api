<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Models\{Menu, SubMenu};
use App\Events\EventNotification;
use Auth;

class DataSubMenuManagementController extends Controller
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
            $menus = Menu::whereJsonContains('roles', $role)
            ->with('sub_menus')
            ->get();
            return response()->json([
                'success' => true,
                'message' => 'List all data menus 🗂️',
                'data' => count($menus) > 0 ? $menus : null,
                'user' => $userAuth
            ]);
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
        try {
            $validator = Validator::make($request->all(), [
                'parent_menu' => 'required',
                'menu' => 'required',
                'icon' => 'required',
                'roles' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $menu = Menu::whereId($request->parent_menu)->get();

            // var_dump($menu[0]->id);
            // die;

            $menu_id = $menu[0]->id;

            $sub_menu = new SubMenu;
            $sub_menu->menu = $request->menu;
            $sub_menu->link = Str::slug($request->menu);
            $sub_menu->icon = $request->icon;
            $sub_menu->is_active = 1;
            $sub_menu->roles = $request->roles;
            $sub_menu->save();
            $sub_menu->menus()->sync($menu_id);

            $data_event = [
                'routes' => 'menus',
                'type' => 'sub-menu',
                'notif' => "{$sub_menu->menu}, berhasil ditambahkan! 🥳",
                'user' => Auth::user()
            ];

            event(new EventNotification($data_event));

            $new_menu = Menu::whereId($menu_id)
            ->with('sub_menus')
            ->orderByDesc('id')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Success Added New sub menu 🥳',
                'data' => $new_menu
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
