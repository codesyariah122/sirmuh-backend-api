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
use App\Models\{Kas, KasAwal, User, Roles};
use Auth;

class DataKasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $helpers;

    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers(null);
    }

    public function index(Request $request)
    {
        $keywords = $request->query('keywords');
        $kode = $request->query('kode');
        $sortName = $request->query('sort_name');
        $sortType = $request->query('sort_type');

        if($keywords) {
            $kas = Kas::whereNull('deleted_at')
            ->select('kas.id', 'kas.kode', 'kas.nama', 'kas.saldo as saldo_kas', 'kas_awal.saldo as saldo_awal')
            ->leftJoin('kas_awal', 'kas.kode', '=', 'kas_awal.kode_kas')
            ->where('kas.nama', 'like', '%'.$keywords.'%')
            ->orderBy('kas.saldo', 'DESC')
            ->limit(10)
            ->paginate(10);
        } else if($kode) {
            $kas = Kas::whereNull('deleted_at')
            ->select('kas.id', 'kas.kode', 'kas.nama', 'kas.saldo as saldo_kas', 'kas_awal.saldo as saldo_awal')
            ->leftJoin('kas_awal', 'kas.kode', '=', 'kas_awal.kode_kas')
            ->whereKode($kode)
            ->limit(10)
            ->get();
        } else {
            if($sortName && $sortType) {
                $kas =  Kas::whereNull('deleted_at')
                ->select('kas.id', 'kas.kode', 'kas.nama', 'kas.saldo as saldo_kas', 'kas_awal.saldo as saldo_awal')
                ->leftJoin('kas_awal', 'kas.kode', '=', 'kas_awal.kode_kas')
                ->orderBy($sortName, $sortType)
                ->limit(10)
                ->paginate(10);
            }else{                
                $kas =  Kas::whereNull('deleted_at')
                ->select('kas.id', 'kas.kode', 'kas.nama', 'kas.saldo as saldo_kas', 'kas_awal.saldo as saldo_awal')
                ->leftJoin('kas_awal', 'kas.kode', '=', 'kas_awal.kode_kas')
                ->orderBy('kas.saldo', 'DESC')
                ->limit(10)
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
        try {
           $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'bank' => 'required',
            'saldo' => 'required'
        ]);

           if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $kode_kas = $this->helpers->generateAcronym($request->bank);
        $newKas = new Kas;
        $newKas->kode = $kode_kas;
        $newKas->nama = $request->nama;
        $newKas->saldo = intval($request->saldo);
        $newKas->no_rek = $request->no_rek;
        $newKas->default_toko = $request->default_toko ? 'True' : 'False';
        $newKas->save();

        if($newKas) {
            $userOnNotif = Auth::user();
            $data_event = [
                'routes' => 'kas',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "{$newKas->nama}, baru saja ditambahkan 🤙!",
                'data' => $newKas->nama,
                'user' => $userOnNotif
            ];
            event(new EventNotification($data_event));

            $newDataKas = Kas::findOrFail($newKas->id);

            return response()->json([
                'success' => true,
                'message' => "Data kas dengan nama {$newDataKas->nama}, successfully added✨!",
                'data' => $newDataKas
            ]);
        }


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
        try {
            $kas = Kas::query()
            ->whereNull('kas.deleted_at')
            ->select('kas.*', 'kas_awal.kode_kas', 'kas_awal.saldo as saldo_awal')
            ->leftJoin('kas_awal', 'kas.kode', '=' ,'kas_awal.kode_kas')
            ->where('kas.id', $id)
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
            $user = Auth::user();

            $userRole = Roles::findOrFail($user->role);

            if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {
                $updateKas = Kas::findOrFail($id);
                $updateKas->nama = $request->nama ?? $updateKas->nama;
                $updateKas->kode = $request->kode ?? $updateKas->kode;
                $updateKas->no_rek = $request->no_rek ?? $updateKas->no_rek;
                $updateKas->saldo = $request->saldo ?? $updateKas->saldo;
                $updateKas->save();
                $kas = Kas::whereNull('deleted_at')
                ->whereId($id)
                ->get();

                $dataKasAwal = KasAwal::where('kode_kas', $updateKas->kode)->first();
                $updateKasAwal = KasAwal::findOrFail($dataKasAwal->id);
                $updateKasAwal->saldo = $request->saldo_awal;
                $updateKasAwal->save();

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
            } else {
                return response()->json([
                    'error' => true,
                    'message' => "Hak akses tidak di ijinkan 📛"
                ]);
            }
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
        try {
            $user = Auth::user();

            $userRole = Roles::findOrFail($user->role);

            if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {
                $kas = Kas::whereNull('deleted_at')
                ->findOrFail($id);
                $kas->delete();
                $data_event = [
                    'alert' => 'error',
                    'routes' => 'kas',
                    'type' => 'removed',
                    'notif' => "{$kas->nama}, has move to trash, please check trash!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data supplier {$kas->nama} has move to trash, please check trash"
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => "Hak akses tidak di ijinkan 📛"
                ]);
            }
            
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
