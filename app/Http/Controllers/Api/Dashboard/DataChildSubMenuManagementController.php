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

            $subMenu = SubMenu::findOrFail($request->parent_menu);

            $sub_menu_id = $subMenu->id;
            $menuValue = is_array($request->menu) ? reset($request->menu) : $request->menu;
            $link = Str::slug($menuValue);
            $menu =  is_array($request->menu) ? implode(', ', $request->menu) : $request->menu;
            $child_sub_menu = new ChildSubMenu;
            $child_sub_menu->menu = $menu;
            $child_sub_menu->link = $link;
            $child_sub_menu->roles = $request->roles;
            $child_sub_menu->save();
            $child_sub_menu->sub_menus()->sync($sub_menu_id);

            $data_event = [
                'routes' => 'menus',
                'type' => 'child-sub-menu',
                'notif' => $request->menu.", successfully add child sub menus!!",
                'user' => Auth::user()
            ];

            event(new EventNotification($data_event));

            $new_child_sub_menu = SubMenu::whereId($request->parent_menu)
            ->with('child_sub_menus')
            ->orderBy('id', 'DESC')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Success Added New child sub menu 🥳',
                'data' => $new_child_sub_menu
            ]);
        } catch (\Throwable $th) {
            \Log::error('Error while processing the request. ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error while processing the request.',
                'error' => $th->getMessage(),
            ], 500);
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
