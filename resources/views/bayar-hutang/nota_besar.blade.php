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
            font-family: 'Courier New', monospace,
            font-size: 13px;
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
                {{$helpers->format_tanggal($piutang->tanggal_penjualan)}}
                <br>
                <b>NO INVOICE : </b>
                <b>{{ $piutang->kode }}</b>
            </td>
        </tr>
        <tr>
            <td>{{ $hutang->nama_supplier }} ({{$hutang->supplier}})</td>
        </tr>
        <tr>
            <td>Kasir:  {{ strtoupper($hutang->operator) }}</td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td>Type: {{$hutang->po === 'True' ? "Penjualan P.O" : $hutang->jenis_penjualan}}</td>
        </tr>
    </table>

    <table class="data" width="100%">
        <thead>
            <tr>
                <th>Nama Barang</th>
                <!-- <th>Supplier</th> -->
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
                <!-- <td>{{ $hutang->nama_supplier }} ({{$hutang->supplier}})</td> -->
                <td class="text-right">{{ $helpers->format_uang($hutang->harga_beli) }}</td>
                <td class="text-right">{{ round($hutang->qty)." ".$hutang->satuan }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian) }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian - $hutang->jumlah) }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><b>Total Beli</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jumlah_pembelian) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diskon</b></td>
                <td class="text-right"><b>{{  $helpers->format_uang($hutang->diskon) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diterima</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jumlah_pembelian - $hutang->jumlah) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Hutang</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jumlah) }}</b></td>
            </tr>
            @foreach($angsurans as $angsuran)
            <tr>
                <td colspan="6" class="text-right"><b>Angsuran ke {{$angsuran->angsuran_ke}} {{$angsuran->angsuran_ke == 1 ? '(DP Awal)' : ''}}</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($angsuran->bayar_angsuran) }}</b></td>
            </tr>
            @endforeach
            <tr>
                <td colspan="6" class="text-right"><b>Sisa Hutang:</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->jml_hutang) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Kembali</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($hutang->diterima - $hutang->jumlah) }}</b></td>
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
                {{ strtoupper($hutang->operator) }}
            </td>
        </tr>
    </table> --}}
</body>
</html>