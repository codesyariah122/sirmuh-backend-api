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
use App\Models\{PemakaianBarang, Barang, ItemPemakaian};
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
            ->select('pemakaian_barangs.id','pemakaian_barangs.kode', 'pemakaian_barangs.draft', 'pemakaian_barangs.tanggal', 'pemakaian_barangs.keperluan', 'pemakaian_barangs.keterangan', 'pemakaian_barangs.total', 'pemakaian_barangs.operator', 'pemakaian_barangs.draft');
            if ($keywords) {
                $query->where('pemakaian_barangs.kode', 'like', '%' . $keywords . '%');
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
                'keperluan' => 'required',
                'keterangan' => 'required',
                'total' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $userOnNotif = Auth::user();

            $currentDate = now()->format('dmy');
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));
            $pemakaianKode = "PEM-".$currentDate.$randomNumber;

            if($request->barang_asal !== NULL) {
                $dataBarangAsal = Barang::where('kode', $request->barang_asal)->first();
            }

            if($request->barang_tujuan !== NULL) {
                $dataBarangTujuan = Barang::where('kode', $request->barang_tujuan)->first();
            }

            // var_dump($request->draft); die;

            $newPemakaian = new PemakaianBarang;
            $newPemakaian->kode = $request->kode ? $request->kode : $pemakaianKode;
            $newPemakaian->draft = $request->draft;
            $newPemakaian->tanggal = $currentDate;
            $newPemakaian->keperluan = $request->keperluan ?? NULL;
            $newPemakaian->keterangan = $request->keterangan ?? NULL;
            $newPemakaian->total = $request->total ?? NULL;
            $newPemakaian->operator = $userOnNotif->name;
            $newPemakaian->save();


            $data_event = [
                'routes' => 'pemakaian-barang',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Pemakaian barang {$newPemakaian->kode}, successfully added 🤙!",
                'data' => $newPemakaian,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Pemakaian barang {$newPemakaian->kode}, successfully added ✨!",
                'data' => $newPemakaian
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
            $pemakaianBarang = PemakaianBarang::query()
            ->whereNull('pemakaian_barangs.deleted_at')
            ->select('pemakaian_barangs.id','pemakaian_barangs.kode', 'pemakaian_barangs.tanggal', 'pemakaian_barangs.keperluan', 'pemakaian_barangs.keterangan', 'pemakaian_barangs.total', 'pemakaian_barangs.operator', 'itempemakaian.kode_pemakaian', 'itempemakaian.barang_asal as barang_asal', 'itempemakaian.qty_asal as qty_asal', 'itempemakaian.barang_tujuan as barang_tujuan', 'itempemakaian.qty_tujuan as qty_tujuan', 'itempemakaian.harga', 'itempemakaian.supplier', 
                'barang_asal.kode as kode_barang_asal', 
                'barang_asal.toko as stok_barang_asal', 
                'barang_asal.supplier as barang_supplier_asal',
                'barang_tujuan.kode as kode_barang_tujuan', 
                'barang_tujuan.toko as stok_barang_tujuan', 
                'barang_tujuan.supplier as barang_supplier_tujuan'
            )
            ->leftJoin('itempemakaian', 'pemakaian_barangs.kode', '=', 'itempemakaian.kode_pemakaian')
            ->leftJoin('barang as barang_asal', 'itempemakaian.barang_asal', '=', 'barang_asal.kode')
            ->leftJoin('barang as barang_tujuan', 'itempemakaian.barang_tujuan', '=', 'barang_tujuan.kode')
            ->where('pemakaian_barangs.id', $id);

            $pemakaian_barang = $pemakaianBarang->first();

            $itemPemakaian = ItemPemakaian::query()
            ->select('itempemakaian.id', 'itempemakaian.kode_pemakaian', 'itempemakaian.barang_asal as barang_asal', 'itempemakaian.qty_asal as qty_asal', 'itempemakaian.barang_tujuan as barang_tujuan', 'itempemakaian.qty_tujuan as qty_tujuan', 'itempemakaian.harga', 'itempemakaian.total', 'itempemakaian.supplier', 
                'barang_asal.kode as kode_barang_asal',
                'barang_asal.nama as nama_barang_asal', 
                'barang_asal.toko as stok_barang_asal', 
                'barang_asal.satuan as satuan_barang_asal',
                'barang_asal.supplier as barang_supplier_asal',
                'barang_tujuan.kode as kode_barang_tujuan', 
                'barang_tujuan.nama as nama_barang_tujuan',
                'barang_tujuan.toko as stok_barang_tujuan',
                'barang_tujuan.satuan as satuan_barang_tujuan',
                'barang_tujuan.supplier as barang_supplier_tujuan'
            )
            ->leftJoin('barang as barang_asal', 'itempemakaian.barang_asal', '=', 'barang_asal.kode')
            ->leftJoin('barang as barang_tujuan', 'itempemakaian.barang_tujuan', '=', 'barang_tujuan.kode')
            ->where('itempemakaian.kode_pemakaian', '=', $pemakaian_barang->kode);
            $items = $itemPemakaian->get();
            
            return response()->Json([
                'success' => true,
                'message' => "Detail pemakaian barang {$pemakaian_barang->kode}",
                'data' => $pemakaian_barang,
                'items' => $items
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
