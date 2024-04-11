<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Return Penjualan - {{$kode}}</title>

    <style>
        table td {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: bold;
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
    <h4>INVOICE</h4>
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top;">
                <b>Kepada</b>
            </td>
            <td rowspan="4" width="50%" style="vertical-align: top;">
                <b>{{ $toko['name'] }}</b> <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="80">
                <br>
                <address>
                    {{ $toko['address'] }}
                </address>
                <br>
                {{$helpers->format_tanggal($penjualan['tanggal'])}}
                <br>
                <b>Kode Return : </b>
                <b>{{$penjualan->kode}}</b>
            </td>
        </tr>
        <tr>
            <td>
                {{$penjualan->nama_pelanggan}}({{$penjualan->pelanggan}})
            </td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td>Kasir:  {{ strtoupper($penjualan->operator) }}</td>
        </tr>
        <tr>
            <td>Type: Return Penjualan</td>
        </tr>
    </table>

    <br/>
    <table class="data" width="100%">
        <thead>
            <tr>
                <th>Barang</th>
                <th>Supplier</th>
                <th>Harga Satuan</th>
                <th>Qty Return</th>
                <th>Status</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ $penjualan->nama_barang }} ({{$penjualan->kode_barang}})</td>
                <td class="text-center">{{ $penjualan->nama_supplier }} ({{$penjualan->supplier}})</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->harga) }}</td>
                <td class="text-left">{{ $penjualan->qty." ".$penjualan->satuan }}</td>
                <td class="text-center">{{ $penjualan->kembali !== 'True' ? "Belum Diterima" : "Sudah Diterima" }}</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->jumlah) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><b>No Faktur</b></td>
                <td class="text-left"> <b> {{$penjualan->kode_item}}</b> </td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><b>Qty Penjualan</b></td>
                <td class="text-right"><b>{{ intval($penjualan->last_qty) }}{{$penjualan->satuan}}</b></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><b>Subtotal</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($penjualan->subtotal) }}</b></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
