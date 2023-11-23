<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User, Menu};

class UserDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $user_login = User::whereEmail($user->email)
            ->with('roles')
            ->with('logins')
            ->firstOrFail();
            $menus = Menu::whereJsonContains('roles', $user_login->role)
            ->with([
                'sub_menus',
                'sub_menus.child_sub_menus' => function ($query) use ($user_login) {
                    $query->whereJsonContains('roles', $user_login->role);
                }
            ])
            ->get();

            if (count($user_login->logins) === 0) {
                return response()->json([
                    'success' => false,
                    'not_login' => true,
                    'message' => 'Anauthenticated'
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'User is login 🧑🏻‍💻',
                'data' => $user_login,
                'menus' => $menus
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage(),
                'valid' => auth()->check()
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
        //
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
