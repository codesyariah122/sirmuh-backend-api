<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Return Pembelian - {{$kode}}</title>

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
                {{$helpers->format_tanggal($pembelian['tanggal'])}}
                <br>
                <b>Kode Return : </b>
                <b>{{$pembelian->kode}}</b>
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
                <td colspan="5" class="text-right"><b>No Faktur</b></td>
                <td class="text-left"> <b>{{$pembelian->kode_item}}</b> </td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><b>Qty Pembelian</b></td>
                <td class="text-right"><b>{{ intval($pembelian->last_qty) }}{{$pembelian->satuan}}</b></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><b>Subtotal Pembelian</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->subtotal) }}</b></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><b>Total Qty Diterima</b></td>
                <td class="text-right"><b>{{ intval($pembelian->last_qty) - intval($pembelian->qty) }}{{$pembelian->satuan}}</b></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><b>Total Diterima</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->subtotal) }}</b></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
