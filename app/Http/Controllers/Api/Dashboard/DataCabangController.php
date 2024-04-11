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
use App\Models\{Cabang};
use Auth;


class DataCabangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $keywords = $request->query('keywords');
            $kode = $request->query('kode');

            if($keywords) {
                $cabangs = Cabang::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'alamat', 'max_piutang', 'saldo_piutang', 'nopwp', 'pelanggan_kena_pajak')
                ->where('nama', 'like', '%'.$keywords.'%')
                // ->orderByDesc('harga_toko')
                ->orderByDesc('id')
                ->paginate(10);
            }else {
                $cabangs = Cabang::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'alamat', 'max_piutang', 'saldo_piutang', 'nopwp', 'pelanggan_kena_pajak')
                ->where('kode', 'like', '%'.$kode.'%')
                // ->orderByDesc('harga_toko')
                ->orderByDesc('id')
                ->paginate(10);
            }

            return new ResponseDataCollect($cabangs);
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
