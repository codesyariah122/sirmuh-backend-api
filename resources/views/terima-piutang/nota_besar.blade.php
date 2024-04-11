<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembayaran Hutang -  {{$kode}}</title>

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
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top;">
                <b>Kepada</b>
            </td>
            <td rowspan="4" width="40%" style="vertical-align: top;">
                <b>{{ $toko['name'] }}</b> <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="80">
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
                <td colspan="6" class="text-right"><b>Total Beli</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($piutang->jumlah_penjualan) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diskon</b></td>
                <td class="text-right"><b>{{  $helpers->format_uang($piutang->diskon) }}</b></td>
            </tr>
            @if($piutang->status_lunas === 'False' || $piutang->status_lunas === '0')
                <tr>
                    <td colspan="6" class="text-right"><b>Hutang</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($piutang->jumlah_piutang) }}</b></td>
                </tr>
            @else 
                <tr>
                    <td colspan="6" class="text-right"><b>Diterima</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($piutang->jumlah_penjualan - $piutang->jumlah) }}</b></td>
                </tr>
            @endif
            
            @foreach($angsurans as $angsuran)
            <tr>
                <td colspan="6" class="text-right"><b>Angsuran ke {{$angsuran->angsuran_ke}} {{$angsuran->angsuran_ke == 1 ? '(DP Awal)' : ''}}</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($angsuran->bayar_angsuran) }}</b></td>
            </tr>
            @endforeach

            @if($piutang->status_lunas === 'False')
                <tr>
                    <td colspan="6" class="text-right"><b>Sisa Hutang:</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($piutang->piutang_penjualan) }}</b></td>
                </tr>
            @else
                <tr>
                    <td colspan="6" class="text-right"><b>Kembali</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($piutang->kembali) }}</b></td>
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