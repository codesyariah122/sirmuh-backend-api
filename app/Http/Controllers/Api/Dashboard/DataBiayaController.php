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
use App\Models\{Biaya,Roles};
use Auth;

class DataBiayaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $keywords = $request->query('keywords');
        $kode = $request->query('kode');
        $sort = $request->query('sort');

        if($keywords) {
            $biaya = Biaya::whereNull('deleted_at')
            ->select('id', 'kode', 'nama', 'saldo')
            ->where(function($query) use ($keywords) {
                $query->where('nama', 'like', '%' . $keywords . '%')
                ->orWhere('kode', 'like', '%' . $keywords . '%');
            })
            ->limit(10)
            ->orderBy('id', 'ASC')
            ->paginate(10);
        } else if($kode) {
            $biaya = Biaya::whereNull('deleted_at')
            ->select('id', 'kode', 'nama', 'saldo')
            ->where('kode', 'like', '%' . $kode . '%')
            ->limit(10)
            ->orderBy('id', 'ASC')
            ->paginate(10);
        } else {
         $biaya =  Biaya::whereNull('deleted_at')
         ->select('id', 'kode', 'nama', 'saldo')
         ->orderBy('id', 'ASC')
         ->limit(10)
         ->paginate(10);
     }

     return new ResponseDataCollect($biaya);
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

            $existing_biaya = Biaya::whereNama($request->nama)->first();

            if($existing_biaya) {
                return response()->json([
                    'error' => true,
                    'message' => "Biaya dengan nama {$existing_biaya->nama}, has already been taken✨!"
                ]);
            }

            $new_biaya = new Biaya;
            $new_biaya->kode = strtoupper(implode('', $substringArray));
            $new_biaya->nama = $request->nama;
            $new_biaya->saldo = $request->saldo;
            $new_biaya->save();

            if($new_biaya) {
                $userOnNotif = Auth::user();
                $data_event = [
                    'routes' => 'biaya',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "{$new_biaya->nama}, baru saja ditambahkan 🤙!",
                    'data' => $new_biaya->nama,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                $newDataBiaya = Biaya::findOrFail($new_biaya->id);
                return response()->json([
                    'success' => true,
                    'message' => "Data biaya dengan nama {$newDataBiaya->nama}, successfully added✨!",
                    'data' => $newDataBiaya
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
            $biaya = Biaya::whereNull('deleted_at')
            ->select("id", "kode", "nama", "saldo")
            ->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => "Detail data biaya {$biaya->nama}✨!",
                'data' => $biaya
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
            $new_biaya = Biaya::findOrFail($id);
            if($request->nama) {
                $kode = explode(' ', $request->nama);
                $substringArray = [];

                foreach ($kode as $i) {
                    $substringArray[] = substr($i, 1, 2);
                } 
                $new_biaya->kode = strtoupper(implode('', $substringArray));
            } else {
                $new_biaya->kode = $newBiaya->kode;
            }
            $new_biaya->nama = $request->nama;
            $new_biaya->saldo = $request->saldo;
            $new_biaya->save();

            if($new_biaya) {
                $userOnNotif = Auth::user();
                $data_event = [
                    'routes' => 'biaya',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "{$new_biaya->nama}, berhasil diupdate 🤙!",
                    'data' => $new_biaya->nama,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                $newDataBiaya = Biaya::findOrFail($new_biaya->id);
                return response()->json([
                    'success' => true,
                    'message' => "Data biaya dengan nama {$newDataBiaya->nama}, successfully added✨!",
                    'data' => $newDataBiaya
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
                $biaya = Biaya::whereNull('deleted_at')
                ->findOrFail($id);
                $biaya->delete();
                $data_event = [
                    'alert' => 'error',
                    'routes' => 'data-biaya',
                    'type' => 'removed',
                    'notif' => "biaya dengan nama {$biaya->nama}, has move to trash, please check trash!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data pbiaya {$biaya->nama} has move to trash, please check trash"
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
