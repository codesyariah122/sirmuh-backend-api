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
use App\Models\{Pembelian,KoreksiStok,ItemKoreksi,ItemPembelian,Supplier,Barang,Kas};
use Auth;

class DataKoreksiStokController extends Controller
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
        $viewAll = $request->query('view_all');
        $today = now()->toDateString();
        $now = now();
        $startOfMonth = $now->startOfMonth()->toDateString();
        $endOfMonth = $now->endOfMonth()->toDateString();
        $dateTransaction = $request->query('date_transaction');

        $user = Auth::user();


        $query = KoreksiStok::query()
        ->select('koreksi.*', 'itemkoreksi.kode_barang', 'itemkoreksi.nama_barang', 'itemkoreksi.satuan', 'itemkoreksi.selisih')
        ->leftJoin('itemkoreksi', 'koreksi.kode', '=', 'itemkoreksi.kode')
        ->limit(10);

        if ($dateTransaction) {
            $query->whereDate('koreksi.tanggal', '=', $dateTransaction);
        }

        if ($keywords) {
            $query->where('koreksi.kode', 'like', '%' . $keywords . '%');
        }

        if($viewAll === false || $viewAll === "false") {
            $query->whereBetween('koreksi.tanggal', [$startOfMonth, $endOfMonth]);
        }

        $results = $query
        ->where(function ($query) use ($user) {
            if ($user->role !== 1) {
                $query->whereRaw('LOWER(koreksi.operator) like ?', [strtolower('%' . $user->name . '%')]);
            } 
        })
        ->orderByDesc('koreksi.id')
        ->paginate(10);

        return new ResponseDataCollect($results);

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
            'alasan' => 'required',
            'stok_kini' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value == 0) {
                        $fail($attribute.' tidak boleh 0.');
                    }
                },
            ],
        ]);

           if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $storeKoreksi = new KoreksiStok;
        $storeKoreksi->kode = $request->kode;
        $storeKoreksi->tanggal = $request->tanggal;
        $storeKoreksi->operator = $request->operator;
        $storeKoreksi->lokasistok = $request->lokasistok;
        $storeKoreksi->jumlah = $request->stok_kini;
        $storeKoreksi->jenis = $request->jenis;
        $storeKoreksi->save();

        $itemKoreksi = new ItemKoreksi;
        $itemKoreksi->kode = $storeKoreksi->kode;
        $itemKoreksi->kode_barang = $request->kode_barang;
        $itemKoreksi->nama_barang = $request->nama_barang;
        $itemKoreksi->satuan = $request->satuan;
        $itemKoreksi->stok_lalu = $request->stok_lalu;
        $itemKoreksi->stok_kini = $request->stok_kini;
        $itemKoreksi->alasan = $request->alasan;
        $itemKoreksi->selisih = $request->selisih;
        $itemKoreksi->hpp = $request->hpp;
        $itemKoreksi->save();

        $dataBarang = Barang::whereKode($request->kode_barang)->first();
        $updateStok = Barang::findOrFail($dataBarang->id);
        $updateStok->toko = $request->stok_kini;
        $updateStok->save();

        $userOnNotif = Auth::user();

        $data_event = [
            'routes' => 'koreksi-stok',
            'alert' => 'success',
            'type' => 'add-data',
            'notif' => "Barang {$dataBarang->nama}, stok successfully updated 🤙!",
            'data' => $storeKoreksi,
            'user' => $userOnNotif
        ];

        event(new EventNotification($data_event));

        return response()->json([
            'success' => true,
            'message' => "Koreksi stok {$dataBarang->nama}, successfully ✨",
            'data' => $updateStok
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
            $detailKoreksi = KoreksiStok::query()
            ->select('koreksi.*', 'itemkoreksi.kode as kode_item', 'itemkoreksi.kode_barang', 'itemkoreksi.nama_barang', 'itemkoreksi.satuan', 'itemkoreksi.stok_lalu', 'itemkoreksi.stok_kini', 'itemkoreksi.alasan', 'itemkoreksi.selisih', 'itemkoreksi.hpp')
            ->leftJoin('itemkoreksi', 'koreksi.kode', '=', 'itemkoreksi.kode')
            ->where('koreksi.id', $id)
            ->first();
            return response()->json([
                'success' => true,
                'message' => 'Detail Data Koreksi Stok',
                'data' => $detailKoreksi
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
