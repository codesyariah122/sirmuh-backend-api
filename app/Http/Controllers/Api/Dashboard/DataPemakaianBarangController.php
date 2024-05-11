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
            ->select('pemakaian_barangs.kode', 'pemakaian_barangs.tanggal', 'pemakaian_barangs.barang', 'pemakaian_barangs.qty', 'pemakaian_barangs.keperluan', 'pemakaian_barangs.keterangan', 'pemakaian_barangs.operator', 'barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.satuan')
            ->leftJoin('barang', 'pemakaian_barangs.barang', '=', 'barang.kode');

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
                'barang' => 'required',
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

            $dataBarang = Barang::where('kode', $request->barang)->first();
            $newPemakaian = new PemakaianBarang;
            $newPemakaian->kode = $request->kode ? $request->kode : $pemakaianKode;
            $newPemakaian->tanggal = $currentDate;
            $newPemakaian->barang = $dataBarang->kode;
            $newPemakaian->qty = $request->qty;
            $newPemakaian->keperluan = $request->keperluan;
            $newPemakaian->keterangan = $request->keterangan;
            $newPemakaian->operator = $userOnNotif->name;
            $newPemakaian->save();

            $updateStokBarang = Barang::findOrFail($dataBarang->id);
            $updateStokBarang->toko = intval($dataBarang->toko) - intval($newPemakaian->qty);
            $updateStokBarang->last_qty = $dataBarang->toko;
            $updateStokBarang->save();


            $data_event = [
                'routes' => 'pemakaian-barang',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Pemakaian barang {$newPemakaian->nama_barang}, successfully added 🤙!",
                'data' => $newPemakaian,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            $newPemakaianBarang = [
                'nama_barang' => $dataBarang->nama,
                'kode_barang' => $newPemakaian->barang,
                'qty' => $newPemakaian->qty,
                'satuan' => $dataBarang->satuan,
                'jenis' => $newPemakaian->jenis,
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
