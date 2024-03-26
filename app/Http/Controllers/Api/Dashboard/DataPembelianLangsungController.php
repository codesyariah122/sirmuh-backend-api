<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{Roles,Pembelian,ItemPembelian,Supplier,Barang,Kas,Toko,Hutang,ItemHutang,PembayaranAngsuran,PurchaseOrder,SetupPerusahaan};
use Auth;
use PDF;

class DataPembelianLangsungController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $helpers;

    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
    }

    public function data($id)
    {
        try {
            $barang = Barang::findOrFail($id);
            var_dump($barang);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $supplier = $request->query('supplier');
            $viewAll = $request->query('view_all');
            $today = now()->toDateString();
            $dateTransaction = $request->query('date_transaction');

            $user = Auth::user();

            $query = Pembelian::query()
            ->select(
                'pembelian.id','pembelian.tanggal','pembelian.kode','pembelian.jumlah','pembelian.operator','pembelian.jt','pembelian.lunas', 'pembelian.visa', 'pembelian.hutang','pembelian.keterangan','pembelian.diskon','pembelian.tax','pembelian.supplier', 'supplier.nama as nama_supplier'
            )
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->limit(10);

            if ($dateTransaction) {
                $query->whereDate('pembelian.tanggal', '=', $dateTransaction);
            }

            if ($keywords) {
                $query->where('pembelian.kode', 'like', '%' . $keywords . '%');
            }

            if ($supplier) {
                $query->where('pembelian.supplier', 'like', '%' . $supplier . '%');
            }

            if($viewAll === false || $viewAll === "false") {
                $query->whereDate('pembelian.tanggal', '=', $today);
            }

            $pembelians = $query
            ->where(function ($query) use ($user) {
                if ($user->role !== 1) {
                    $query->whereRaw('LOWER(pembelian.operator) like ?', [strtolower('%' . $user->name . '%')]);
                } 
            })
            ->where('pembelian.po', '=', 'False')
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
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));

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

            $newPembelian = new Pembelian;
            $newPembelian->tanggal = $data['tanggal'] ? $data['tanggal'] : $currentDate;
            $newPembelian->kode = $data['ref_code'] ? $data['ref_code'] : $generatedCode;
            $newPembelian->draft = 0;
            $newPembelian->supplier = $supplier->kode;
            $newPembelian->kode_kas = $kas->kode;

            $newPembelian->jumlah = $data['jumlah'];
            $newPembelian->bayar = $data['bayar'];
            $newPembelian->diterima = $data['diterima'];

            // echo "<pre>";
            // var_dump($data['masuk_hutang']); 
            // echo "</pre>";
            // die;

            if($data['pembayaran'] !== "cash") {
                $newPembelian->lunas = "False";
                $newPembelian->visa = 'HUTANG';
                $newPembelian->hutang = $data['hutang'];
                $newPembelian->po = 'False';
                $newPembelian->receive = "True";
                $newPembelian->jt = $data['jt'];

                // Masuk ke hutang
                $dataPerusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
                $masuk_hutang = new Hutang;
                $masuk_hutang->kode = $dataPerusahaan->kd_bayar_hutang.'-'. $currentDate . $randomNumber;
                $masuk_hutang->kd_beli = $data['ref_code'];
                $masuk_hutang->tanggal = $currentDate;
                $masuk_hutang->supplier = $supplier->kode;
                $masuk_hutang->jumlah = $data['hutang'];
                $masuk_hutang->bayar = $data['bayar'];
                $masuk_hutang->kode_kas = $newPembelian->kode_kas;
                $masuk_hutang->operator = $data['operator'];
                $masuk_hutang->save();

                $item_hutang = new ItemHutang;
                $item_hutang->kode = $masuk_hutang->kode;
                $item_hutang->kd_beli = $data['ref_code'];
                $item_hutang->kode_hutang = $masuk_hutang->kode;
                $item_hutang->tgl_hutang = $currentDate;
                $item_hutang->jumlah_hutang = $masuk_hutang->jumlah;
                $item_hutang->jumlah = $masuk_hutang->jumlah;
                $item_hutang->save();

                $angsuranTerakhir = PembayaranAngsuran::where('kode', $masuk_hutang->kode)
                ->orderBy('angsuran_ke', 'desc')
                ->first();

                $angsuranKeBaru = ($angsuranTerakhir) ? $angsuranTerakhir->angsuran_ke + 1 : 1;

                $angsuran = new PembayaranAngsuran;
                $angsuran->kode = $masuk_hutang->kode;
                $angsuran->tanggal = $masuk_hutang->tanggal;
                $angsuran->angsuran_ke = $angsuranKeBaru;
                $angsuran->kode_pelanggan = NULL;
                $angsuran->kode_faktur = NULL;
                $angsuran->bayar_angsuran = $data['diterima'];
                $angsuran->jumlah = $item_hutang->jumlah_hutang;
                $angsuran->save();

                $updateSaldoSupplier = Supplier::findOrFail($supplier->id);
                $updateSaldoSupplier->saldo_hutang = $supplier->saldo_hutang + $data['hutang'];
                $updateSaldoSupplier->save();
            } else {            
                $newPembelian->lunas = $data['pembayaran'] == 'cash' ? "True" : "False";
                $newPembelian->visa = "LUNAS";
                $newPembelian->hutang = $data['hutang'];
                $newPembelian->po = $data['pembayaran'] == 'cash' ? 'False' : 'True';
                $newPembelian->receive = "True";
                $newPembelian->jt = $data['jt'];
            }

            $newPembelian->keterangan = $data['keterangan'] ? $data['keterangan'] : NULL;
            $newPembelian->operator = $data['operator'];

            $newPembelian->save();
            
            $updateDrafts = ItemPembelian::whereKode($newPembelian->kode)->get();
            foreach($updateDrafts as $idx => $draft) {
                $updateDrafts[$idx]->draft = 0;
                $updateDrafts[$idx]->save();
            }
            
            if($data['pembayaran'] !== "cash") {
                $diterima = intval($newPembelian->diterima);
                $updateKas = Kas::findOrFail($data['kode_kas']);
                $updateKas->saldo = intval($updateKas->saldo) - $data['bayar'];
                $updateKas->save();
            } else {                
                $diterima = intval($newPembelian->diterima);
                $updateKas = Kas::findOrFail($data['kode_kas']);
                $updateKas->saldo = intval($updateKas->saldo) - intval($data['jumlah']);
                $updateKas->save();
            }

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
                    'routes' => 'pembelian-langsung',
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

    public function cetak_nota($type, $kode, $id_perusahaan)
    {
        $ref_code = $kode;
        $nota_type = $type === 'nota-kecil' ? "Nota Kecil": "Nota Besar";
        $helpers = $this->helpers;
        $today = now()->toDateString();
        $toko = Toko::whereId($id_perusahaan)
        ->select("name","logo","address","kota","provinsi")
        ->first();

        // echo "<pre>";
        // var_dump($toko['name']); die;
        // echo "</pre>";

        $query = Pembelian::query()
        ->select(
            'pembelian.*',
            'itempembelian.*',
            'supplier.kode as kode_supplier',
            'supplier.nama as nama_supplier',
            'supplier.alamat as alamat_supplier',
            'barang.nama as nama_barang',
            'barang.satuan as satuan_barang'
        )
        ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
        ->leftJoin('supplier', 'itempembelian.supplier', '=', 'supplier.kode')
        ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
        ->orderByDesc('pembelian.id')
            // ->whereDate('pembelian.tanggal', '=', $today)
        ->where('pembelian.kode', $kode);

        $barangs = $query->get();
        $pembelian = $query->get()[0];
       
        foreach($barangs as $barang) {            
            $orders = PurchaseOrder::where('kode_po', $kode)
            ->where('kode_barang', $barang->kode_barang)
            ->get()->sum('qty');
        }
        $setting = "";

        // echo "<pre>";
        // var_dump($orders);
        // echo "</pre>";
        // die;

        switch($type) {
            case "nota-kecil":
            return view('pembelian.nota_kecil', compact('pembelian', 'barangs', 'orders', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('pembelian.nota_besar', compact('pembelian', 'barangs', 'orders', 'kode', 'toko', 'nota_type', 'helpers'));
            $pdf->setPaper(0,0,609,440, 'potrait');
            return $pdf->stream('Transaksi-'. $pembelian->kode .'.pdf');
            break;
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
            ->where('pembelian.po', 'False')
            ->first();

            $items = ItemPembelian::query()
            ->select('itempembelian.*','barang.id as id_barang','barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.hpp as harga_beli_barang', 'barang.toko as stok_barang','barang.expired as expired_barang', 'barang.ada_expired_date','supplier.id as id_supplier','supplier.kode as kode_supplier','supplier.nama as nama_supplier','supplier.alamat as alamat_supplier')
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

            if(gettype($data['bayar']) === 'string') {
                $bayar = preg_replace("/[^0-9]/", "", $data['bayar']);
            } else {
                $bayar = intval($data['bayar']);
            }

            if(gettype($data['diterima']) === 'string') {
                $diterima = preg_replace("/[^0-9]/", "", $data['diterima']);
            } else {
                $diterima = intval($data['diterima']);
            }

            if(gettype($data['total']) === 'string') {
                $total = preg_replace("/[^0-9]/", "", $data['total']);
            } else {
                $total = intval($data['total']);
            }

            $currentDate = now()->format('ymd');
            
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
            $updatePembelian->jumlah = $data['total'] ? $total : $updatePembelian->jumlah;
            $updatePembelian->bayar = $data['bayar'] ? intval($bayar) : $updatePembelian->bayar;
            $updatePembelian->diterima = $data['diterima'] ? intval($data['diterima']) : $updatePembelian->diterima;

            if($data['masuk_hutang']) {
                $updatePembelian->jt = $data['jt'];
                $updatePembelian->lunas = "False";
                $updatePembelian->visa = "HUTANG";
                $updatePembelian->hutang = $data['hutang'];

            } else {
                if($diterima > $updatePembelian->jumlah) {
                    $updatePembelian->lunas = "True";
                    $updatePembelian->visa = "LUNAS";
                } else if($diterima == $updatePembelian->jumlah) {
                    $updatePembelian->lunas = "True";
                    $updatePembelian->visa = "UANG PAS";
                } else {
                    $updatePembelian->lunas = "True";
                }
            }

            $updatePembelian->save();

            $updateKas = Kas::findOrFail($kas->id);
            $updateKas->saldo = intval($kas->saldo) - intval($updatePembelian->diterima);
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
        try {
           $user = Auth::user();

            $userRole = Roles::findOrFail($user->role);

            if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {                
                // $delete_pembelian = Pembelian::whereNull('deleted_at')
                // ->findOrFail($id);
                $delete_pembelian = Pembelian::findOrFail($id);

                $dataHutang = Hutang::where('kode', $delete_pembelian->kode)->first();

                if($dataHutang) {
                    $delete_hutang = Hutang::findOrFail($dataHutang->id);
                    $delete_hutang->delete();

                    $hutangItems = ItemHutang::where('kode', $delete_pembelian->kode)->get();
                    foreach($hutangItems as $itemHutang) {                    
                        $deleteItemHutang = ItemHutang::findOrFail($itemHutang->id);
                        $deleteItemHutang->delete();
                    }

                    $angsuranItems = PembayaranAngsuran::where('kode', $delete_pembelian->kode)->get();
                    foreach($angsuranItems as $itemAngsuran) {                    
                        $deleteAngsuran = PembayaranAngsuran::findOrFail($itemAngsuran->id);
                        $deleteAngsuran->delete();
                    }
                }
                
                $delete_pembelian->delete();

                $pembelianItems = ItemPembelian::where('kode', $delete_pembelian->kode)->get();
                foreach($pembelianItems as $itemPembelian) {                
                    $deleteItem = ItemPembelian::findOrFail($itemPembelian->id);
                    $deleteItem->delete();

                    $dataBarangs = Barang::where('kode', $itemPembelian->kode_barang)->get();
                    foreach($dataBarangs as $barang) {                        
                        $updateStokBarang = Barang::findOrFail($barang->id);
                        $updateStokBarang->toko = $barang->toko - $itemPembelian->qty;
                        $updateStokBarang->last_qty = $barang->toko;
                        $updateStokBarang->save();
                    }
                }

                $dataKas = Kas::where('kode', $delete_pembelian->kode_kas)->first();
                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = $dataKas->saldo + $delete_pembelian->jumlah;
                $updateKas->save();

                $data_event = [
                    'alert' => 'error',
                    'routes' => 'pembelian-langsung',
                    'type' => 'removed',
                    'notif' => "Pembelian dengan kode, {$delete_pembelian->kode}, successfully deleted!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Pembelian dengan kode, {$delete_pembelian->kode} berhasil dihapus 👏"
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
