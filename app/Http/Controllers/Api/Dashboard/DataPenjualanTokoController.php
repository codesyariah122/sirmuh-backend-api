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
use App\Models\{Penjualan,ItemPenjualan,Pelanggan,Barang,Kas};
use Auth;

class DataPenjualanTokoController extends Controller
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

            $query = Penjualan::query()
            ->select(
                'penjualan.*',
                'itempenjualan.*',
                'pelanggan.nama as nama_pelanggan',
                'pelanggan.alamat as alamat_pelanggan'
            )
            ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->orderByDesc('penjualan.id')

            ->limit(10);

            if ($keywords) {
                $query->where('penjualan.kode', 'like', '%' . $keywords . '%');
            }

            $query->whereDate('penjualan.tanggal', '=', $today);

            $penjualans = $query
            ->where('penjualan.pelanggan', '!=', '--')
            ->orderByDesc('penjualan.id')
            ->paginate(10);

            return new ResponseDataCollect($penjualans);

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
            $data = $request->all();

            $currentDate = now()->format('ymd');

            $lastIncrement = Penjualan::max('id') ?? 0;
            $increment = $lastIncrement + 1;

            $formattedIncrement = sprintf('%03d', $increment);

            $generatedCode = 'R43-' . $currentDate . $formattedIncrement;
            
            $pelanggan = Pelanggan::findOrFail($data['pelanggan']);


            $barang = Barang::findOrFail($data['barang']);
            $check_stok = intval($barang->toko);
            $qty = intval($request->qty);
            // var_dump($qty > $check_stok); die;
            if($qty > $check_stok) {
                $barangOutOfStok = Barang::select("kode", "nama", "photo")->findOrFail($data['barang']);
                return response()->json([
                    'error' => true,
                    'message' => 'Stok barang tidak tersedia',
                    'data' => $barangOutOfStok
                ]);
            }

            $updateStokBarang = Barang::findOrFail($data['barang']);
            $updateStokBarang->toko = $updateStokBarang->toko - $request->qty;
            $updateStokBarang->save();

            $kas = Kas::findOrFail($data['kode_kas']);

            $newPenjualan = new Penjualan;
            $newPenjualan->tanggal = $currentDate;
            $newPenjualan->kode = $request->ref_no ? $request->ref_no : $generatedCode;
            $newPenjualan->pelanggan = $pelanggan->kode;
            $newPenjualan->kode_kas = $kas->kode;
            $newPenjualan->subtotal = $barang->hpp * $data['qty'];
            $newPenjualan->kembali = $data['bayar'] - ($barang->hpp * $data['qty']);
            $newPenjualan->jumlah = $data['bayar'] - ($data['bayar'] - ($barang->hpp * $data['qty'])) ;
            $newPenjualan->bayar = $data['bayar'];
            $newPenjualan->lunas = $data['pembayaran'] === 'cash' ? "True" : "False";
            $newPenjualan->visa = $data['pembayaran'] === 'cash' ? 'UANG PAS' : 'HUTANG';
            $newPenjualan->piutang = 0.00;
            $newPenjualan->po = "False";
            $newPenjualan->receive = "False";
            $newPenjualan->jt = 0.00;
            $newPenjualan->keterangan = $data['keterangan'];
            $newPenjualan->operator = $data['operator'];

            $newPenjualan->save();

            $newItemPenjualan = new ItemPenjualan;
            $newItemPenjualan->kode =$newPenjualan->kode;
            $newItemPenjualan->kode_barang = $barang->kode;
            $newItemPenjualan->nama_barang = $barang->nama;
            $newItemPenjualan->satuan = $barang->satuan;
            $newItemPenjualan->qty = $data['qty'];
            $newItemPenjualan->harga = $barang->hpp;
            $newItemPenjualan->subtotal = $barang->hpp * $data['qty'];
            $newItemPenjualan->isi = $barang->isi;

            // $updateQty = ItemPenjualan::findOrFail($newItemPenjualan->id);
            // $updateQty->qty = $newItemPenjualan->qty + $data['qty'];

            if($data['diskon']) {
                $total = $barang['hpp'] * $data['qty'];
                $diskonAmount = $data['diskon'] / 100 * $total;
                $totalSetelahDiskon = $total - $diskonAmount;
                $newItemPenjualan->harga_setelah_diskon = $totalSetelahDiskon;
            }

            $newItemPenjualan->save();

            $userOnNotif = Auth::user();

            if($newPenjualan && $newItemPenjualan) {
               $newPenjualanSaved =  Penjualan::query()
               ->select(
                'penjualan.*',
                'itempenjualan.*',
                'pelanggan.nama as nama_pelanggan',
                'pelanggan.alamat as alamat_pelanggan'
            )
               ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
               ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
               ->where('penjualan.id',$newPenjualan->id)
               ->get();

               $data_event = [
                'routes' => 'penjualan-toko',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Penjualan dengan kode {$newPenjualan->kode}, baru saja ditambahkan 🤙!",
                'data' =>$newPenjualan->kode,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return new RequestDataCollect($newPenjualanSaved);
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
