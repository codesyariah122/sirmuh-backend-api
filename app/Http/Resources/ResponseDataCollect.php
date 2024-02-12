<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ResponseDataCollect extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $collects = collect($this->collection);
        
        $collects = $collects->map(function($item) {
            return $item;
        });

        return [
            'success' => true,
            'message' => 'Data Lists 📋 !',
            'total' => count($collects),
            'data' => $collects
        ];
    }
}
