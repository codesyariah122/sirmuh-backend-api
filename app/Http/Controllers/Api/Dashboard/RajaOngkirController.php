<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RajaOngkirController extends Controller
{
    public function provinces()
    {
        try {
            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_KEY')
            ])->get('https://api.rajaongkir.com/starter/province');

            $provinces = $response['rajaongkir']['results'];

            return response()->json([
                'success' => true,
                'message' => 'Get All Provinces',
                'data'    => $provinces
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function citys($id)
    {
        try {
            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_KEY')
            ])->get('https://api.rajaongkir.com/starter/city?&province='.$id.'');

            $cities = $response['rajaongkir']['results'];

            return response()->json([
                'success' => true,
                'message' => 'Get City By ID Provinces : '.$id,
                'data'    => $cities
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
