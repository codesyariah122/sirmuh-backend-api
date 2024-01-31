<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{Karyawan};
use Auth;

class DataKaryawanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
         $keywords = $request->query('keywords');
         $kode = $request->query('kode');
         $sortName = $request->query('sort_name');
         $sortType = $request->query('sort_type');

        if($keywords) {
            $karyawans = Karyawan::whereNull('deleted_at')
            ->select('id', 'nama', 'kode', 'level')
            ->where('nama', 'like', '%'.$keywords.'%')
            ->with('users')
            ->orderByDesc('id', 'DESC')
            ->paginate(10);
        } else if($kode) {
            $karyawans = Karyawan::whereNull('deleted_at')
            ->select('id', 'nama', 'kode', 'level')
            ->where('kode', $kode)
            ->with('users')
            ->orderByDesc('id', 'DESC')
            ->paginate(10);
        } else {
            if($sortName && $sortType) {
                $karyawans =  Karyawan::whereNull('deleted_at')
                ->select('id', 'nama', 'kode', 'level')
                ->with('users')
                ->orderBy($sortName, $sortType)
                ->paginate(10);
            } else {
               $karyawans =  Karyawan::whereNull('deleted_at')
               ->select('id', 'nama', 'kode', 'level')
               ->with('users')
               ->orderByDesc('id', 'DESC')
               ->paginate(10);
           }
        }

        return new ResponseDataCollect($karyawans);
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
        try {
            $karyawan = Karyawan::whereNull('deleted_at')
            ->findOrFail($id);
            $karyawan->delete();
            $data_event = [
                'alert' => 'error',
                'routes' => 'karyawan',
                'type' => 'removed',
                'notif' => "{$karyawan->nama}, has move to trash, please check trash!",
                'user' => Auth::user()
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Data supplier {$karyawan->nama} has move to trash, please check trash"
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
