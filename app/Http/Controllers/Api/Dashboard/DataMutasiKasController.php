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
    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
    }
    
    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $jenis = $request->query('jenis');
            $viewAll = $request->query('view_all');
            $today = now()->toDateString();
            $now = now();
            $startOfMonth = $now->startOfMonth()->toDateString();
            $endOfMonth = $now->endOfMonth()->toDateString();
            $dateTransaction = $request->query('date_transaction');

            $query = MutasiKas::query()
            ->select('mutasikas.*')
            ->whereNull('deleted_at');
            
            if ($dateTransaction) {
                $query->whereDate('mutasikas.tanggal', '=', $dateTransaction);
            }

            if ($keywords) {
                $query->where('mutasikas.kode', 'like', '%' . $keywords . '%');
            }

            if($viewAll === false || $viewAll === "false") {
                // $query->whereDate('pembelian.tanggal', '=', $today);
                $query->whereBetween('mutasikas.tanggal', [$startOfMonth, $endOfMonth]);
            }

            $mutasikas = $query->orderByDesc('id')
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

            $currentDate = now()->format('ymd');
            $kas_id = $request->kas_id;
            $destination = $request->destination;
            $jumlah = $request->jumlah;
            $ownKas = Kas::findOrFail($kas_id);
            $destKas = Kas::findOrFail($destination);

            $newMutasiKas = new MutasiKas;
            $newMutasiKas->kode = $request->kode;
            $newMutasiKas->tanggal = $currentDate;
            $newMutasiKas->debet = $ownKas->kode;
            $newMutasiKas->kredit = $destKas->kode;
            $newMutasiKas->keterangan = $request->keterangan;
            $newMutasiKas->rupiah = $request->jumlah;
            $newMutasiKas->operator = $request->operator;
            $newMutasiKas->save();

            $ownKas->saldo = intval($ownKas->saldo) - intval($jumlah);
            $ownKas->save();

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

            $historyKeterangan = "{$userOnNotif->name}, melakukan mutasi kas dari kas : {$newOwnKas->kode}, ke kas : {$newDestKas->kode}, sebesar {$this->helpers->format_uang($newMutasiKas->rupiah)}";
            $dataHistory = [
                'user' => $userOnNotif->name,
                'keterangan' => $historyKeterangan,
                'routes' => '/dashboard/backoffice/mutasi-kas',
                'route_name' => 'Mutasi Kas'
            ];
            $createHistory = $this->helpers->createHistory($dataHistory);

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
        try {
            $mutasiKas = MutasiKas::findOrFail($id);

            $dataKasDebet = Kas::whereKode($mutasikas->debet);
            $dataKasKredit = Kas::whereKode($mutasiKas->kredit);

            return response()->json([
                'success' => true,
                'message' => "Detail mutasi {$mutasikas->kode}",
                'data' => $mutasiKas,
            ]);
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
