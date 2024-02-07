<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembayaran Hutang -  {{$kode}}</title>

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
            <td>: {{ $hutang->kode }}</td>
        </tr>
        <tr>
            <td>Type</td>
            <td>: {{$hutang->po === 'True' ? "Purchase Order" : "Pembelian Langsung"}}</td>
        <tr>
            <td>
                Supplier
            </td>
            <td>: {{ $hutang->nama_supplier }}
                @if($hutang->alamat_supplier)
                <br>
                <address>
                    {{ $hutang->alamat_supplier ?? '-' }}
                </address>
                @endif
                <br>
            </td>
        </tr>
        <tr>
            <td>Operator : {{ strtoupper($hutang->operator) }}</td>
            <td>Lunas : @if($hutang->lunas === 1 || $hutang->lunas === '1' || $hutang->lunas === 'true') ✅ @else ⛔ @endif</td>
        </tr>
    </table>

    <table class="data" width="100%">
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Harga Beli</th>
                <th>Qty</th>
                <th>Jumlah</th>
                <th>Dibayarkan</th>
                <th>Hutang</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $hutang->nama_barang }}</td>
                <td>{{ $hutang->kode_barang }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->harga_beli) }}</td>
                <td class="text-right">{{ round($hutang->qty)." ".$hutang->satuan }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian) }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->diterima) }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->hutang) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><b>Total Harga</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jumlah_pembelian) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diskon</b></td>
                <td class="text-right"><b>{{  $helpers->format_uang($hutang->diskon) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diterima</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->diterima) }}</b></td>
            </tr>
            @if($hutang->visa === 'HUTANG')
            <tr>
                <td colspan="6" class="text-right"><b>Hutang</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->hutang) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diangsur</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jumlah_hutang - $hutang->jumlah) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Sisa Hutang:</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jumlah) }}</b></td>
            </tr>
            @else
            <tr>
                <td colspan="6" class="text-right"><b>Kembali</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->diterima - $hutang->jumlah) }}</b></td>
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
                {{ strtoupper($hutang->operator) }}
            </td>
        </tr>
    </table> --}}
</body>
</html>