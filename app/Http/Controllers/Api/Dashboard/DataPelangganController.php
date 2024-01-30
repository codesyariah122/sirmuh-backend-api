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
use App\Models\{Pelanggan, Penjualan};


class DataPelangganController extends Controller
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
        //
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
            $pelanggan = Pelanggan::whereId($id)->get();
            return new ResponseDataCollect($pelanggan);
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
