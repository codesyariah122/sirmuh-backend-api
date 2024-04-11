<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataCanvas;

class DataCanvasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $canvas = DataCanvas::paginate(10);

            return response()->json([
              'success' => true,
              'message' => 'List data canvas 💼',
              'data' => $canvas
          ], 200);

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
     * @param  \App\Models\DataCanvas  $dataCanvas
     * @return \Illuminate\Http\Response
     */
    public function show(DataCanvas $dataCanvas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DataCanvas  $dataCanvas
     * @return \Illuminate\Http\Response
     */
    public function edit(DataCanvas $dataCanvas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DataCanvas  $dataCanvas
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DataCanvas $dataCanvas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DataCanvas  $dataCanvas
     * @return \Illuminate\Http\Response
     */
    public function destroy(DataCanvas $dataCanvas)
    {
        //
    }

}
