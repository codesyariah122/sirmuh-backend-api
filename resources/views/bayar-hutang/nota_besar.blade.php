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
            <td>: {{$hutang->po === 'True' ? "Pembelian P.O" : "Pembelian Langsung"}}</td>
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
            <td>Status : @if(intval($hutang->jml_hutang) === 0) Lunas @else Angsuran @endif</td>
        </tr>
    </table>

    <table class="data" width="100%">
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th>Supplier</th>
                <th>Harga Beli</th>
                <th>Qty</th>
                <th>Jumlah</th>
                <th>Dibayarkan</th>
                <th>Hutang</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $hutang->nama_barang }} - {{ $hutang->kode_barang }}</td>
                <td>{{ $hutang->nama_supplier }} ({{$hutang->supplier}})</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->harga_beli) }}</td>
                <td class="text-right">{{ round($hutang->qty)." ".$hutang->satuan }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian) }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian - $hutang->jumlah) }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah) }}</td>
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
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jumlah) }}</b></td>
            </tr>
            @if($hutang->angsuran_ke > 0)
            @foreach($angsurans as $angsuran)
            <tr>
                <td colspan="6" class="text-right"><b>Angsuran ke {{$angsuran->angsuran_ke}}</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($angsuran->bayar_angsuran) }}</b></td>
            </tr>
            @endforeach
            @endif
            <tr>
                <td colspan="6" class="text-right"><b>Sisa Hutang:</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jml_hutang) }}</b></td>
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