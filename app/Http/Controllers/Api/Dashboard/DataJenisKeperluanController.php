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
use App\Models\{JenisKeperluan};
use Auth;

class DataJenisKeperluanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $query = JenisKeperluan::query()
            ->limit(10);

            $jenisKeperluan = $query->orderBy("id", "DESC")
            ->paginate(10);

            return new ResponseDataCollect($jenisKeperluan);
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
            'kode' => 'required'
        ]);

           if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $userOnNotif = Auth::user();

        $newJenisKeperluan = new JenisKeperluan;
        $newJenisKeperluan->kode = strtoupper($request->kode);
        $newJenisKeperluan->save();

        $data_event = [
            'routes' => 'jenis-keperluan',
            'alert' => 'success',
            'type' => 'add-data',
            'notif' => "Jenis keperluan {$newJenisKeperluan->kode}, successfully added 🤙!",
            'data' => $newJenisKeperluan,
            'user' => $userOnNotif
        ];

        event(new EventNotification($data_event));

        return response()->json([
            'success' => true,
            'message' => "Keperluan baru : {$newJenisKeperluan->kode}, berhasil ditambahkan 🤓👍",
            'data' => $newJenisKeperluan
        ]);
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
