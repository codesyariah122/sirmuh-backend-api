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
use App\Models\{PemakaianBarang, Barang};
use Auth;

class DataPemakaianBarangController extends Controller
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
            $today = now()->toDateString();

            $query = PemakaianBarang::query()
            ->whereNull('pemakaian_barangs.deleted_at')
            ->select('pemakaian_barangs.id','pemakaian_barangs.kode', 'pemakaian_barangs.tanggal', 'pemakaian_barangs.barang_asal', 'pemakaian_barangs.qty', 'pemakaian_barangs.barang_tujuan', 'pemakaian_barangs.keperluan', 'pemakaian_barangs.keterangan', 'pemakaian_barangs.operator', 'barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.satuan')
            ->leftJoin('barang', 'pemakaian_barangs.barang_asal', '=', 'barang.kode');

            if ($keywords) {
                $query->where('kode', 'like', '%' . $keywords . '%');
            }

            $pemakian_barangs = $query
            ->orderByDesc('pemakaian_barangs.id')
            ->paginate(10);

            return new ResponseDataCollect($pemakian_barangs);

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
                'barang_asal' => 'required',
                'qty' => 'required',
                'keperluan' => 'required',
                'keterangan' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $userOnNotif = Auth::user();

            $currentDate = now()->format('dmy');
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));
            $pemakaianKode = "PEM-".$currentDate.$randomNumber;

            $dataBarangAsal = Barang::where('kode', $request->barang_asal)->first();
            $dataBarangTujuan = Barang::where('kode', $request->barang_tujuan)->first();
            $newPemakaian = new PemakaianBarang;
            $newPemakaian->kode = $request->kode ? $request->kode : $pemakaianKode;
            $newPemakaian->tanggal = $currentDate;
            $newPemakaian->barang_asal = $dataBarangAsal->kode;
            $newPemakaian->barang_tujuan = $dataBarangTujuan->kode;
            $newPemakaian->qty = $request->qty;
            $newPemakaian->keperluan = $request->keperluan;
            $newPemakaian->keterangan = $request->keterangan;
            $newPemakaian->operator = $userOnNotif->name;
            $newPemakaian->save();

            $updateStokBarangAsal = Barang::findOrFail($dataBarangAsal->id);
            $updateStokBarangAsal->toko = intval($dataBarangAsal->toko) - intval($newPemakaian->qty);
            $updateStokBarangAsal->last_qty = $dataBarangAsal->toko;
            $updateStokBarangAsal->save();

            $updateStokBarangTujuan = Barang::findOrFail($dataBarangTujuan->id);
            $updateStokBarangTujuan->toko = intval($dataBarangTujuan->toko) + intval($newPemakaian->qty);
            $updateStokBarangTujuan->last_qty = $dataBarangTujuan->toko;
            $updateStokBarangTujuan->save();


            $data_event = [
                'routes' => 'pemakaian-barang',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Pemakaian barang {$newPemakaian->nama_barang}, successfully added 🤙!",
                'data' => $newPemakaian,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            $newBarangTujuan = Barang::whereKode($newPemakaian->barang_tujuan)->first();

            $newPemakaianBarang = [
                'nama_barang_asal' => $dataBarangAsal->nama,
                'kode_barang_asal' => $newPemakaian->barang_asal,
                'nama_barang_tujuan' => $dataBarangTujuan->nama,
                'kode_barang_tujuan' => $newPemakaian->barang_tujuan,
                'qty' => $newPemakaian->qty,
                'stok_tujuan' => $newBarangTujuan->toko,
                'satuan_asal' => $newBarangTujuan->satuan,
                'satuan_tujuan' => $dataBarangTujuan->satuan,
                'keperluan' => $newPemakaian->keperluan,
                'keterangan' => $newPemakaian->keterangan
            ];

            return response()->json([
                'success' => true,
                'message' => "Pemakaian barang {$newPemakaian->nama_barang}, successfully added ✨!",
                'data' => $newPemakaianBarang
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
            $query = PemakaianBarang::query()
            ->whereNull('pemakaian_barangs.deleted_at')
            ->select('pemakaian_barangs.id','pemakaian_barangs.kode', 'pemakaian_barangs.tanggal', 'pemakaian_barangs.barang_asal', 'pemakaian_barangs.qty', 'pemakaian_barangs.barang_tujuan', 'pemakaian_barangs.keperluan', 'pemakaian_barangs.keterangan', 'pemakaian_barangs.operator', 'barang_asal.kode as kode_barang_asal', 'barang_asal.nama as nama_barang_asal', 'barang_asal.satuan as satuan_barang_asal', 'barang_tujuan.kode as kode_barang_tujuan', 'barang_tujuan.nama as nama_barang_tujuan', 'barang_tujuan.satuan as satuan_barang_tujuan')
            ->leftJoin('barang as barang_asal', function($join) {
                $join->on('pemakaian_barangs.barang_asal', '=', 'barang_asal.kode');
            })
            ->leftJoin('barang as barang_tujuan', function($join) {
                $join->on('pemakaian_barangs.barang_tujuan', '=', 'barang_tujuan.kode');
            })
            ->where('pemakaian_barangs.id', $id);

            $pemakaian_barang = $query->first();
            
            return response()->Json([
                'success' => true,
                'message' => "Detail pemakaian barang {$pemakaian_barang->kode}",
                'data' => $pemakaian_barang
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
