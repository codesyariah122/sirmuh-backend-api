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

    public function ekspeditions()
    {
        try {
            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_KEY')
            ])->get('https://api.rajaongkir.com/starter/courier');
            return response()->json([
                'success' => true,
                'message' => 'Lists of ekpeditions',
                'data'    => $response->json()
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function checkOngkir(Request $request)
    {
        try{
            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_KEY')
            ])->post('https://api.rajaongkir.com/starter/cost', [
                'origin'            => $request->origin,
                // 'type_ori'          => $request->type_ori,
                'destination'       => $request->destination,
                // 'type_dest'         => $request->destination_type,
                'weight'            => $request->weight,
                'courier'           => $request->courier
            ]);

            $ongkir = $response['rajaongkir']['results'];

            foreach ($ongkir as $layanan) {
                foreach ($layanan['costs'] as $cost) {

                    $biaya = intval($cost['cost'][0]['value']);

                    $biaya_per_berat = $biaya * $request->weight;

                    $cost['cost'][0]['biaya_per_berat'] = $biaya_per_berat;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Result Cost Ongkir',
                'data'    => $ongkir,
                'biaya_per_berat' => $biaya_per_berat
            ]);
        }catch(Exception $e){
             return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
