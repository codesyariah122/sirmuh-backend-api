<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembayaran Hutang -  {{$kode}}</title>
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
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top;">
                Kepada
            </td>
            <td rowspan="4" width="40%" style="vertical-align: top;">
                {{ $toko['name'] }} <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="80">
                <br>
                <address>
                    {{ $toko['address'] }}
                </address>
                <br>
                {{$helpers->format_tanggal($piutang->tanggal_penjualan)}}
                <br>
                NO INVOICE : 
                {{ $piutang->kode }}
            </td>
        </tr>
        <tr>
            <td>{{ $piutang->nama_pelanggan }} ({{$piutang->pelanggan}})</td>
        </tr>
        <tr>
            <td>Kasir:  {{ strtoupper($piutang->operator) }}</td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td>Type: {{$piutang->po === 'True' ? "Penjualan P.O" : $piutang->jenis_penjualan}}</td>
        </tr>
    </table>
    
    <table class="data" width="100%">
        <thead>
            <tr>
                <th>Nama Barang</th>
                <!-- <th>Pelanggan</th> -->
                <td>Kode Barang</td>
                <th>Harga</th>
                <th>Qty</th>
                <th>Jumlah</th>
                <th>Dibayarkan</th>
                <th>Piutang</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $piutang->nama_barang }}</td>
                <td>{{ $piutang->kode_barang }}</td>
                <!-- <td>{{ $piutang->nama_pelanggan }} ({{$piutang->pelanggan}})</td> -->
                <td class="text-right">{{ $helpers->format_uang($piutang->harga) }}</td>
                <td class="text-right">{{ round($piutang->qty)." ".$piutang->satuan }}</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan) }}</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan - $piutang->jumlah) }}</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right">Total Beli</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="text-right">Diskon</td>
                <td class="text-right">{{  $helpers->format_uang($piutang->diskon) }}</td>
            </tr>
            @if($piutang->status_lunas === 'False' || $piutang->status_lunas === '0')
            <tr>
                <td colspan="6" class="text-right">Hutang</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_piutang) }}</td>
            </tr>
            @else 
            <tr>
                <td colspan="6" class="text-right">Diterima</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan - $piutang->jumlah) }}</td>
            </tr>
            @endif
            
            @foreach($angsurans as $angsuran)
            <tr>
                <td colspan="6" class="text-right">Angsuran ke {{$angsuran->angsuran_ke}} {{$angsuran->angsuran_ke == 1 ? '(DP Awal)' : ''}}</td>
                <td class="text-right">{{ $helpers->format_uang($angsuran->bayar_angsuran) }}</td>
            </tr>
            @endforeach

            @if($piutang->status_lunas === 'False')
            <tr>
                <td colspan="6" class="text-right">Sisa Hutang:</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->piutang_penjualan) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="6" class="text-right">Kembali</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->kembali) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    <p style="text-align: center; font-size:10px;">
        <p class="text-center">Semoga Lancar</p>
        <p class="text-center">&</p>
        <p class="text-center">Tetap Menjadi Langganan</p>
        <p class="text-center">*** TERIMA KASIH ****</p>
    </p>
</body>
</html>