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
use App\Models\{Pengeluaran, SetupPerusahaan, Kas, Roles};
use Auth;

class DataPengeluaranController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $biaya = $request->query('biaya');
            $viewAll = $request->query('view_all');
            $today = now()->toDateString();
            $now = now();
            $startOfMonth = $now->startOfMonth()->toDateString();
            $endOfMonth = $now->endOfMonth()->toDateString();
            $dateTransaction = $request->query('date_transaction');

            $query = Pengeluaran::query()
            ->select('pengeluaran.*', 'biaya.kode as kode_biaya', 'biaya.nama as biaya_nama', 'kas.kode as kas_kode', 'kas.nama as nama_kas')
            ->leftJoin('biaya', 'pengeluaran.kd_biaya', '=', 'biaya.kode')
            ->leftJoin('kas', 'pengeluaran.kode_kas', '=', 'kas.kode')
            ->whereNull('pengeluaran.deleted_at')
            ->limit(10);


            if ($dateTransaction) {
                $query->whereDate('pengeluaran.tanggal', '=', $dateTransaction);
            }

            if ($keywords) {
                $query->where('pengeluaran.kode', 'like', '%' . $keywords . '%');
            }

            if ($biaya) {
                $query->where('biaya.kode', 'like', '%' . $biaya . '%');
            }

            if($viewAll === false || $viewAll === "false") {
                // $query->whereDate('pembelian.tanggal', '=', $today);
                $query->whereBetween('pengeluaran.tanggal', [$startOfMonth, $endOfMonth]);
            }

            $pengeluarans = $query->orderByDesc("pengeluaran.tanggal")
            ->paginate(10);
            return new ResponseDataCollect($pengeluarans);

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
            'kode' => 'required',
            'kd_biaya' => 'required',
            'kode_kas' => 'required',
            'jumlah' => 'required'
        ]);

           if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $newPemasukan = new Pengeluaran;
        $newPemasukan->kode = $request->kode;
        $newPemasukan->tanggal = $request->tanggal;
        $newPemasukan->kd_biaya = $request->kd_biaya;
        $newPemasukan->keterangan = $request->keterangan;
        $newPemasukan->kode_kas = $request->kode_kas;
        $newPemasukan->jumlah = $request->jumlah;
        $newPemasukan->operator = $request->operator;
        $newPemasukan->save();

        if($newPemasukan) {
            $dataKas = Kas::whereKode($request->kode_kas)->first();
            $updateKas = Kas::findOrFail($dataKas->id);
            $updateKas->saldo = intval($dataKas->saldo) - intval($request->jumlah);
            $updateKas->save();

            $userOnNotif = Auth::user();
            $data_event = [
                'routes' => 'kas',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Pengeluaran {$newPemasukan->kode}, baru saja ditambahkan 🤙!",
                'data' => $newPemasukan->kode,
                'user' => $userOnNotif
            ];
            event(new EventNotification($data_event));

            $newDataPemasukan= Pengeluaran::findOrFail($newPemasukan->id);

            return response()->json([
                'success' => true,
                'message' => "Pengeluaran dengan kode {$newPemasukan->kode}, successfully added✨!",
                'data' => $newDataPemasukan
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
            $pengeluaran = Pengeluaran::query()
            ->select('pengeluaran.*', 'biaya.kode as kode_biaya', 'biaya.nama as biaya_nama', 'kas.kode as kas_kode', 'kas.nama as nama_kas')
            ->leftJoin('biaya', 'pengeluaran.kd_biaya', '=', 'biaya.kode')
            ->leftJoin('kas', 'pengeluaran.kode_kas', '=', 'kas.kode')
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => "Detail pengeluaran",
                'data' => $pengeluaran
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
        try {
            $user = Auth::user();

            $userRole = Roles::findOrFail($user->role);

            if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {
                $pengeluaran = Pengeluaran::whereNull('deleted_at')
                ->findOrFail($id);
                $pengeluaran->delete();

                $dataKas = Kas::whereKode($pengeluaran->kode_kas)->first();
                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = intval($dataKas->saldo) + intval($pengeluaran->jumlah);
                $updateKas->save();

                $data_event = [
                    'alert' => 'error',
                    'routes' => 'pengeluaran',
                    'type' => 'removed',
                    'notif' => "Pengeluaran with kode {$pengeluaran->kode}, has move to trash, please check trash!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Pengeluaran with kode {$pengeluaran->kode} has move to trash, please check trash"
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
