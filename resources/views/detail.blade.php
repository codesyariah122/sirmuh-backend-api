<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $detail->nama }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">
    <div class="container mx-auto my-10 p-5 bg-white rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold mb-6 text-center">{{ $detail->nama }}</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="flex justify-center">
                @if($detail->photo)
                <img src="{{ Storage::url($detail->photo) }}" alt="{{ $detail->nama }}" class="w-full max-w-md h-auto rounded-lg shadow-md">
                @else
                <img src="{{ asset('assets/images/default.png') }}" alt="{{ $detail->nama }}" class="w-full max-w-md h-auto rounded-lg shadow-md">
                @endif
            </div>
            <div>
                <table class="min-w-full bg-white border-collapse">
                    <tbody>
                        @foreach ([
                            'Kode' => $detail->kode_barang,
                            'Kategori' => $detail->kategori_barang,
                            'Stok' => intval($detail->toko).$detail->satuanbeli,
                            'Harga Toko' => 'Rp ' . number_format($detail->harga_toko, 0, ',', '.'),
                            'Diskon' => $detail->diskon . '%',
                            'Supplier' => $detail->nama_supplier,
                            'Kode Barcode' => '<img src="' . $detail->kode_barcode . '" class="w-[85px] h-[50px]" alt="barcode"/> <small class="font-semibold">'.$detail->kode_barang.'</small>'
                            ] as $label => $value)
                            <tr>
                                <td class="px-6 py-4 border-b border-gray-200 font-bold text-gray-700">{{ $label }}</td>
                                <td class="px-6 py-4 border-b border-gray-200">{!! $value !!}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-10 flex justify-center">
                <button class="bg-blue-500 text-white font-bold py-2 px-4 rounded-lg shadow hover:bg-blue-700 transition duration-300">
                    Add to Cart
                </button>
            </div>
        </div>
    </body>
    </html>
