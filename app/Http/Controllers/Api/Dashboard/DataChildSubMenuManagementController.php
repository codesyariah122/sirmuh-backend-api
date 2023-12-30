<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Models\{ChildSubMenu, SubMenu, Menu};
use App\Events\EventNotification;
use Auth;

class DataChildSubMenuManagementController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $userAuth = Auth::user();
            $role = $userAuth->role;
            $menus = SubMenu::whereJsonContains('roles', $role)
            ->with('child_sub_menus')
            ->get();
            return response()->json([
                'success' => true,
                'message' => 'List all data child sub menus 🗂️',
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
                'roles' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $subMenu = SubMenu::whereId($request->parent_menu)->get();

            $sub_menu_id = $subMenu[0]->id;

            // var_dump($sub_menu_id);
            // die;

            $child_sub_menu = new ChildSubMenu;
            $child_sub_menu->menu = $request->menu;
            $child_sub_menu->link = Str::slug($request->menu);
            $child_sub_menu->roles = $request->roles;
            $child_sub_menu->save();
            $child_sub_menu->sub_menus()->sync($sub_menu_id);

            $data_event = [
                'type' => 'child-sub-menu',
                'notif' => "{$child_sub_menu->menu}, berhasil ditambahkan! 🥳",
                'data' => $child_sub_menu
            ];

            event(new EventNotification($data_event));

            $new_child_sub_menu = SubMenu::whereId($sub_menu_id)
            ->with('child_sub_menus')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Success Added New child sub menu 🥳',
                'data' => $new_child_sub_menu
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
