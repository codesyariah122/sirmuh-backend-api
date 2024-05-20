<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{Barang, Kategori, KategoriBarang, SatuanBeli, SatuanJual, Supplier, ItemPembelian, Roles};
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
    // public function index(Request $request)
    // {
    //     try {
    //         $keywords = $request->query('keywords');
    //         $kategori = $request->query('kategori');
    //         $endDate = $request->query('tgl_terakhir');
    //         $barcode = $request->query('barcode');

    //         if($keywords) {
    //             $barangs = Barang::whereNull('deleted_at')
    //             ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
    //             ->where('nama', 'like', '%'.$keywords.'%')
    //             // ->orderByDesc('harga_toko')
    //             ->orderByDesc('id')
    //             ->with('suppliers')
    //             ->paginate(10);
    //         } else if($kategori){
    //             $barangs = Barang::whereNull('deleted_at')
    //             ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
    //             ->where('kategori', $kategori)
    //             // ->orderByDesc('harga_toko')
    //             ->orderByDesc('id')
    //             ->with('suppliers')
    //             ->paginate(10);
    //         } else if($endDate) {
    //             $barangs = Barang::whereNull('deleted_at')
    //             ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
    //             ->where('tgl_terakhir', $endDate)
    //             // ->orderByDesc('harga_toko')
    //             ->orderByDesc('id')
    //             ->with('suppliers')
    //             ->paginate(10);
    //         } else if($barcode) {
    //             $barangs = Barang::whereKodeBarcode($barcode)->get();
    //         }else {
    //             $barangs = Barang::whereNull('deleted_at')
    //             ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
    //             ->with("kategoris")
    //             // ->orderByDesc('harga_toko')
    //             ->with('suppliers')
    //             ->orderByDesc('id')
    //             ->paginate(10);
    //         }

    //         foreach ($barangs as $item) {
    //             $kodeBarcode = $item->kode_barcode;
    //             $this->feature_helpers->generateQrCode($kodeBarcode);
    //             // $this->feature_helpers->generateBarcode($kodeBarcode);
    //         }

    //         return new ResponseDataCollect($barangs);

    //     } catch (\Throwable $th) {
    //         throw $th;
    //     }
    // }


    public function list_barangs(Request $request)
    {
        try {
            $cacheKey = 'barangs_' . md5(serialize($request->all()));

            if (Cache::has($cacheKey)) {
                $barangs = Cache::get($cacheKey);
            } else {
                $keywords = $request->query('keywords');
                $kode = $request->query('kode');
                $sortName = $request->query('sort_name');
                $sortType = $request->query('sort_type');

                if ($keywords) {
                    $barangs = Barang::whereNull('deleted_at')
                    ->select('id', 'nama', 'kode')
                    ->where(function ($query) use ($keywords) {
                        $query->where('nama', 'like', '%' . $keywords . '%')
                        ->orWhere('kode', 'like', '%' . $keywords . '%');
                    })
                    ->orderBy('id', 'ASC')
                    ->limit(10)
                    ->paginate(10);
                } elseif ($kode) {
                    $barangs = Barang::whereNull('deleted_at')
                    ->select('id', 'nama', 'kode')
                    ->where('kode', 'like', '%' . $kode . '%')
                    ->orderBy('id', 'ASC')
                    ->limit(10)
                    ->paginate(10);
                } else {
                    if ($sortName && $sortType) {
                        $barangs = Barang::whereNull('deleted_at')
                        ->select('id', 'nama', 'kode')
                        ->orderBy($sortName, $sortType)
                        ->limit(10)
                        ->paginate(10);
                    } else {
                        $barangs = Barang::whereNull('deleted_at')
                        ->select('id', 'nama', 'kode')
                        ->orderBy('id', 'ASC')
                        ->limit(10)
                        ->paginate(10);
                    }
                }

                Cache::put($cacheKey, $barangs, now()->addMinutes(60)); // Cache for 60 minutes
            }

            return new ResponseDataCollect($barangs);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $kode = $request->query('kode');
            $supplier = $request->query('supplier');
            $endDate = $request->query('tgl_terakhir');
            $startDate = $request->query('start_date');
            $barcode = $request->query('barcode');
            $sortName = $request->query('sort_name');
            $sortType = $request->query('sort_type');
            
            // $query = Barang::whereNull('barang.deleted_at')
            // // ->select('barang.id', 'barang.kode', 'barang.nama', 'barang.photo', 'barang.kategori', 'barang.satuan', 'barang.toko', 'barang.gudang', 'barang.hpp', 'barang.harga_toko', 'barang.diskon', 'barang.supplier', 'supplier.nama as supplier_nama', 'barang.kode_barcode', 'barang.tgl_terakhir', 'barang.ada_expired_date', 'barang.expired','itempembelian.qty', 'pembelian.hutang')
            // ->select('barang.id', 'barang.kode', 'barang.nama', 'barang.photo', 'barang.kategori', 'barang.satuan', 'barang.toko', 'barang.gudang', 'barang.hpp', 'barang.harga_toko', 'barang.diskon', 'barang.supplier', 'supplier.nama as supplier_nama', 'barang.kode_barcode')
            // ->with("kategoris")
            // ->with('suppliers')
            // ->leftJoin('supplier', 'barang.kategori', '=', 'supplier.nama');
            // ->leftJoin('itempembelian', 'barang.kode', '=', 'itempembelian.kode_barang')
            // ->leftJoin('pembelian', 'itempembelian.kode', '=', 'pembelian.kode');
            // ->orderBy('barang.nama', 'ASC');

            $query = Barang::join('supplier', 'barang.supplier', '=', 'supplier.kode')
            ->select('barang.id', 'barang.kode', 'barang.nama', 'barang.toko', 'barang.last_qty', 'barang.hpp','barang.harga_toko','barang.toko', 'barang.satuan', 'barang.kategori', 'barang.supplier', 'barang.kode_barcode', 'barang.ada_expired_date', 'barang.expired','barang.tgl_terakhir','supplier.kode as kode_supplier','supplier.nama as supplier_nama', 'supplier.alamat as supplier_alamat')
            ->when($keywords, function ($query) use ($keywords) {
                return $query->where(function ($query) use ($keywords) {
                    $query->where('barang.nama', 'like', '%' . $keywords . '%')
                    ->orWhere('barang.kode', 'like', '%' . $keywords . '%');
                });
            })
            ->when($supplier, function ($query) use ($supplier) {
                return $query->where('barang.supplier', $supplier );
            })
            ->when($startDate, function ($query) use ($startDate) {
                return $query->where('barang.tgl_terakhir', $startDate );
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->where('barang.tgl_terakhir', $endDate );
            })
            ->when($barcode, function ($query) use ($barcode) {
                return $query->where('barang.kode_barcode', $barcode );
            });


            // if($supplier) {
            //     $query->where('barang.supplier', $supplier );
            // }

            if($sortName && $sortType) {
                $barangs = $query
                ->orderBy($sortName, $sortType)
                ->paginate(10);
            } else {                
                $barangs = $query
                ->orderByDesc('barang.id')
                ->paginate(10);
            }

            foreach ($barangs as $item) {
                $kodeBarcode = $item->kode_barcode;
                $this->feature_helpers->generateBarcode($kodeBarcode);
            }

            // var_dump($barangs); die;

            return new ResponseDataCollect($barangs);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function barang_by_warehouse(Request $request)
    {
        try {
           $keywords = $request->query('keywords');
           $kategori = $request->query('kategori');
           $endDate = $request->query('tgl_terakhir');
           $barcode = $request->query('barcode');
           $startDate = $request->query('start_date');
           $sortName = $request->query('sort_name');
           $sortType = $request->query('sort_type');

           $query = Barang::select('kategori_barang as nama', 'satuan', DB::raw('SUM(toko) as total_stok'))
           ->whereNull('deleted_at')
           ->groupBy('kategori_barang','satuan')
           ->when($keywords, function ($query) use ($keywords) {
            return $query->where(function ($query) use ($keywords) {
                $query->where('nama', 'like', '%' . $keywords . '%')
                ->orWhere('kode', 'like', '%' . $keywords . '%');
            });
        })
           ->when($kategori, function ($query) use ($kategori) {
            return $query->where('kategori', $kategori );
        });


           if($sortName && $sortType) {
               $barangs = $query
               ->orderBy($sortName, $sortType)
               ->paginate(10);
           } else {
            $barangs =$query
            ->orderBy('nama', 'ASC')
            ->paginate(10);
        }
        return new ResponseDataCollect($barangs);
    } catch (\Throwable $th) {
        throw $th;
    }
}

public function barang_all(Request $request)
{
    try {
       $keywords = $request->query('keywords');
       $kategori = $request->query('kategori');
       $endDate = $request->query('tgl_terakhir');
       $barcode = $request->query('barcode');
       $startDate = $request->query('start_date');
       $sortName = $request->query('sort_name');
       $sortType = $request->query('sort_type');

       $query = Barang::select('id', 'kode', 'nama','kategori_barang', 'satuan', 'toko as total_stok')
       ->whereNull('deleted_at')
       ->when($keywords, function ($query) use ($keywords) {
        return $query->where(function ($query) use ($keywords) {
            $query->where('nama', 'like', '%' . $keywords . '%')
            ->orWhere('kode', 'like', '%' . $keywords . '%');
        });
    })
       ->when($kategori, function ($query) use ($kategori) {
        return $query->where('kategori', $kategori );
    });


       if($sortName && $sortType) {
           $barangs = $query
           ->orderBy($sortName, $sortType)
           ->paginate(10);
       } else {
        $barangs =$query
        ->orderBy('nama', 'ASC')
        ->paginate(10);
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
                'stok' => 'required',
                'photo' => 'image|mimes:jpg,png,jpeg|max:2048',
            ]);


            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_barang = Barang::whereNama($request->nama)->get();

            $newBarang = new Barang;
            $newBarang->nama = strtoupper($request->nama);
            $newBarang->kode = $request->kode;
            $newBarang->kategori_barang = $request->kategori_barang;
            $newBarang->kategori = $request->supplier;

            if ($request->file('photo')) {
                $photo = $request->file('photo');
                $file = $photo->store(trim(preg_replace('/\s+/', '', '/products')), 'public');
                $newBarang->photo = $file;
            }


            $supplierData = Supplier::whereKode($request->kategori)->first();
            $checkKategoriSupplier = Kategori::where('kode', $supplierData->nama)->count();

            if($checkKategoriSupplier === 0) {
                $newKategoriSupplier = new Kategori;
                $newKategoriSupplier->kode = $supplierData->nama;
                $newKategoriSupplier->save();
                $kategoriSupplier = Kategori::findOrFail($newKategoriSupplier->id);
                $newBarang->kategori = $kategoriSupplier->kode;
            } else {
                $kategoriSupplier = Kategori::where('kode', $supplierData->nama)->first();
                $newBarang->kategori = $kategoriSupplier->kode;
            }

            $checkKategoriBarang = KategoriBarang::where('nama', $request->kategori_barang)->count();

            if($checkKategoriBarang === 0) {
                $newKategoriBarang = new KategoriBarang;
                $newKategoriBarang->nama = $request->kategori_barang;
                $newKategoriBarang->save();
                $kategoriBarang = KategoriBarang::findOrFail($newKategoriBarang->id);
                $newBarang->kategori = $kategoriBarang->nama;
            } else {
                $kategoriBarang = KategoriBarang::where('nama', $request->kategori_barang)->first();
                $newBarang->kategori_barang = $kategoriBarang->nama;
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
            
            $newBarang->isi = $request->isi !== null ? $request->isi : null;
            $newBarang->toko = $request->stok !== null ? $request->stok : null;
            $newBarang->hpp = $request->hargabeli !== null ? $request->hargabeli : null;
            $newBarang->harga_toko = $request->hargajual !== 'null' ? $request->hargajual : null;
            $newBarang->diskon = $request->diskon !== 'null' ? $request->diskon : null;

            $checkSupplier = Supplier::where('kode', $request->kategori)->count();
            if($checkSupplier > 0) {
                $supplierBarang = Supplier::where('kode', $request->kategori)->first();
                $newBarang->supplier = $supplierBarang->kode;
                $newBarang->kategori = $supplierBarang->nama;
            } else {
                $newSupplier = new Supplier;
                $kode = $this->generateAcronym($request->kategori);
                $newSupplier->kode = $kode;
                $newSupplier->nama = $request->kategori;
                $newSupplier->save();
                $newSupplierBarang = Supplier::findOrFail($newSupplier->id);
                $newBarang->kategori = $newSupplierBarang->kode;
                $newBarang->supplier = $newSupplierBarang->nama;
            }

            $newBarang->kode_barcode = $request->barcode;

            if ($request->tglbeli !== "null") {
                $tgl_terakhir = Carbon::createFromFormat('Y-m-d', $request->tglbeli)->format('Y-m-d');
            } else {
                $tgl_terakhir = NULL;
            }

            $newBarang->tgl_terakhir = $tgl_terakhir;
            $newBarang->ket = $request->keterangan ? ucfirst(htmlspecialchars($request->keterangan)) : NULL;

            $newBarang->save();

            $userOnNotif = Auth::user();

            if ($newBarang) {
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
            throw $th;
        }
    }

        /**
         * Display the specified resource.
         *
         * @param  int  $id
         * @return \Illuminate\Http\Response
         */

        public function detail_barang_by_kode(Request $request)
        {
            try {
                $kode = $request->query("kode");
                $dataGet = Barang::whereKode($kode)->first();
                $dataBarang = Barang::findOrFail($dataGet->id);
                
                return response()->json([
                    'success' => true,
                    'message' => "Detail data barang {$dataBarang->nama}",
                    'data' => $dataBarang
                ]);
            } catch (\Throwable $th) {
                throw $th;
            }
        }

        public function show($id)
        {
            try {
                $namaBarang = null;
                // $dataBarang = Barang::where('id', $id)
                // ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'harga_partai', 'harga_cabang', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
                // ->with(['suppliers' => function($query) {
                //     $query->select('kode', 'nama');
                // }])
                // ->with('kategoris')
                // ->firstOrFail();
                $dataBarang = Barang::select('barang.id', 'barang.kode', 'barang.nama', 'barang.photo', 'barang.kategori', 'barang.kategori_barang', 'barang.satuanbeli', 'barang.satuan', 'barang.isi', 'barang.toko', 'barang.gudang', 'barang.hpp', 'barang.harga_toko', 'barang.last_qty', 'barang.harga_partai', 'barang.harga_cabang', 'barang.diskon', 'barang.supplier', 'barang.kode_barcode', 'barang.tgl_terakhir', 'barang.ada_expired_date', 'barang.expired', 'itempembelian.id as id_itempembelian', 'itempembelian.diskon as diskon_itempembelian','supplier.id as id_supplier','supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
                ->leftJoin('itempembelian', 'barang.kode', '=', 'itempembelian.kode_barang')
                ->leftJoin('supplier', 'barang.kategori', '=', 'supplier.nama')
                ->where('itempembelian.draft','=', 1)
                ->where('barang.id', $id)
                ->first();

                if($dataBarang === NULL) {
                    $dataBarang = Barang::select('barang.id', 'barang.kode', 'barang.nama', 'barang.photo', 'barang.kategori', 'barang.kategori_barang', 'barang.satuanbeli', 'barang.satuan', 'barang.isi', 'barang.toko', 'barang.gudang', 'barang.hpp', 'barang.harga_toko', 'barang.last_qty',  'barang.harga_partai', 'barang.harga_cabang', 'barang.diskon', 'barang.supplier', 'barang.kode_barcode', 'barang.tgl_terakhir', 'barang.ada_expired_date', 'barang.expired', 'supplier.id as id_supplier','supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
                    ->leftJoin('supplier', 'barang.supplier', '=', 'supplier.kode')
                    ->where('barang.id', $id)
                    ->first();
                    $namaBarang = $dataBarang->nama;
                }

                return response()->json([
                    'success' => true,
                    'message' => "Detail data barang {$dataBarang->nama}",
                    'data' => $dataBarang
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
            $barang_data = Barang::with('suppliers')
            ->findOrFail($id);
            $supplierId = NULL;

            try {
                $user = Auth::user();

                $userRole = Roles::findOrFail($user->role);
                
                if($userRole->name === "MASTER" || $userRole->name === "ADMIN" || $userRole->name === "GUDANG") {
                    if(count($barang_data->suppliers) > 0) {
                        $supplier = Supplier::findOrFail($barang_data->suppliers[0]->id);
                        $supplierId = $supplier->id;
                    } else {
                        $supplier = Supplier::whereNama($request->supplier)->first();
                        if($supplier !== NULL) {
                            $supplierId = $supplier->id;
                        }
                    }

                    if(count($barang_data->kategoris) > 0) {
                        $kategori = Kategori::findOrFail($barang_data->kategoris[0]->id);
                    }

                    $kategori = Kategori::whereKode($barang_data->kategori)->firstOrFail();

                    $update_barang = Barang::findOrFail($barang_data->id);

                    $update_barang->nama = $request->nama ? $request->nama : $update_barang->nama;
                    $update_barang->kategori_barang = $request->kategori_barang ? $request->kategori_barang : $update_barang->kategori_barang;
                    $update_barang->kategori = $request->kategori ? $request->kategori : $update_barang->kategori;
                    $update_barang->satuanbeli = $request->satuanbeli ? $request->satuanbeli : $update_barang->satuanbeli;
                    $update_barang->isi = $request->isi ? $request->isi : $update_barang->isi;
                    $update_barang->toko = $request->stok ? $request->stok : $update_barang->toko;
                    $update_barang->last_qty = $request->last_qty ? $request->last_qty : $update_barang->toko;
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
                    $update_barang->suppliers()->sync($supplierId);

                    $data_event = [
                        'type' => 'updated',
                        'routes' => 'data-barang',
                        'notif' => "{$update_barang->nama}, successfully update!"
                    ];

                    event(new EventNotification($data_event));

                    $saving_barang = Barang::with('kategoris')
                    ->select('id', 'kode', 'nama', 'photo', 'kategori', 'kategori_barang','satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
                    ->with('suppliers')
                    ->whereId($update_barang->id)
                    ->get();

                    return response()->json([
                        'success' => true,
                        'message' => "{$update_barang->nama}, successfully update!",
                        'data' => $saving_barang
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

        public function destroy($id)
        {
         try {
            $user = Auth::user();

            $userRole = Roles::findOrFail($user->role);
            
            if($userRole->name === "MASTER" || $userRole->name === "ADMIN" || $userRole->name === "GUDANG") {                
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

    public function data_barang_with_item_pembelian($id)
    {
        try {
            $dataBarang = Barang::where('id', $id)
            ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'harga_partai', 'harga_cabang', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
            ->with(['suppliers' => function($query) {
                $query->select('kode', 'nama');
            }])
            ->with('kategoris')
            ->firstOrFail(); 

            $kodeBarang = $dataBarang->kode;

            $dataItemPembelian = ItemPembelian::join('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->where('barang.kode', $kodeBarang)
            ->select('itempembelian.*')
            ->get();

            return response()->json([
                'success' => true,
                'message' => "Detail data barang {$dataBarang->nama}",
                'data_barang' => $dataBarang,
                'data_item_pembelian' => $dataItemPembelian,
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function barang_by_suppliers(Request $request, $id) 
    {   
        try {
           $query = DB::table('barang')
           ->whereNull('barang.deleted_at')
           ->join('supplier', 'barang.supplier', '=', 'supplier.kode')
           ->where('supplier.id', $id)
           ->select('barang.id', 'barang.kode', 'barang.nama', 'barang.toko','barang.hpp', 'barang.toko', 'barang.satuan', 'barang.kategori', 'barang.supplier', 'supplier.nama as supplier_nama', 'supplier.alamat as supplier_alamat');

           $barangs = $query
           ->orderByDesc('barang.id')
           ->get();

           return new ResponseDataCollect($barangs);
       }catch (\Throwable $th) {
        throw $th;
    }
}

public function barang_pemakaian_list()
{
    try {
        $query = DB::table('barang')
        ->whereNull('barang.deleted_at')
        ->join('supplier', 'barang.supplier', '=', 'supplier.kode')
            ->where('barang.nama', 'like', 'gula%') // Mengambil hanya data barang yang nama awalnya mengandung kata "gula"
            ->select('barang.id', 'barang.kode', 'barang.nama', 'barang.toko','barang.hpp', 'barang.toko', 'barang.satuan', 'barang.kategori', 'barang.supplier', 'supplier.nama as supplier_nama', 'supplier.alamat as supplier_alamat');

            $barangs = $query
            ->orderByDesc('barang.id')
            ->get();

            return new ResponseDataCollect($barangs);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function barang_cetak_pemakaian()
    {
        try {
            $query = DB::table('barang')
            ->whereNull('barang.deleted_at')
            ->join('supplier', 'barang.supplier', '=', 'supplier.kode')
            ->where('barang.nama', 'like', 'gula cetak%') // Mengambil hanya data barang yang nama awalnya mengandung kata "gula"
            ->select('barang.id', 'barang.kode', 'barang.nama', 'barang.toko','barang.hpp', 'barang.toko', 'barang.satuan', 'barang.kategori', 'barang.supplier', 'supplier.nama as supplier_nama', 'supplier.alamat as supplier_alamat');

            $barangs = $query
            ->orderByDesc('barang.id')
            ->get();

            return new ResponseDataCollect($barangs);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
