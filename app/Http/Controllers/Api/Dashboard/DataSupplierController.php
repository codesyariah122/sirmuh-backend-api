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

class DataSupplierController extends Controller
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
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
            } else if($kode) {
                $suppliers = Supplier::whereNull('deleted_at')
                ->select('id', 'nama', 'kode', 'alamat', 'kota', 'telp', 'fax', 'email', 'saldo_piutang')
                ->where('kode', 'like', '%' . $kode . '%')
                ->orderByDesc('id', 'DESC')
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
                    ->orderByDesc('id', 'DESC')
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
            $suppliers = Supplier::whereId($id)
            ->get();
            return new ResponseDataCollect($suppliers);
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
        try {
            $supplier = Supplier::findOrFail($id);
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
                'message' => "Data pelanggan {$pelanggan->nama} has move to trash, please check trash"
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
