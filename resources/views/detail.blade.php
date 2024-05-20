<!DOCTYPE html>
<html lang="en">
<head>
    {{-- @php
    var_dump($detail->suppliers); die;
    @endphp --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$nama}}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">
    <div class="container mx-auto my-10 p-5 bg-white rounded shadow-md">
        <h1 class="text-2xl font-bold mb-5">{{ $detail->nama }}</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div>
                @if($detail->photo)
                <img src="{{ Storage::url($detail->photo) }}" alt="{{ $detail->nama }}" class="w-full h-auto rounded shadow-md">
                @else
                <img src="{{ asset('assets/images/default.png') }}" alt="{{ $detail->nama }}" class="w-full h-auto rounded shadow-md">
                @endif
            </div>
            <div>
                <table class="min-w-full bg-white">
                    <tbody>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Kode</td>
                            <td class="px-6 py-4 border-b border-gray-200">{{ $detail->kode }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Kategori</td>
                            <td class="px-6 py-4 border-b border-gray-200">{{ $detail->kategori }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Stok</td>
                            <td class="px-6 py-4 border-b border-gray-200">{{ $detail->toko }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Satuan Beli</td>
                            <td class="px-6 py-4 border-b border-gray-200">{{ $detail->satuanbeli }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Harga Toko</td>
                            <td class="px-6 py-4 border-b border-gray-200">Rp {{ number_format($detail->harga_toko, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Diskon</td>
                            <td class="px-6 py-4 border-b border-gray-200">{{ $detail->diskon }}%</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Supplier</td>
                            <td class="px-6 py-4 border-b border-gray-200">{{ $detail->supplier }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Kode Barcode</td>
                            <td class="px-6 py-4 border-b border-gray-200">{{ $detail->kode_barcode }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 border-b border-gray-200 font-bold">Keterangan</td>
                            <td class="px-6 py-4 border-b border-gray-200">{{ $detail->ket }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>