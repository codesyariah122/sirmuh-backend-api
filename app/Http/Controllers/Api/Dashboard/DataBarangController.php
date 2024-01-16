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
use Auth;

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
            'kategori' => 'required',
            'supplier' => 'required',
            'satuanbeli' => 'required',
            'hargabeli' => 'required',
            'satuanjual' => 'required',
            'hargajual' => 'required',
            'isi' => 'required',
            'stok' => 'required',
            'photo' => 'image|mimes:jpg,png,jpeg|max:2048',
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

        $checkSatuanBeli = SatuanBeli::where('nama', $request->satuanbeli)->count();
        if($checkSatuanBeli === 0) {
            $newSatuanBeli = new SatuanBeli;
            $newSatuanBeli->nama = $request->satuanbeli;
            $newSatuanBeli->save();
            $satuanBeliBarang = SatuanBeli::where('nama', $newSatuanBeli->nama)->first();
            $newBarang->satuanbeli = $satuanBeliBarang->nama;
        } else {
            $satuanBeliBarang = SatuanBeli::where('nama', $request->satuanbeli)->first();
            $newBarang->satuanbeli = $satuanBeliBarang->nama;
        }

        $checkSatuanJual = SatuanJual::where('nama', $request->satuanjual)->count();
        if($checkSatuanJual === 0) {
            $newSatuanJual = new SatuanJual;
            $newSatuanJual->nama = $request->satuan_jual;
            $newSatuanJual->save();
            $satuanJualBarang = SatuanJual::where('nama', $newSatuanJual->nama)->first();
            $newBarang->satuan = $satuanJualBarang->nama;
        } else {
            $satuanJualBarang = SatuanJual::where('nama', $request->satuanjual)->first();
            $newBarang->satuan = $satuanJualBarang->nama;
        }


        if($request->ada_expired_date === "True") {
            $newBarang->ada_expired_date = "True";
            $newBarang->expired = $request->expired;
        } else {
            $newBarang->ada_expired_date = "False";
            $newBarang->expired = NULL;
        }
        $newBarang->isi = $request->isi;
        $newBarang->toko = $request->stok;
        $newBarang->hpp = $request->hargabeli;
        $newBarang->harga_toko = $request->hargajual;
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
        $newBarang->tgl_terakhir = $request->tglbeli ? Carbon::createFromFormat('Y-m-d', $request->tglbeli)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $newBarang->ket = $request->keterangan ? ucfirst(htmlspecialchars($request->keterangan)) : NULL;

        $newBarang->save();

        $userOnNotif = Auth::user();

        if ($newBarang) {
            $supplierBarang = Supplier::where('nama', $request->supplier)->first();
            $kategoriBarang = Kategori::where('kode', $request->kategori)->first();
            $supplierBarangIds = [$supplierBarang->id];
            $kategoriBarangIds = [$kategoriBarang->id];
            $newBarang->suppliers()->sync($supplierBarangIds, false);
            $newBarang->kategoris()->sync($kategoriBarangIds, false);


            $newBarangSaved = Barang::where('id', $newBarang->id)
            ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
            ->with('suppliers')
            ->get();


            $data_event = [
                'routes' => 'data-barang',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "{$newBarang->nama}, baru saja ditambahkan 🤙!",
                'data' => $newBarang->nama,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return new RequestDataCollect($newBarangSaved);
        } else {
            return response()->json(['message' => 'Gagal menyimpan data barang.'], 500);
        }

    } catch (\Throwable $th) {
        \Log::error($th);
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
            $dataBarang = Barang::where('id', $id)
            ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'harga_partai', 'harga_cabang', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
            ->with(['suppliers' => function($query) {
                $query->select('kode', 'nama');
            }])
            ->with('kategoris')
            ->firstOrFail(); 

            return response()->json([
                'success' => true,
                'message' => "Detail data barang {$dataBarang->nama}",
                'data' => $dataBarang
            ]);
        } catch (\Throwable $th) {
            \Log::error($th);
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
    // public function update_photo_barang(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'photo' => 'image|mimes:jpg,png,jpeg|max:2048'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 400);
    //     }

    //     $barang_data = Barang::with('kategoris')
    //     ->whereKodeBarcode($kode)
    //     ->firstOrFail();

    //     if(count($barang_data->kategoris) > 0) {
    //         $kategori = Kategori::findOrFail($barang_data->kategoris[0]->id);
    //     }

    //     $kategori = Kategori::whereKode($barang_data->kategori)->firstOrFail();


    //     $update_barang = Barang::with('kategoris')
    //     ->findOrFail($barang_data->id);

    //     if ($request->file('photo')) {
    //         $photo = $request->file('photo');
    //         $file = $photo->store(trim(preg_replace('/\s+/', '', '/products')), 'public');
    //         $update_barang->photo = $file;
    //     } else {
    //         $update_barang->photo = $update_barang->photo;
    //     }

    //     $update_barang->save();
    //     $data_event = [
    //         'type' => 'updated',
    //         'notif' => "{$update_barang->nama}, successfully update photo barang!"
    //     ];

    //     event(new EventNotification($data_event));

    //     $saving_barang = Barang::with('kategoris')
    //     ->with('suppliers')
    //     ->whereId($update_barang->id)
    //     ->get();

    //         // return new RequestDataCollect($saving_barang);

    //     return response()->json([
    //         'success' => true,
    //         'message' => "{$update_barang->nama}, successfully update!",
    //         'data' => $saving_barang
    //     ]);
    // }
    public function update_photo_barang(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'image|mimes:jpg,png,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $update_barang = Barang::with('kategoris')
        ->findOrFail($id);

        $previousPhotoPath = $update_barang->photo;

        if ($request->file('photo')) {
            $photo = $request->file('photo');
            $file = $photo->store(trim(preg_replace('/\s+/', '', '/products')), 'public');

            if ($previousPhotoPath) {
                Storage::disk('public')->delete($previousPhotoPath);
            }

            $update_barang->photo = $file;
        } else {
            $update_barang->photo = $previousPhotoPath;
        }

        $update_barang->save();

        $data_event = [
            'type' => 'updated',
            'routes' => 'data-barang',
            'notif' => "{$update_barang->nama}, successfully update photo barang!"
        ];

        event(new EventNotification($data_event));

        $saving_barang = Barang::with('kategoris')
        ->with('suppliers')
        ->whereId($update_barang->id)
        ->get();

        return response()->json([
            'success' => true,
            'message' => "{$update_barang->nama}, successfully update!",
            'data' => $saving_barang
        ]);
    }

    public function update(Request $request, $id)
    {
        try {

            // $barang_data = Barang::with('kategoris')
            // ->whereKodeBarcode($kode)
            // ->firstOrFail();
            $barang_data = Barang::whereId($id)
            ->with('kategoris')
            ->with('suppliers')
            ->first();

            // var_dump($barang_data->suppliers); die;

            if(count($barang_data->kategoris) > 0) {
                $kategori = Kategori::findOrFail($barang_data->kategoris[0]->id);
            }

            $kategori = Kategori::whereKode($barang_data->kategori)->firstOrFail();


            if(count($barang_data->suppliers) > 0) {
                $supplier = Supplier::whereKode($barang_data->supplier)->firstOrFail();
            } else {
                $supplier = Supplier::whereKode($request->supplier)->firstOrFail();
            }


            $update_barang = Barang::with('kategoris')
            ->findOrFail($barang_data->id);

            $update_barang->nama = $request->nama ? $request->nama : $update_barang->nama;


            $update_barang->kategori = $request->kategori ? $request->kategori : $update_barang->kategori;
            $update_barang->satuanbeli = $request->satuanbeli ? $request->satuanbeli : $update_barang->satuanbeli;
            $update_barang->isi = $request->isi ? $request->isi : $update_barang->isi;
            $update_barang->toko = $request->stok ? $request->stok : $update_barang->toko;
            $update_barang->hpp = $request->hargabeli ? $request->hargabeli : $update_barang->hpp;
            $update_barang->harga_toko = $request->hargajual ? $request->hargajual : $update_barang->harga_toko;
            $update_barang->diskon = $request->diskon ? $request->diskon : $update_barang->diskon;
            $update_barang->supplier = $request->supplier ? $request->supplier : $update_barang->supplier;
            $update_barang->tgl_terakhir = $request->tglbeli ? Carbon::parse($request->tglbeli)->format('Y-m-d') : $update_barang->tgl_terakhir;
            $update_barang->ada_expired_date = $request->ada_expired_date ? $request->ada_expired_date : $update_barang->ada_expired_date;
            $update_barang->expired = $request->expired ? Carbon::parse($request->expired)->format('Y-m-d') : $update_barang->expired;
            $update_barang->ket = $request->keterangan ? $request->keterangan : $update_barang->ket;

            $update_barang->save();

            $update_barang->kategoris()->sync($kategori->id);
            $update_barang->suppliers()->sync($supplier->id);


            $data_event = [
                'type' => 'updated',
                'routes' => 'data-barang',
                'notif' => "{$update_barang->nama}, successfully update!"
            ];

            event(new EventNotification($data_event));

            $saving_barang = Barang::with('kategoris')
            ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
            ->with('suppliers')
            ->whereId($update_barang->id)
            ->get();

            // return new RequestDataCollect($saving_barang);

            return response()->json([
                'success' => true,
                'message' => "{$update_barang->nama}, successfully update!",
                'data' => $saving_barang
            ]);

        } catch(\Throwable $th) {
            // return response()->json([
            //     'error' => true,
            //     'message' => "Error {$th->getMessage()}"
            // ]);
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
        $delete_barang = Barang::whereNull('deleted_at')
        ->findOrFail($id);

        $delete_barang->delete();

        $data_event = [
            'alert' => 'error',
            'routes' => 'data-barang',
            'type' => 'removed',
            'notif' => "{$delete_barang->nama}, has move to trash, please check trash!",
            'user' => Auth::user()
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
