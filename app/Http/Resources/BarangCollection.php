<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BarangCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $collects = collect($this->collection);

        $barangs = $collects->map(function($barang) {
            return $barang;
        });

        return [
            'success' => true,
            'message' => 'Data Lists 📋 !',
            'data' => $barangs
        ];
    }
}
