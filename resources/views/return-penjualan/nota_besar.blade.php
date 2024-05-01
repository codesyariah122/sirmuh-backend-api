<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Return Penjualan - {{$kode}}</title>

    <style>
        * {
            font-family: 'Courier New', Courier, monospace;
            margin-top: .1rem;
            letter-spacing: 1px;
        }

        table td {
            font-size: 13px;
        }
        table.data td,
        table.data th {
            border: 1px solid #ccc;
            padding: 2px;
            font-size: 10px;
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
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <h4>INVOICE</h4>
    <table width="100%" style="border-collapse: collapse; margin-top: -1rem;">
        <tr>
            <td style="vertical-align: top;">
                Kepada
            </td>
            <td rowspan="6" width="30%" style="vertical-align: top;">
                @if($toko['name'] === 'CV Sangkuntala Jaya Sentosa')
                <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="60" />
                @else
                <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="100" />
                @endif
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
                {{$pelanggan->nama_pelanggan}}({{$pelanggan->pelanggan}})
            </td>
        </tr>

        <tr>
            <td>
                NO INVOICE : 
                {{$pelanggan->kode}}
                <br>
                {{$helpers->format_tanggal($pelanggan['tanggal'])}}
            </td>
        </tr>
        <tr>
            <td>
                Jenis : Return {{$pelanggan->po == 'True' ? 'Pembelian P.O' : 'Pembelian Langsung'}}
            </td>
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
                <td colspan="5" class="text-right">No Faktur</td>
                <td class="text-left">  {{$penjualan->kode_item}} </td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">Qty Penjualan</td>
                <td class="text-right">{{ intval($penjualan->last_qty) }}{{$penjualan->satuan}}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">Subtotal</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->subtotal) }}</td>
            </tr>
        </tfoot>
    </table>

    <table width="100%" style="margin-top: -.1rem;">
        <tr>
            <td class="text-right">
                <h4>Kasir</h4>
                <br>
                <span style="margin-top:-.2rem;">{{ strtoupper($penjualan->operator) }}</span>
            </td>
        </tr>
    </table>
</body>
</html>
