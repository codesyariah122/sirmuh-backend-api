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
use App\Models\{Hutang};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use Auth;


class DataHutangController extends Controller
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
            $sortName = $request->query('sort_name');
            $sortType = $request->query('sort_type');
            $startDate = $request->query("start_date");
            $endDate = $request->query("end_date");

            $query = DB::table('hutang')
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo', 'itempembelian.id as itempembelian_id', 'itempembelian.kode as itempembelian_kode', 'itempembelian.qty', 'itempembelian.subtotal','supplier.nama as nama_supplier', 'barang.kode as kode_barang', 'barang.nama as barang_nama', 'barang.hpp as barang_harga_beli')
            ->leftJoin('pembelian', 'hutang.kode', '=', 'pembelian.kode')
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->where('pembelian.jt', '>', 0);

            if ($keywords) {
                $query->where('hutang.supplier', 'like', '%' . $keywords . '%');
            }

            if ($sortName && $sortType) {
                $query->orderBy($sortName, $sortType);
            } else {
                if($startDate && $endDate) {
                    $query->whereBetween('hutang.tanggal', [$startDate, $endDate]);
                }
            }
            $query->orderByDesc('hutang.id');

            $hutangs = $query->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'List data hutang',
                'data' => $hutangs
            ], 200);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function data_hutang()
    {
        try {

        }catch (\Throwable $th) {
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
            $query =  DB::table('hutang')
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo')
            ->leftJoin('pembelian', 'hutang.kode', '=', 'pembelian.kode');
            $query->orderByDesc('hutang.id');

            $hutang = $query->where('hutang.id', $id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Detail hutang',
                'data' => $hutang
            ], 200);
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
