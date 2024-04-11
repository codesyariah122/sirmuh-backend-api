<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{Barang, KategoriBarang, Kategori};
use Auth;

class DataKategoriBarangController extends Controller
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

    public function kategori_supplier_lists(Request $request)
    {
        try {
            $keywords = $request->query('keywords');

            if($keywords) {
                $barangs = Kategori::whereNull('deleted_at')
                ->select('kode', 'description')
                ->where('kode', 'like', '%'.$keywords.'%')
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
            } else {
                $barangs =  Kategori::whereNull('deleted_at')
                ->select('kode', 'description')
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
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

            if($keywords) {
                $barangs = KategoriBarang::whereNull('deleted_at')
                ->select('id','nama')
                ->where('nama', 'like', '%'.$keywords.'%')
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
            } else {
                $barangs =  KategoriBarang::whereNull('deleted_at')
                ->select('id','nama')
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
            }

            return new ResponseDataCollect($barangs);
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
        try{
            $validator = Validator::make($request->all(), [
                'nama' => 'required'
            ]);


            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $checkAlready = KategoriBarang::whereNama($request->nama)->first();

            if($checkAlready) {
                return response()->json([
                    'error' => true,
                    'message' => $request->nama." has already been taken 💥"
                ], 400);
            }

            $kategori = new KategoriBarang;
            $kategori->nama = $request->nama;
            $kategori->save();

            $data_event = [
                'routes' => 'data-kategori',
                'type' => 'add-data',
                'notif' => "{$kategori->nama}, successfully added✨",
                'user' => Auth::user()
            ];

            event(new EventNotification($data_event));

            $newKategori = KategoriBarang::findOrFail($kategori->id);
            return response()->json([
                'success' => true,
                'message' => 'New kategori barang has been created 👏',
                'data' => $newKategori
            ]);

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
        try{
            $kategori = KategoriBarang::findOrFail($id);
            $kategori->nama = $request->nama;
            $kategori->save();

            $data_event = [
                'routes' => 'data-kategori',
                'type' => 'updated',
                'notif' => "{$kategori->nama}, successfully updated!",
                'user' => Auth::user()
            ];

            event(new EventNotification($data_event));

            $newKategori = KategoriBarang::findOrFail($kategori->id);
            return response()->json([
                'success' => true,
                'message' => 'Kategori barang successfully updated ✅',
                'data' => $newKategori
            ]);

        } catch (\Throwable $th) {
            \Log::error($th);
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
        try{
            $kategori = KategoriBarang::whereNull('deleted_at')
                ->findOrFail($id);
            $kategori->delete();

            $data_event = [
                'alert' => 'error',
                'routes' => 'data-kategori',
                'type' => 'removed',
                'notif' => "{$kategori->nama}, has move to trash, please check trash!",
                'user' => Auth::user()
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Data kategori {$kategori->nama} has move to trash, please check trash"
            ]);

        } catch (\Throwable $th) {
            \Log::error($th);
            throw $th;
        }
    }
}
