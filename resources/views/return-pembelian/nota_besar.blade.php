<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Return Pembelian - {{$kode}}</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Dot Matrix', sans-serif;
        }
        table td {
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
    <h4>INVOICE</h4>
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top;">
                Kepada
            </td>
            <td rowspan="4" width="50%" style="vertical-align: top;">
                {{ $toko['name'] }} <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="80">
                <br>
                <address>
                    {{ $toko['address'] }}
                </address>
                <br>
                {{$helpers->format_tanggal($pembelian['tanggal'])}}
                <br>
                Kode Return : 
                {{$pembelian->kode}}
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
        <tr>
            <td>Type: Return Pembelian</td>
        </tr>
    </table>

    <br/>
    <table class="data" width="100%">
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Harga Satuan</th>
                <th>Qty Return</th>
                <th>Status</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $pembelian->kode_barang }}</td>
                <td>{{ $pembelian->nama_barang }}</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->harga) }}</td>
                <td class="text-left">{{ $pembelian->qty." ".$pembelian->satuan }}</td>
                <td class="text-center">{{ $pembelian->kembali !== 'True' ? "Belum Diterima" : "Sudah Diterima" }}</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->jumlah) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">No Faktur</td>
                <td class="text-left"> {{$pembelian->kode_item}} </td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">Qty Pembelian</td>
                <td class="text-right">{{ intval($pembelian->last_qty) }}{{$pembelian->satuan}}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">Subtotal Pembelian</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->subtotal) }}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">Total Qty Diterima</td>
                <td class="text-right">{{ intval($pembelian->last_qty) - intval($pembelian->qty) }}{{$pembelian->satuan}}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">Total Diterima</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->subtotal) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
