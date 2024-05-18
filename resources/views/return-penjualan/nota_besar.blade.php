<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Return Penjualan - {{$kode}}</title>

    <style> 
        * { 
            font-family: 'Draft Condensed', sans-serif; margin-top: .1rem; 
            letter-spacing: 1.5px; 
            font-size: 12px; 
        }
        table.data th {
            background: rgb(239, 239, 240);
        }
        table.data td,
        table.data th {
            border: 1px solid #ccc;
            padding: 3px;
            font-size: 11px;
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
        table tfoot ul li {
            list-style: none;
            margin-left: -3rem;
        }
    </style>
</head>
<body>
    <h4 style="margin-top: .5rem;">INVOICE</h4> 
    <table width="100%" style="border-collapse: collapse; margin-top: .5rem;">
        <tr>
            <td style="vertical-align: top;">
                Kepada
            </td>
            <td rowspan="6" width="40%" style="vertical-align: top;">
             <span style="font-weight: 800; font-size: 14px;">{{ $toko['name'] }}</span>  @if($toko['name'] === 'CV Sangkuntala Jaya Sentosa')
             <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="60" />
             @else
             <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="120" />
             @endif
             <br>
             <span>{{ $toko['name'] }} </span>                
             <br>
             <address>
                {{ $toko['address'] }}
            </address>
            <br>
            @php
            use Carbon\Carbon;
            $currentDate = Carbon::now()->format('d-m-Y');
            @endphp
            Tanggal : {{$helpers->format_tanggal_transaksi($currentDate)}}
            <br>
            NO INVOICE : 
            <b>{{$penjualan->kode}}</b>
        </td>
    </tr>

    <tr>
        <td>
            {{ucfirst($penjualan->nama_pelanggan)}}({{$penjualan->pelanggan}})
            <br>
            <address>
                {{$penjualan->pelanggan_alamat !== NULL ? $penjualan->pelanggan_alamat : 'Belum ada alamat'}}
            </address>
        </td>
    </tr>
</table>

<table class="data" width="100%" style="margin-top:-1.5rem;">
    <thead>
        <tr>
            <th>Kode Faktur</th>
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
            <td class="text-center" style="width: 20%;">  {{$penjualan->kode_item}} </td>
            <td class="text-left">{{ $penjualan->nama_barang }} ({{$penjualan->kode_barang}})</td>
            <td class="text-left">{{ $penjualan->nama_supplier }} ({{$penjualan->supplier}})</td>
            <td class="text-right">{{ $helpers->format_uang($penjualan->harga) }}</td>
            <td class="text-center">{{ $penjualan->qty."".$penjualan->satuan }}</td>
            <td class="text-center">{{ $penjualan->kembali !== 'True' ? "Belum Diterima" : "Sudah Diterima" }}</td>
            <td class="text-right">{{ $helpers->format_uang($penjualan->jumlah) }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="text-right">Qty Penjualan</td>
            <td class="text-right">{{ intval($penjualan->last_qty) }}{{$penjualan->satuan}}</td>
        </tr>
        <tr>
            <td colspan="6" class="text-right">Subtotal</td>
            <td class="text-right">{{ $helpers->format_uang($penjualan->subtotal) }}</td>
        </tr>
    </tfoot>
</table>

<table width="100%" style="margin-top: 1.5rem;">
    <tr>
        <td class="text-right">
            <span style="font-weight: 800;border-top: 2px solid black;width: 10%;height: 12px;">
                <br>
                {{ strtoupper($penjualan->operator) }}
            </span>
        </td>
    </tr>
    <tr>
        <td>
            <span style="margin-top: -3rem; font-weight: 800;">TERIMA KASIH ATAS PEMBELIANNYA</span>
        </td>
    </tr>
</table>
</body>
</html>
