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
    <br>
    <h4>INVOICE</h4>
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top;">
                <b>Kepada</b>
            </td>
            <td rowspan="4" width="40%" style="vertical-align: top;">
                <b>{{ $toko['name'] }}</b> <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="100">
                <br>
                <address>
                    {{ $toko['address'] }}
                </address>
                <br>
                {{$toko['tanggal']}}
            </td>
        </tr>
        <tr>
            <td>
                {{$pembelian->nama_supplier}}({{$pembelian->supplier}})
            </td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td>Kasir:  {{ strtoupper($pembelian->operator) }}</td>
        </tr>
    </table>

    <br/>
    <table class="data" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Pembayaran</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
                <td>{{ $item->kode_barang }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->harga_beli) }}</td>
                <td class="text-right">{{ round($item->qty)." ".$item->satuan }}</td>
                <td class="text-right">{{ $item->visa }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><b>Total Bayar</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->jumlah) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diskon</b></td>
                <td class="text-right"><b>{{  $helpers->format_uang($pembelian->diskon) }}</b></td>
            </tr>
            
            @if($pembelian->visa === 'HUTANG')
            <tr>
                <td colspan="6" class="text-right"><b>Bayar DP</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->diterima) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Hutang</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->hutang) }}</b></td>
            </tr>
            @else
            <tr>
                <td colspan="6" class="text-right"><b>Diterima</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->diterima) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Kembali</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->diterima - $pembelian->jumlah) }}</b></td>
            </tr>
            @endif
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
