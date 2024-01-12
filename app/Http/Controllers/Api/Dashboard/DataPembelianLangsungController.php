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
use App\Models\{Pembelian,ItemPembelian,Supplier,Barang,Kas};
use Auth;

class DataPembelianLangsungController extends Controller
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

            $query = Pembelian::query()
            ->select(
                'pembelian.*',
                'itempembelian.*',
                'supplier.nama as nama_supplier',
                'supplier.alamat as alamat_supplier'
            )
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->orderByDesc('pembelian.id')
            ->limit(10);

            if ($keywords) {
                $query->where('pembelian.nama', 'like', '%' . $keywords . '%');
            }

            $query->whereDate('pembelian.tanggal', '=', $today);

            $pembelians = $query->orderByDesc('pembelian.id')
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
            $data = $request->all();

            $currentDate = now()->format('ymd');

            $lastIncrement = Pembelian::max('id') ?? 0;
            $increment = $lastIncrement + 1;

            $formattedIncrement = sprintf('%03d', $increment);

            $generatedCode = 'R21-' . $currentDate . $formattedIncrement;
            
            $supplier = Supplier::findOrFail($data['supplier']);

            $barang = Barang::findOrFail($data['barang']);

            $kas = Kas::findOrFail($data['kode_kas']);

            $newPembelian = new Pembelian;
            $newPembelian->tanggal = $currentDate;
            $newPembelian->kode = $generatedCode;
            $newPembelian->supplier = $supplier->kode;
            $newPembelian->kode_kas = $kas->kode;
            $newPembelian->jumlah = $data['jumlah'];
            $newPembelian->lunas = $data['pembayaran'] === 'cash' ? true : false;
            $newPembelian->visa = $data['pembayaran'] === 'cash' ? 'UANG PAS' : 'HUTANG';
            $newPembelian->hutang = 0.00;
            $newPembelian->po = "False";
            $newPembelian->receive = "True";
            $newPembelian->jt = 0.00;
            $newPembelian->keterangan = $data['keterangan'];
            $newPembelian->operator = $data['operator'];

            $newPembelian->save();

            $newItemPembelian = new ItemPembelian;
            $newItemPembelian->kode = $newPembelian->kode;
            $newItemPembelian->kode_barang = $barang->kode;
            $newItemPembelian->nama_barang = $barang->nama;
            $newItemPembelian->satuan = $barang->satuan;
            $newItemPembelian->qty = $data['qty'];
            $newItemPembelian->harga_beli = $barang->hpp;
            $newItemPembelian->harga_toko = $barang->harga_toko;
            $newItemPembelian->harga_cabang = $barang->harga_cabang;
            $newItemPembelian->harga_partai = $barang->harga_partai;
            $newItemPembelian->subtotal = $barang->hpp * $data['qty'];
            $newItemPembelian->isi = $barang->isi;

            if($data['diskon']) {
                $total = $barang['hpp'] * $data['qty'];
                $diskonAmount = $data['diskon'] / 100 * $total;
                $totalSetelahDiskon = $total - $diskonAmount;
                $newItemPembelian->harga_setelah_diskon = $totalSetelahDiskon;
            }

            $newItemPembelian->save();

            if($newPembelian && $newItemPembelian) {
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
                    'notif' => "{$newPembelian->kode}, baru saja ditambahkan 🤙!",
                    'data' => $newPembelian->kode,
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
