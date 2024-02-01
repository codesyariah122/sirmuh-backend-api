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
use App\Models\{Kas};
use Auth;

class DataKasController extends Controller
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
            $kas = Kas::whereNull('deleted_at')
            ->select('id', 'kode', 'nama', 'saldo')
            ->where('nama', 'like', '%'.$keywords.'%')
            // ->orderByDesc('id', 'DESC')
            ->orderBy('saldo', 'DESC')
            ->paginate(10);
        } else if($kode) {
            $kas = Kas::whereNull('deleted_at')
            ->select('id', 'kode', 'nama', 'saldo')
            ->whereKode($kode)
            // ->orderByDesc('id', 'DESC')
            ->get();
        } else {
            if($sortName && $sortType) {
                $kas =  Kas::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'saldo')
                ->orderBy($sortName, $sortType)
                ->paginate(10);
            }else{                
                $kas =  Kas::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'saldo')
            // ->orderByDesc('id', 'DESC')
                ->orderBy('saldo', 'DESC')
                ->paginate(10);
            }
        }

        return new ResponseDataCollect($kas);
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
        try {
            $kas = Kas::whereNull('deleted_at')
            ->whereId($id)
            ->get();
            return new ResponseDataCollect($kas);
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
        try {
            $updateKas = Kas::findOrFail($id);
            $updateKas->nama = $request->nama ?? $updateKas->nama;
            $updateKas->kode = $request->kode ?? $updateKas->kode;
            $updateKas->saldo = $request->saldo ?? $updateKas->saldo;
            $updateKas->save();
            $kas = Kas::whereNull('deleted_at')
            ->whereId($id)
            ->get();

            $userOnNotif = Auth::user();
            $data_event = [
                'routes' => 'kas',
                'alert' => 'success',
                'type' => 'update-data',
                'notif' => "Data kas dengan kode {$updateKas->kode}, berhasil diupdate 🤙!",
                'data' => $updateKas->kode,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));
            return response()->json([
                'success' => true,
                'message' => 'Successfully updated data kas !',
                'data' => $updateKas
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
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
