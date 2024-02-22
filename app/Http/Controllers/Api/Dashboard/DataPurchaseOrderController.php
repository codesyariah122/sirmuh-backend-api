<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{Pembelian,ItemPembelian,Supplier,Barang,Kas,Toko,Hutang,ItemHutang,PembayaranAngsuran};
use Auth;
use PDF;

class DataPurchaseOrderController extends Controller
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

            $user = Auth::user();

            $query = Pembelian::query()
            ->select(
                'pembelian.id','pembelian.tanggal','pembelian.kode','pembelian.kode_kas','pembelian.supplier','pembelian.jumlah','pembelian.operator','pembelian.jt','pembelian.lunas', 'pembelian.visa', 'pembelian.hutang','pembelian.keterangan','pembelian.diskon','pembelian.tax',
            )
            ->limit(10);

            if ($keywords) {
                $query->where('pembelian.kode', 'like', '%' . $keywords . '%');
            }

            $query->whereDate('pembelian.tanggal', '=', $today);

            $pembelians = $query
            ->where(function ($query) use ($user) {
                if ($user->role !== 1) {
                    $query->whereRaw('LOWER(pembelian.operator) like ?', [strtolower('%' . $user->name . '%')]);
                } 
            })
            ->where('pembelian.po', '=', 'True')
            ->orderByDesc('pembelian.id')
            ->paginate(10);

            return new ResponseDataCollect($pembelians);

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
                'kode_kas' => 'required',
                'barangs' => 'required',
            ]);


            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $data = $request->all();
            // echo "<pre>";
            // var_dump($data); die;
            // echo "</pre>";

            $barangs = $data['barangs'];
            
            $dataBarangs = json_decode($barangs, true);

            $currentDate = now()->format('ymd');

            $lastIncrement = Pembelian::max('id') ?? 0;
            $increment = $lastIncrement + 1;

            $formattedIncrement = sprintf('%03d', $increment);

            $supplier = Supplier::findOrFail($data['supplier']);

            $barangIds = array_column($dataBarangs, 'id');
            $barangs = Barang::whereIn('id', $barangIds)->get();
            // $updateStokBarang = Barang::findOrFail($data['barang']);
            // $updateStokBarang->toko = $updateStokBarang->toko + $request->qty;
            // $updateStokBarang->save();

            $kas = Kas::findOrFail($data['kode_kas']);

            if($kas->saldo < $data['diterima']) {
                return response()->json([
                    'error' => true,
                    'message' => "Saldo tidak mencukupi!!"
                ]);
            }

            $dataItemPembelian = ItemPembelian::whereKode($data['ref_code'])->first();
            $subtotal = intval($dataItemPembelian->subtotal);

            $newPembelian = new Pembelian;
            $newPembelian->tanggal = $data['tanggal'] ? $data['tanggal'] : $currentDate;
            $newPembelian->kode = $data['ref_code'];
            $newPembelian->draft = 0;
            // $newPembelian->supplier = $supplier->kode;
            $newPembelian->kode_kas = $kas->kode;
            $newPembelian->jumlah = $data['jumlah'];
            $newPembelian->bayar = $data['bayar'];
            $newPembelian->diterima = $data['diterima'];
            $newPembelian->lunas = "False";
            $newPembelian->visa = "HUTANG";
            $newPembelian->hutang = $data['hutang'];
            $newPembelian->po = 'True';
            $newPembelian->receive = "False";
            $newPembelian->jt = $data['jt'];
            $newPembelian->keterangan = $data['keterangan'] ? $data['keterangan'] : NULL;
            $newPembelian->operator = $data['operator'];

            $newPembelian->save();
            
            $updateDrafts = ItemPembelian::whereKode($newPembelian->kode)->get();
            foreach($updateDrafts as $idx => $draft) {
                $updateDrafts[$idx]->draft = 0;
                $updateDrafts[$idx]->save();
            }

            $diterima = intval($newPembelian->diterima);
            $updateKas = Kas::findOrFail($data['kode_kas']);
            $updateKas->saldo = intval($updateKas->saldo) - $diterima;
            $updateKas->save();

            $userOnNotif = Auth::user();

            if($newPembelian) {
                $newPembelianSaved =  Pembelian::query()
                ->select(
                    'pembelian.*',
                    'itempembelian.*',
                    'supplier.nama as nama_supplier',
                    'supplier.alamat as alamat_supplier'
                )
                ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
                ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
                ->where('pembelian.id', $newPembelian->id)
                ->get();

                $data_event = [
                    'routes' => 'purchase-order',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "Pembelian dengan kode {$newPembelian->kode}, baru saja ditambahkan 🤙!",
                    'data' => $newPembelian->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                return new RequestDataCollect($newPembelianSaved);
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
            $pembelian = Pembelian::query()
            ->select(
                'pembelian.id','pembelian.kode', 'pembelian.tanggal', 'pembelian.supplier', 'pembelian.kode_kas', 'pembelian.keterangan', 'pembelian.diskon','pembelian.tax', 'pembelian.jumlah', 'pembelian.bayar', 'pembelian.diterima','pembelian.operator', 'pembelian.jt as tempo' ,'pembelian.lunas', 'pembelian.visa', 'pembelian.hutang', 'pembelian.po', 'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama','kas.saldo as kas_saldo'
            )
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
            ->where('pembelian.id', $id)
            ->where('pembelian.po', 'True')
            ->first();

            $items = ItemPembelian::query()
            ->select('itempembelian.*','barang.id as id_barang','barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.hpp as harga_beli_barang','barang.toko as stok_barang','barang.expired as expired_barang', 'barang.ada_expired_date','supplier.id as id_supplier','supplier.nama as nama_supplier','supplier.alamat as alamat_supplier')
            ->leftJoin('supplier', 'itempembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->where('itempembelian.kode', $pembelian->kode)
            ->orderByDesc('itempembelian.id')
            ->get();

            return response()->json([
                'success' => true,
                'message' => "Detail pembelian {$pembelian->kode}",
                'data' => $pembelian,
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
        try {
            $data = $request->all();

            $bayar = preg_replace("/[^0-9]/", "", $data['bayar']);
            $diterima = preg_replace("/[^0-9]/", "", $data['diterima']);
            $hutang = $data['hutang'];

            $updatePembelian = Pembelian::findOrFail($id);

            $kas = Kas::whereKode($data['kode_kas'])->first();
        
            if(intval($kas->saldo) < $diterima) {
                return response()->json([
                    'error' => true,
                    'message' => "Saldo tidak mencukupi!!"
                ]);
            }

            $updatePembelian->draft = 0;
            $updatePembelian->kode_kas = $kas->kode;
            $updatePembelian->jumlah = $data['jumlah'] ? $data['jumlah'] : $updatePembelian->jumlah;
            $updatePembelian->bayar = $data['bayar'] ? intval($bayar) : $updatePembelian->bayar;
            $updatePembelian->diterima = $data['diterima'] ? intval($diterima) : $updatePembelian->diterima;
            if($diterima > $updatePembelian->jumlah) {
                $updatePembelian->lunas = "True";
                $updatePembelian->visa = "LUNAS";
            } else if($diterima == $updatePembelian->jumlah) {
                $updatePembelian->lunas = "True";
                $updatePembelian->visa = "UANG PAS";
            } else {
                $updatePembelian->lunas = "False";
                $updatePembelian->visa = "HUTANG";
                $updatePembelian->hutang = $hutang;

                $dataHutang = Hutang::whereKode($updatePembelian->kode)->first();
                $dataItemHutang = ItemHutang::whereKode($dataHutang->kode)->first();
                $updateHutang = Hutang::findOrFail($dataHutang->id);
                $updateHutang->jumlah = $updatePembelian->hutang;
                $updateHutang->save();
                $updateItemHutang = ItemHutang::findOrFail($dataItemHutang->id);
                $updateItemHutang->jumlah_hutang = $updatePembelian->hutang;
                $updateItemHutang->jumlah = $updatePembelian->hutang;
                $updateItemHutang->save();
                $dataPembayaranAngsuran = PembayaranAngsuran::where('kode', $dataHutang->kode)
                ->where('angsuran_ke', 1)
                ->first();
                $updateAngsuran = PembayaranAngsuran::findOrFail($dataPembayaranAngsuran->id);
                $updateAngsuran->bayar_angsuran = $updatePembelian->diterima;
                $updateAngsuran->jumlah = $updatePembelian->hutang;
                $updateAngsuran->save();
            }

            $updatePembelian->save();

            $updateKas = Kas::findOrFail($kas->id);
            $updateKas->saldo = intval($kas->saldo) + intval($updatePembelian->diterima);
            $updateKas->save();

            if($updatePembelian) {
                $userOnNotif = Auth::user();

                $updatePembelianSaved =  Pembelian::query()
                ->select(
                    'pembelian.*',
                    'itempembelian.*',
                    'supplier.nama as nama_supplier',
                    'supplier.alamat as alamat_supplier'
                )
                ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
                ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
                ->where('pembelian.id', $updatePembelian->id)
                ->first();

                $data_event = [
                    'routes' => 'pembelian-langsung',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "Pembelian dengan kode {$updatePembelian->kode}, berhasil diupdate 🤙!",
                    'data' => $updatePembelian->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data pembelian , berhasil diupdate 👏🏿"
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
        //
    }
}
