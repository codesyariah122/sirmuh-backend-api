<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Events\EventNotification;
use App\Models\{User, Toko};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};


class DataPerusahaanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // public function __construct()
    // {
    //     $this->middleware(function ($request, $next) {
    //         if ($request->route()->getActionMethod() === 'index') {
    //             return $next($request);
    //         }

    //         if (Gate::allows('data-perusahaan')) {
    //             return $next($request);
    //         }

    //         return response()->json([
    //             'error' => true,
    //             'message' => 'Anda tidak memiliki cukup hak akses'
    //         ]);
    //     });
    // }

    public function index()
    {
        try {
            $tokos = Toko::whereNull('deleted_at')
            ->with('users')
            ->take(2)
            ->get();

            $tokos->transform(function ($toko) {
                $toko->koordinat = DB::select(DB::raw("SELECT ST_AsText(koordinat) as text FROM tokos WHERE id = :id"), ['id' => $toko->id])[0]->text;
                return $toko;
            });

            return new ResponseDataCollect($tokos);
        }catch (\Throwable $th) {
            \Log::error($th);
            return response()->json(['error' => true, 'message' => 'Terjadi kesalahan saat memproses data.'], 500);
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
            'name' => 'required',
            'logo' => 'image|mimes:jpg,png,jpeg|max:2048',
        ]);


         if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $userOwner = User::findOrFail(1);
        $newToko = new Toko;

        $newToko->name = $request->name;

        if ($request->file('logo')) {
            $logo = $request->file('logo');
            $extension = $logo->getClientOriginalExtension();

            $filename = 'logo_' . time() . '.' . $extension;

            $file = $logo->storeAs(trim(preg_replace('/\s+/', '', '/tokos')), $filename, 'public');

            $newToko->logo = $filename;
        }

        if ($request->file('banner')) {
            $banner = $request->file('banner');

            $extension = $banner->getClientOriginalExtension();

            $filename = 'banner_' . time() . '.' . $extension;

            $file = $banner->storeAs(trim(preg_replace('/\s+/', '', '/tokos')), $filename, 'public');

            $newToko->banner = $filename;
        }

        $newToko->phone = $request->phone;
        $newToko->email = $request->email;
        $newToko->address = $request->address;
        $newToko->kota = $request->kota;
        $newToko->provinsi = $request->provinsi;
        $newToko->negara = $request->negara;
        $newToko->koordinat = DB::raw("ST_GeomFromText('POINT({$request->longitude} {$request->latitude})')");
        $newToko->about = $request->about;
        $newToko->npwp = $request->npwp;
        $newToko->nppkp = $request->nppkp;
        $newToko->kode_lokasi = $request->kode_lokasi;

        $newToko->save();

        if ($newToko) {
            $newTokoId = $newToko->id;
            $userOwner->tokos()->sync($newTokoId, false);


            $newTokoSaved = Toko::where('id', $newToko->id)
            ->select('id', 'name', 'logo')
            ->with('users')
            ->get();

            $data_event = [
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "{$newToko->name}, baru saja ditambahkan 🤙!",
                'data' => $newToko->name,
            ];

            event(new EventNotification($data_event));

            return new RequestDataCollect($newTokoSaved);
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
