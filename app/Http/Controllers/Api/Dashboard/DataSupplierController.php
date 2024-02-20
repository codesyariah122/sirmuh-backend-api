<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers, UserHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{User, Roles, Barang, Kategori, SatuanBeli, SatuanJual, Supplier};
use Auth;

class DataSupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $user_helpers;

    public function __construct()
    {
        $this->user_helpers = new UserHelpers;
    }

    public function supplier_for_lists(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $kode = $request->query('kode');
            $sortName = $request->query('sort_name');
            $sortType = $request->query('sort_type');

            if($keywords) {
                $suppliers = Supplier::whereNull('deleted_at')
                ->select('id', 'nama', 'kode', 'alamat', 'kota', 'telp', 'fax', 'email', 'saldo_piutang')
                ->where(function($query) use ($keywords) {
                    $query->where('nama', 'like', '%' . $keywords . '%')
                    ->orWhere('kode', 'like', '%' . $keywords . '%');
                })
                ->orderBy('id', 'ASC')
                ->paginate(10);
            } else if($kode) {
                $suppliers = Supplier::whereNull('deleted_at')
                ->select('id', 'nama', 'kode', 'alamat', 'kota', 'telp', 'fax', 'email', 'saldo_piutang')
                ->where('kode', 'like', '%' . $kode . '%')
                ->orderBy('id', 'ASC')
                ->paginate(10);
            } else {
                if($sortName && $sortType) {
                    $suppliers =  Supplier::whereNull('deleted_at')
                    ->select('id', 'nama', 'kode', 'alamat', 'kota', 'telp', 'fax', 'email', 'saldo_piutang')
                    ->orderBy($sortName, $sortType)
                    ->paginate(10);
                } else {
                    $suppliers =  Supplier::whereNull('deleted_at')
                    ->select('id', 'nama', 'kode', 'alamat', 'kota', 'telp', 'fax', 'email', 'saldo_piutang')
                    ->orderBy('id', 'ASC')
                    ->paginate(10);
                }
            }

            return new ResponseDataCollect($suppliers);
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function list_suppliers(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $kode = $request->query('kode');
            $sortName = $request->query('sort_name');
            $sortType = $request->query('sort_type');

            if($keywords) {
                $suppliers = Supplier::whereNull('deleted_at')
                ->select('id', 'nama', 'kode')
                ->where(function($query) use ($keywords) {
                    $query->where('nama', 'like', '%' . $keywords . '%')
                    ->orWhere('kode', 'like', '%' . $keywords . '%');
                })
                ->orderBy('id', 'ASC')
                ->paginate(10);
            } else if($kode) {
                $suppliers = Supplier::whereNull('deleted_at')
                ->select('id', 'nama', 'kode')
                ->where('kode', 'like', '%' . $kode . '%')
                ->orderBy('id', 'ASC')
                ->paginate(10);
            } else {
                if($sortName && $sortType) {
                    $suppliers =  Supplier::whereNull('deleted_at')
                    ->select('id', 'nama', 'kode')
                    ->orderBy($sortName, $sortType)
                    ->paginate(10);
                } else {
                    $suppliers =  Supplier::whereNull('deleted_at')
                    ->select('id', 'nama', 'kode')
                    ->orderBy('id', 'ASC')
                    ->paginate(10);
                }
            }

            return new ResponseDataCollect($suppliers);
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $kode = $request->query('kode');
            $sortName = $request->query('sort_name');
            $sortType = $request->query('sort_type');

            if($keywords) {
                $suppliers = Supplier::whereNull('supplier.deleted_at')
                ->leftJoin('hutang', 'supplier.kode', '=', 'hutang.supplier')
                ->select('supplier.kode as kode_supplier', 'supplier.nama', 'supplier.alamat', 'hutang.kode as kode_hutang', 'hutang.jumlah', 'hutang.tanggal')
                ->where('supplier.nama', 'like', '%' . $keywords . '%')
                ->orderBy('supplier.id', 'ASC')
                ->paginate(10);
            } else if($kode) {
                $suppliers = Supplier::whereNull('supplier.deleted_at')
                ->leftJoin('hutang', 'supplier.kode', '=', 'hutang.supplier')
                ->select('supplier.kode as kode_supplier', 'supplier.nama', 'supplier.alamat', 'hutang.kode as kode_hutang', 'hutang.jumlah', 'hutang.tanggal')
                ->where('supplier.kode', 'like', '%' . $kode . '%')
                ->orderBy('supplier.id', 'ASC')
                ->paginate(10);
            } else {
                if($sortName && $sortType) {
                    $suppliers =  Supplier::whereNull('supplier.deleted_at')
                    ->leftJoin('hutang', 'supplier.kode', '=', 'hutang.supplier')
                    ->select('supplier.kode as kode_supplier', 'supplier.nama', 'supplier.alamat', 'hutang.kode as kode_hutang', 'hutang.jumlah', 'hutang.tanggal')
                    ->orderBy($sortName, $sortType)
                    ->paginate(10);
                } else {
                    $suppliers =  Supplier::whereNull('supplier.deleted_at')
                    ->leftJoin('hutang', 'supplier.kode', '=', 'hutang.supplier')
                    ->select('supplier.kode as kode_supplier', 'supplier.nama', 'supplier.alamat', 'hutang.kode as kode_hutang', 'hutang.jumlah', 'hutang.tanggal')
                    ->orderBy('id', 'ASC')
                    ->paginate(10);
                }
            }

            return new ResponseDataCollect($suppliers);
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
                'nama' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $kode = explode(' ', $request->nama);
            $substringArray = [];

            foreach ($kode as $i) {
                $substringArray[] = substr($i, 0, 1);
            }

            $existing_supplier = Supplier::whereNama($request->nama)->first();

            if($existing_supplier) {
                return response()->json([
                    'error' => true,
                    'message' => "Pelanggan dengan nama {$existing_supplier->nama}, has already been taken✨!"
                ]);
            }

            $new_supplier = new Supplier;
            $new_supplier->kode = strtoupper(implode('', $substringArray));
            $new_supplier->nama = $request->nama;
            $new_supplier->email = $request->email;
            $new_supplier->telp = $this->user_helpers->formatPhoneNumber($request->telp);
            $new_supplier->alamat = htmlspecialchars(nl2br($request->alamat));
            $new_supplier->no_npwp = $request->no_npwp;
            $new_supplier->save();

            if($new_supplier) {
                $userOnNotif = Auth::user();
                $data_event = [
                    'routes' => 'supplier',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "{$new_supplier->nama}, baru saja ditambahkan 🤙!",
                    'data' => $new_supplier->nama,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                $newDataSupplier = Supplier::findOrFail($new_supplier->id);
                return response()->json([
                    'success' => true,
                    'message' => "Supplier dengan nama {$newDataSupplier->nama}, successfully added✨!",
                    'data' => $newDataSupplier
                ]);
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
            $supplier = Supplier::whereNull('deleted_at')
            ->select("id", "nama", "telp", "email", "alamat", "no_npwp")
            ->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => "Detail data supplier {$supplier->nama}✨!",
                'data' => $supplier
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
            $validator = Validator::make($request->all(), [
                'nama' => 'required',
                'alamat' => 'required',
                'telp' => 'required',
                'email' => 'required|email|unique:users'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $kode = explode(' ', $request->nama);
            $substringArray = [];

            foreach ($kode as $i) {
                $substringArray[] = substr($i, 0, 1);
            }

        
            $update_supplier = Supplier::whereNull('deleted_at')
            ->findOrFail($id);
            $update_supplier->kode = $request->kode ? strtoupper(implode('', $substringArray)) : $update_supplier->kode;
            $update_supplier->nama = $request->nama ? $request->nama : $update_supplier->nama;
            $update_supplier->email = $request->email ? $request->email : $update_supplier->email;
            $update_supplier->telp = $request->telp ? $this->user_helpers->formatPhoneNumber($request->telp) : $update_supplier->telp;
            $update_supplier->alamat = $request->alamat ? htmlspecialchars(nl2br($request->alamat)) : $update_supplier->alamat;
            $update_supplier->no_npwp = $request->no_npwp ? $request->no_npwp : $update_supplier->no_npwp;
            $update_supplier->save();

            if($update_supplier) {
                $userOnNotif = Auth::user();
                $data_event = [
                    'routes' => 'supplier',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "{$update_supplier->nama}, baru saja ditambahkan 🤙!",
                    'data' => $update_supplier->nama,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                $newUpdateSupplier = Supplier::findOrFail($update_supplier->id);
                return response()->json([
                    'success' => true,
                    'message' => "Supplier dengan nama {$newUpdateSupplier->nama}, successfully added✨!",
                    'data' => $newUpdateSupplier
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

            if($userRole->name === "MASTER" || $userRole->name === "ADMIN" || $userRole->name === "GUDANG") {
                $supplier = Supplier::whereNull('deleted_at')
                ->findOrFail($id);
                $supplier->delete();
                $data_event = [
                    'alert' => 'error',
                    'routes' => 'data-supplier',
                    'type' => 'removed',
                    'notif' => "{$supplier->nama}, has move to trash, please check trash!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data supplier {$supplier->nama} has move to trash, please check trash"
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
