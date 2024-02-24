<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{MutasiKas, Kas};
use Auth;

class DataMutasiKasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $mutasikas = MutasiKas::whereNull('deleted_at')
            ->paginate(10);

            return new ResponseDataCollect($mutasikas);
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
                'kas_id' => 'required',
                'jumlah' => 'required',
                'destination' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $kas_id = $request->kas_id;
            $destination = $request->destination;
            $jumlah = $request->jumlah;

            $ownKas = Kas::findOrFail($kas_id);
            $ownKas->saldo = intval($ownKas->saldo) - intval($jumlah);
            $ownKas->save();

            $destKas = Kas::findOrFail($destination);
            $destKas->saldo = intval($destKas->saldo) + intval($jumlah);
            $destKas->save();

            $userOnNotif = Auth::user();

            $data_event = [
                'routes' => 'mutasi-kas',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Kas {$ownKas->nama}, mutasi successfully 🤙!",
                'data' => $destKas,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            $newOwnKas = Kas::findOrFail($kas_id);
            $newDestKas = Kas::findOrFail($destination);

            return response()->json([
                'success' => true,
                'message' => "Mutasi kas {$ownKas->nama}, successfully ✨",
                'data' => [
                    'own_kas' => $newOwnKas,
                    'dest_kas' => $newDestKas
                ]
            ], 200);

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
