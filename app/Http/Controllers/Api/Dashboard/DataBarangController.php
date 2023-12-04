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
use App\Models\{Barang, Kategori, SatuanBeli, SatuanJual, Supplier};

class DataBarangController extends Controller 
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $feature_helpers;

    public function __construct()
    {
        $this->feature_helpers = new WebFeatureHelpers;
    }
    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $kategori = $request->query('kategori');
            $endDate = $request->query('tgl_terakhir');
            $barcode = $request->query('barcode');

            if($keywords) {
                $barangs = Barang::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
                ->where('nama', 'like', '%'.$keywords.'%')
                // ->orderByDesc('harga_toko')
                ->orderByDesc('id')
                ->with('suppliers')
                ->paginate(10);
            } else if($kategori){
                $barangs = Barang::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
                ->where('kategori', $kategori)
                // ->orderByDesc('harga_toko')
                ->orderByDesc('id')
                ->with('suppliers')
                ->paginate(10);
            } else if($endDate) {
                $barangs = Barang::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
                ->where('tgl_terakhir', $endDate)
                // ->orderByDesc('harga_toko')
                ->orderByDesc('id')
                ->with('suppliers')
                ->paginate(10);
            } else if($barcode) {
                $barangs = Barang::whereKodeBarcode($barcode)->get();
            }else {
                $barangs = Barang::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
                ->with("kategoris")
                // ->orderByDesc('harga_toko')
                ->with('suppliers')
                ->orderByDesc('id')
                ->paginate(10);
            }

            foreach ($barangs as $item) {
                $kodeBarcode = $item->kode_barcode;
                $this->feature_helpers->generateQrCode($kodeBarcode);
                // $this->feature_helpers->generateBarcode($kodeBarcode);
            }

            return new ResponseDataCollect($barangs);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function category_lists()
    {
        try {
            $categories = Barang::whereNull('deleted_at')
            ->orderByDesc('id')
            ->pluck('kategori')
            ->unique()
            ->values()
            ->all();
            return new ResponseDataCollect($categories);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function detail_by_barcode($barcode)
    {
        try {
            $detailBarang = Barang::whereKodeBarcode($barcode)->get();
            return new ResponseDataCollect($detailBarang);
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
    public function generateAcronym($inputString) {
        $words = explode(' ', $inputString);
        $acronym = '';

        foreach ($words as $word) {
            $acronym .= strtoupper(substr($word, 0, 1));
        }

        return $acronym;
    }

    public function store(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'nama' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_barang = Barang::whereNama($request->nama)->get();


            if(count($check_barang) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Barang dengan nama {$request->nama}, sudah ada / tersedia 🤦!"
                ]);
            }

            $newBarang = new Barang;
            $kode = explode(' ', $request->nama);
            $substringArray = [];

            foreach ($kode as $i) {
                $substringArray[] = substr($i, 0, 1);
            }

            $newBarang->kode = strtoupper(implode('', $substringArray));


            $newBarang->nama = $request->nama;

            // if ($request->hasFile('photo')) {
            //     $photoPath = $request->file('photo')->store('barang');
            //     $data['photo'] = $photoPath;
            // }

            if ($request->file('photo')) {
                $photo = $request->file('photo');
                $file = $photo->store(trim(preg_replace('/\s+/', '', '/products')), 'public');
                $newBarang->photo = $file;
            }
            
            $checkKategori = Kategori::where('kode', $request->kategori)->count();

            if($checkKategori === 0) {
                $newKategori = new Kategori;
                $newKategori->kode = $request->kategori;
                $newKategori->save();
                $kategoriBarang = Kategori::findOrFail($newKategori->id);
                $newBarang->kategori = $kategoriBarang->kode;
            } else {
                $kategoriBarang = Kategori::where('kode', $request->kategori)->first();
                $newBarang->kategori = $kategoriBarang->kode;
            }

            $checkSatuanBeli = SatuanBeli::where('nama', $request->satuan_beli)->count();
            if($checkSatuanBeli === 0) {
                $newSatuanBeli = new SatuanBeli;
                $newSatuanBeli->nama = $request->satuan_beli;
                $newSatuanBeli->save();
                $satuanBeliBarang = SatuanBeli::where('nama', $newSatuanBeli->nama)->first();
                $newBarang->satuanbeli = $satuanBeliBarang->nama;
            } else {
                $satuanBeliBarang = SatuanBeli::where('nama', $request->satuan_beli)->first();
                $newBarang->satuanbeli = $satuanBeliBarang->nama;
            }

            $checkSatuanJual = SatuanJual::where('nama', $request->satuan_jual)->count();
            if($checkSatuanJual === 0) {
                $newSatuanJual = new SatuanJual;
                $newSatuanJual->nama = $request->satuan_jual;
                $newSatuanJual->save();
                $satuanJualBarang = SatuanJual::where('nama', $newSatuanJual->nama)->first();
                $newBarang->satuan = $satuanJualBarang->nama;
            } else {
                $satuanJualBarang = SatuanJual::where('nama', $request->satuan_jual)->first();
                $newBarang->satuan = $satuanJualBarang->nama;
            }


            if($request->ada_expired_date) {
                $newBarang->ada_expired_date = "True";
                $newBarang->expired = $request->expired;
            } else {
                $newBarang->ada_expired_date = "False";
                $newBarang->expired = null;
            }
            $newBarang->isi = $request->isi;
            $newBarang->toko = $request->stok;
            $newBarang->hpp = $request->harga_beli;
            $newBarang->harga_toko = $request->harga_jual;
            $newBarang->diskon = $request->diskon;

            $checkSupplier = Supplier::where('nama', $request->supplier)->count();
            if($checkSupplier > 0) {
                $supplierBarang = Supplier::where('nama', $request->supplier)->first();
                $newBarang->supplier = $supplierBarang->kode;
            } else {
                $newSupplier = new Supplier;
                $kode = $this->generateAcronym($request->supplier);
                $newSupplier->kode = $kode;
                $newSupplier->nama = $request->supplier;
                $newSupplier->save();
                $newSupplierBarang = Supplier::findOrFail($newSupplier->id);
                $newBarang->supplier = $newSupplierBarang->kode;
            }

            $newBarang->kode_barcode = $newBarang->kode;
            $newBarang->tgl_terakhir = Carbon::now()->format('Y-m-d');

            $newBarang->save();

            if ($newBarang) {
                $supplierBarang = Supplier::where('nama', $request->supplier)->first();
                $newBarang->suppliers()->sync($supplierBarang->id);


                $newBarangSaved = Barang::where('id', $newBarang->id)
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
                ->with('suppliers')
                ->get();

                $data_event = [
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "{$newBarang->nama}, baru saja ditambahkan 🤙!",
                    'data' => $newBarang->nama,
                ];

                event(new EventNotification($data_event));

                return new RequestDataCollect($newBarangSaved);
            } else {
                return response()->json(['message' => 'Gagal menyimpan data barang.'], 500);
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
       try {
        $delete_barang = Barang::whereNull('deleted_at')
        ->findOrFail($id);

        $delete_barang->delete();

        $data_event = [
            'alert' => 'error',
            'type' => 'removed',
            'notif' => "{$delete_barang->nama}, has move to trash, please check trash!"
        ];

        event(new EventNotification($data_event));

        return response()->json([
            'success' => true,
            'message' => "Data barang {$delete_barang->nama} has move to trash, please check trash"
        ]);
    } catch (\Throwable $th) {
        throw $th;
    }
}
}
