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
use App\Models\{Pembelian,ItemPembelian,Supplier,Barang,Kas,Toko};
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
            $today = now()->toDateString();

            $user = Auth::user()->name;

            $query = Pembelian::query()
            ->select(
                'pembelian.*',
                'itempembelian.*',
                'supplier.nama as nama_supplier',
                'supplier.alamat as alamat_supplier',
                'barang.nama as nama_barang',
                'barang.satuan as satuan_barang'
            )
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->orderByDesc('pembelian.id')
            ->limit(10);

            if ($keywords) {
                $query->where('pembelian.kode', 'like', '%' . $keywords . '%');
            }

            $query->whereDate('pembelian.tanggal', '=', $today);

            $pembelians = $query
            ->whereRaw('LOWER(pembelian.operator) like ?', [strtolower('%' . $user . '%')])
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
            $barangs = $data['barangs'];
            // if(is_array($barangs) || is_object($barangs)) {
            //     $dataBarangs = $data['barangs'];
            // } else {}
            $dataBarangs = json_decode($barangs, true);

            $currentDate = now()->format('ymd');

            $lastIncrement = Pembelian::max('id') ?? 0;
            $increment = $lastIncrement + 1;

            $formattedIncrement = sprintf('%03d', $increment);

            $generatedCode = 'R21-' . $currentDate . $formattedIncrement;

            $supplier = Supplier::findOrFail($data['supplier']);

            $barangIds = array_column($dataBarangs, 'id');
            $barangs = Barang::whereIn('id', $barangIds)->get();
            // $updateStokBarang = Barang::findOrFail($data['barang']);
            // $updateStokBarang->toko = $updateStokBarang->toko + $request->qty;
            // $updateStokBarang->save();

            foreach($barangs as $barang) {
                $barangId = $barang->id;
                $qtyToUpdate = 0;

                foreach ($dataBarangs as $dataBarang) {
                    if ($dataBarang['id'] == $barangId) {
                        $qtyToUpdate = $dataBarang['qty'];
                        break;
                    }
                }

                $barang->toko = $barang->toko + $qtyToUpdate;
                $barang->save();
            }

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
            $newPembelian->draft = $data['draft'] ? 1 : 0;
            $newPembelian->supplier = $supplier->kode;
            $newPembelian->kode_kas = $kas->kode;
            $newPembelian->jumlah = $data['jumlah'];
            $newPembelian->bayar = $data['bayar'];
            $newPembelian->diterima = $data['diterima'];
            $newPembelian->lunas = $data['pembayaran'] === 'cash' ? true : false;
            $newPembelian->visa = $data['pembayaran'] === 'cash' ? 'UANG PAS' : 'HUTANG';
            $newPembelian->hutang = 0.00;
            $newPembelian->po = "False";
            $newPembelian->receive = "True";
            $newPembelian->jt = 0.00;
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
            'supplier.nama as nama_supplier',
            'supplier.alamat as alamat_supplier',
            'barang.nama as nama_barang',
            'barang.satuan as satuan_barang'
        )
        ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
        ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
        ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
        ->orderByDesc('pembelian.id')
            // ->whereDate('pembelian.tanggal', '=', $today)
        ->where('pembelian.kode', $kode);

        $barangs = $query->get();
        $pembelian = $query->get()[0];
        // echo "<pre>";
        // var_dump($pembelian);
        // echo "</pre>";
        // die;
        $setting = "";

        switch($type) {
            case "nota-kecil":
            return view('pembelian.nota_kecil', compact('pembelian', 'barangs', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('pembelian.nota_besar', compact('pembelian', 'barangs', 'kode', 'toko', 'nota_type', 'helpers'));
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
    public function show($kode)
    {
        try {
            $today = now()->toDateString();

            $pembelian = Pembelian::query()
            ->select(
                'pembelian.*',
                'itempembelian.*',
                'supplier.nama as nama_supplier',
                'supplier.alamat as alamat_supplier',
                'barang.nama as nama_barang',
                'barang.satuan as satuan_barang'
            )
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->orderByDesc('pembelian.id')
            ->whereDate('pembelian.tanggal', '=', $today)
            ->where('pembelian.kode', $kode)
            ->get();
            return new RequestDataCollect($pembelian);
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
