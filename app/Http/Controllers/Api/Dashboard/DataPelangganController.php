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
use App\Models\{Pelanggan, Penjualan,Roles};
use Auth;


class DataPelangganController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $user_helpers, $feature_helpers;

    public function __construct()
    {
        $this->user_helpers = new UserHelpers;
        $this->feature_helpers = new WebFeatureHelpers;
    }

    public function list_normal(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $sales = $request->query('sales');
            $kode = $request->query('kode');
            $sortName = $request->query('sort_name');
            $sortType = $request->query('sort_type');
            

            if($keywords) {
                $pelanggans = Pelanggan::whereNull('deleted_at')
                ->whereNotIn('kode', ['YJG', 'UN', 'UJ', 'TL', 'PKS', 'IDF', 'IND'])
                ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
                ->where(function($query) use ($keywords) {
                    $query->where('nama', 'like', '%' . $keywords . '%')
                    ->orWhere('kode', 'like', '%' . $keywords . '%');
                })
                ->orderByDesc('id')
                ->paginate(10);
            } else if($sales){
             $pelanggans = Pelanggan::whereNull('deleted_at')
             ->whereNotIn('kode', ['YJG', 'UN', 'UJ', 'TL', 'PKS', 'IDF', 'IND'])
             ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
             ->where('sales', $sales)
             ->orderByDesc('id')
             ->paginate(10);
         } else if($kode) {
             $pelanggans = Pelanggan::whereNull('deleted_at')
             ->whereNotIn('kode', ['YJG', 'UN', 'UJ', 'TL', 'PKS', 'IDF', 'IND'])
             ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
             ->where('kode', $kode)
             ->orderByDesc('id')
             ->paginate(10);
         }else {
            if($sortName && $sortType) {
                $pelanggans =  Pelanggan::whereNull('deleted_at')
                ->whereNotIn('kode', ['YJG', 'UN', 'UJ', 'TL', 'PKS', 'IDF', 'IND'])
                ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
                ->orderBy($sortName, $sortType)
                ->paginate(10); 
            } else {                    
                $pelanggans =  Pelanggan::whereNull('deleted_at')
                ->whereNotIn('kode', ['YJG', 'UN', 'UJ', 'TL', 'PKS', 'IDF', 'IND'])
                ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
                ->orderBy('id', 'DESC')
                ->paginate(10);
            }
        }

        return new ResponseDataCollect($pelanggans);

    } catch (\Throwable $th) {
        throw $th;
    }
}

public function list_partai(Request $request)
{
    try {
        $keywords = $request->query('keywords');
        $sales = $request->query('sales');
        $kode = $request->query('kode');
        $sortName = $request->query('sort_name');
        $sortType = $request->query('sort_type');
        $allowedCodes = ['YJG', 'UN', 'UJ', 'TL', 'PKS', 'IDF', 'IND'];

        if($keywords) {
            $pelanggans = Pelanggan::whereNull('deleted_at')
            ->whereIn('kode', $allowedCodes)
            ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
            ->where(function($query) use ($keywords) {
                $query->where('nama', 'like', '%' . $keywords . '%')
                ->orWhere('kode', 'like', '%' . $keywords . '%');
            })
            ->orderByDesc('id')
            ->paginate(10);
        } else if($sales){
         $pelanggans = Pelanggan::whereNull('deleted_at')
         ->whereIn('kode', $allowedCodes)
         ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
         ->where('sales', $sales)
         ->orderByDesc('id')
         ->paginate(10);
     } else if($kode) {
         $pelanggans = Pelanggan::whereNull('deleted_at')
         ->whereIn('kode', $allowedCodes)
         ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
         ->where('kode', $kode)
         ->orderByDesc('id')
         ->paginate(10);
     }else {
        if($sortName && $sortType) {
            $pelanggans =  Pelanggan::whereNull('deleted_at')
            ->whereIn('kode', $allowedCodes)
            ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
            ->whereNotIn('kode', ['YJG', 'UN', 'UJ', 'TL', 'PKS', 'IDF', 'IND'])
            ->orderBy($sortName, $sortType)
            ->paginate(10); 
        } else {                    
            $pelanggans =  Pelanggan::whereNull('deleted_at')
            ->whereIn('kode', $allowedCodes)
            ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
            ->orderBy('id', 'DESC')
            ->paginate(10);
        }
    }

    return new ResponseDataCollect($pelanggans);

} catch (\Throwable $th) {
    throw $th;
}
}

public function index(Request $request)
{
    try {
        $keywords = $request->query('keywords');
        $sales = $request->query('sales');
        $kode = $request->query('kode');
        $sortName = $request->query('sort_name');
        $sortType = $request->query('sort_type');


        if($keywords) {
            $pelanggans = Pelanggan::whereNull('deleted_at')
            ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
            ->where(function($query) use ($keywords) {
                $query->where('nama', 'like', '%' . $keywords . '%')
                ->orWhere('kode', 'like', '%' . $keywords . '%');
            })
                // ->orderByDesc('harga_toko')
            ->orderByDesc('id')
            ->paginate(10);
        } else if($sales){
         $pelanggans = Pelanggan::whereNull('deleted_at')
         ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
         ->where('sales', $sales)
                // ->orderByDesc('harga_toko')
         ->orderByDesc('id')
         ->paginate(10);
     } else if($kode) {
         $pelanggans = Pelanggan::whereNull('deleted_at')
         ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
         ->where('kode', $kode)
                // ->orderByDesc('harga_toko')
         ->orderByDesc('id')
         ->paginate(10);
     }else {
        if($sortName && $sortType) {
            $pelanggans =  Pelanggan::whereNull('deleted_at')
            ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
                    // ->orderByDesc('harga_toko')
            ->orderBy($sortName, $sortType)
            ->paginate(10); 
        } else {                    
            $pelanggans =  Pelanggan::whereNull('deleted_at')
            ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
                    // ->orderByDesc('harga_toko')
            ->orderBy('id', 'DESC')
            ->paginate(10);
        }
    }

    return new ResponseDataCollect($pelanggans);

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

            $existing_pelanggan = Pelanggan::whereNama($request->nama)->first();

            if($existing_pelanggan) {
                return response()->json([
                    'error' => true,
                    'message' => "Pelanggan dengan nama {$existing_pelanggan->nama}, has already been taken✨!"
                ]);
            }

            $new_pelanggan = new Pelanggan;
            $new_pelanggan->kode = $request->kode ? $request->kode : strtoupper(implode('', $substringArray));
            $new_pelanggan->nama = strtoupper($request->nama);
            $new_pelanggan->email = $request->email;
            $new_pelanggan->telp = $this->user_helpers->formatPhoneNumber($request->telp);
            $new_pelanggan->alamat = strip_tags($request->alamat);
            $new_pelanggan->pekerjaan = $request->pekerjaan;
            $new_pelanggan->save();

            if($new_pelanggan) {
                $userOnNotif = Auth::user();
                $data_event = [
                    'routes' => 'data-pelanggan',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "{$new_pelanggan->nama}, baru saja ditambahkan 🤙!",
                    'data' => $new_pelanggan->nama,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                $historyKeterangan = "{$userOnNotif->name}, berhasil menambahkan pelanggan baru : [{$new_pelanggan->kode}], {$new_pelanggan->nama}";
                $dataHistory = [
                    'user' => $userOnNotif->name,
                    'keterangan' => $historyKeterangan,
                    'routes' => '/dashboard/master/data-pelanggan',
                    'route_name' => 'Data Pelanggan'
                ];
                $createHistory = $this->feature_helpers->createHistory($dataHistory);
                
                $newDataPelanggan = Pelanggan::findOrFail($new_pelanggan->id);

                return response()->json([
                    'success' => true,
                    'message' => "Pelanggan dengan nama {$newDataPelanggan->nama}, successfully added✨!",
                    'data' => $newDataPelanggan
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
            $pelanggan = Pelanggan::whereNull('deleted_at')
            ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
            ->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => "Detail data pelanggan {$pelanggan->nama}✨!",
                'data' => $pelanggan
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
            $update_pelanggan = Pelanggan::findOrFail($id);
            $update_pelanggan->kode = $request->kode ? $request->kode : $update_pelanggan->kode;
            $update_pelanggan->nama = $request->nama ? $request->nama : $update_pelanggan->nama;
            $update_pelanggan->email = $request->email ? $request->email : $update_pelanggan->email;
            $update_pelanggan->telp = $request->telp ? $this->user_helpers->formatPhoneNumber($request->telp) : $update_pelanggan->telp;
            $update_pelanggan->alamat = $request->alamat ? strip_tags($request->alamat) : strip_tags($update_pelanggan->alamat);
            $update_pelanggan->pekerjaan = $request->pekerjaan ? $request->pekerjaan : $update_pelanggan->pekerjaan;
            $update_pelanggan->save();

            $userOnNotif = Auth::user();

            $data_event = [
                'routes' => 'data-pelanggan',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "{$update_pelanggan->nama}, berhasil diupdate 🤙!",
                'data' => $update_pelanggan->nama,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            $historyKeterangan = "{$userOnNotif->name}, berhasil melakukan update data pelanggan : [{$update_pelanggan->kode}], {$update_pelanggan->nama}";
            $dataHistory = [
                'user' => $userOnNotif->name,
                'keterangan' => $historyKeterangan,
                'routes' => '/dashboard/master/data-pelanggan',
                'route_name' => 'Data Pelanggan'
            ];
            $createHistory = $this->feature_helpers->createHistory($dataHistory);

            if($update_pelanggan) {
                return response()->json([
                    'success' => true,
                    'message' => "Pelanggan dengan nama {$update_pelanggan->nama}, successfully updated✨!",
                    'data' => $update_pelanggan
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

            if($userRole->name === "MASTER" || $userRole->name === "ADMIN" || $userRole->name === "KASIR") {
                $pelanggan = Pelanggan::whereNull('deleted_at')
                ->findOrFail($id);
                $pelanggan->delete();
                $data_event = [
                    'alert' => 'error',
                    'routes' => 'data-pelanggan',
                    'type' => 'removed',
                    'notif' => "{$pelanggan->nama}, has move to trash, please check trash!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data pelanggan {$pelanggan->nama} has move to trash, please check trash"
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
