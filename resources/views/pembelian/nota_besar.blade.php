<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembelian - {{$kode}}</title>

    <style>
        table td {
            /* font-family: Arial, Helvetica, sans-serif; */
            font-size: 14px;
        }
        table.data td,
        table.data th {
            border: 1px solid #ccc;
            padding: 5px;
        }
        table.data {
            border-collapse: collapse;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td rowspan="4" width="60%">
                <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="100">
                <br>
                {{ $toko['name'] }}
                <br>
                <address>
                    {{ $toko['address'] }}
                </address>
            </td>
        </tr>
        <tr>
            <td>
                No
            </td>
            <td>: {{ $pembelian->kode }}</td>
        </tr>
        <tr>
            <td>Type</td>
            <td>: {{$pembelian->po === 'True' ? "Purchase Order" : "Pembelian Langsung"}}</td>
        <tr>
            <td>
                Supplier
            </td>
            <td>: {{ $pembelian->nama_supplier }}
                @if($pembelian->alamat_supplier)
                <br>
                <address>
                    {{ $pembelian->alamat_supplier ?? '-' }}
                </address>
                @endif
                <br>
            </td>
        </tr>
        <tr>
            <td>Operator : {{ strtoupper($pembelian->operator) }}</td>
        </tr>
    </table>

    <table class="data" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Diskon</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td>{{ $item->kode_barang }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->harga_beli) }}</td>
                <td class="text-right">{{ round($item->qty)." ".$item->satuan }}</td>
                <td class="text-right">{{ $item->diskon }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><b>Total Harga</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->jumlah) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diskon</b></td>
                <td class="text-right"><b>{{  $helpers->format_uang($pembelian->diskon) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Total Bayar</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->bayar) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diterima</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->diterima) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Kembali</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->diterima - $pembelian->jumlah) }}</b></td>
            </tr>
        </tfoot>
    </table>

  {{--   <table width="100%">
        <tr>
            <td><b>Terimakasih telah berbelanja dan sampai jumpa</b></td>
            <td class="text-center">
                Kasir
                <br>
                <br>
                {{ strtoupper($pembelian->operator) }}
            </td>
        </tr>
    </table> --}}
</body>
</html>