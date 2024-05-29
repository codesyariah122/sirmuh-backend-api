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
use App\Models\{PemakaianBarang, Barang, ItemPemakaianOrigin, ItemPemakaianDest};
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

            $currentDate = now('Asia/Jakarta')->toDateTimeString();
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
            $newPemakaian->total = $request->total ?? 0;
            $newPemakaian->biaya_operasional = $request->biaya_operasional ?? 0;
            $newPemakaian->harga_proses = $request->harga_proses ?? 0;
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
            ->select('pemakaian_barangs.*')
            ->where('pemakaian_barangs.id', $id);

            $pemakaian_barang = $pemakaianBarang->first();

            $itemPemakaianOrigin = ItemPemakaianOrigin::query()
            ->select('itempemakaianorigin.id', 'itempemakaianorigin.kode_pemakaian', 'itempemakaianorigin.barang as barang_asal', 'itempemakaianorigin.qty as qty_asal', 'itempemakaianorigin.harga', 'itempemakaianorigin.total', 'itempemakaianorigin.supplier', 
                'barang_asal.kode as kode_barang_asal',
                'barang_asal.nama as nama_barang_asal', 
                'barang_asal.toko as stok_barang_asal', 
                'barang_asal.satuan as satuan_barang_asal',
                'barang_asal.supplier as barang_supplier_asal'
            )
            ->leftJoin('barang as barang_asal', 'itempemakaianorigin.barang', '=', 'barang_asal.kode')
            ->where('itempemakaianorigin.kode_pemakaian', '=', $pemakaian_barang->kode);
            $itemOrigins = $itemPemakaianOrigin->get();

            $itemPemakaianDest = ItemPemakaianDest::query()
            ->select('itempemakaiandest.id', 'itempemakaiandest.kode_pemakaian', 'itempemakaiandest.barang as barang_tujuan', 'itempemakaiandest.qty as qty_tujuan', 'itempemakaiandest.harga', 'itempemakaiandest.total', 'itempemakaiandest.supplier', 
                'barang_dest.kode as kode_barang_dest',
                'barang_dest.nama as nama_barang_dest', 
                'barang_dest.toko as stok_barang_dest', 
                'barang_dest.satuan as satuan_barang_dest',
                'barang_dest.supplier as barang_supplier_asal'
            )
            ->leftJoin('barang as barang_dest', 'itempemakaiandest.barang', '=', 'barang_dest.kode')
            ->where('itempemakaiandest.kode_pemakaian', '=', $pemakaian_barang->kode);
            $itemDests = $itemPemakaianDest->get();

            
            return response()->Json([
                'success' => true,
                'message' => "Detail pemakaian barang {$pemakaian_barang->kode}",
                'data' => $pemakaian_barang,
                'items' => [
                    'origins' => $itemOrigins,
                    'dests' => $itemDests
                ]
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
        try {
            $userOnNotif = Auth::user();

            $updatePemakaian = PemakaianBarang::findOrFail($id);
            $updatePemakaian->draft = 0;
            $updatePemakaian->keperluan = $request->keperluan ?? NULL;
            $updatePemakaian->keterangan = $request->keterangan ?? NULL;
            // $updatePemakaian->total = $request->total ?? 0;
            $updatePemakaian->biaya_operasional = $request->biaya_operasional ?? 0;
            $updatePemakaian->harga_proses = $request->harga_proses ?? 0;
            $updatePemakaian->harga_cetak = $request->harga_cetak ?? 0;
            $updatePemakaian->save();

            foreach($request->origins as $origin) {
                $barangOriginUpdate = Barang::findOrFail($origin['id_barang']);
                $barangOriginUpdate->toko = intval($barangOriginUpdate->toko) - $origin['qty'];
                $barangOriginUpdate->save();
            }

            foreach($request->dests as $dest) {
                $barangDestUpdate = Barang::findOrFail($dest['id_barang']);
                $barangDestUpdate->toko = intval($barangDestUpdate->toko) + $dest['qty'];
                $barangDestUpdate->hpp = $request->harga_cetak;
                $barangDestUpdate->save();
            }

            $data_event = [
                'routes' => 'pemakaian-barang',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Pemakaian barang {$updatePemakaian->kode}, successfully added 🤙!",
                'data' =>$updatePemakaian,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Pemakaian barang {$updatePemakaian->kode}, successfully updated ✨!",
                'data' => $updatePemakaian
            ], 200);
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
