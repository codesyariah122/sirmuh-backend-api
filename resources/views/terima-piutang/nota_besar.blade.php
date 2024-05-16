<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembayaran Piutang -  {{$kode}}</title>
    <style>
        * {
            font-family: 'Courier New', Courier, monospace;
            margin-top: .1rem;
            letter-spacing: 1px;
            font-size: 11px;
        }

        table.data th {
            background: rgb(239, 239, 240);
        }

        table.data td,
        table.data th {
            border: 1px solid #ccc;
            padding: 4px;
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
    <h4 style="margin-top: 1rem;">INVOICE</h4>
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
            {{$helpers->format_tanggal(date('Y-m-d'))}}
            <br>
            NO INVOICE : 
            <b>{{$piutang->kode}}</b>
        </td>
    </tr>

    <tr>
        <td>
            {{ucfirst($piutang->nama_pelanggan)}}({{$piutang->pelanggan}})
            <br>
            <address>
                {{$piutang->pelanggan_alamat !== NULL ? $hutang->pelanggan_alamat : 'Belum ada alamat'}}
            </address>
        </td>
    </tr>
</table>

<table class="data" width="100%" style="margin-top: -.3rem;">
    <thead>
        <tr>
           <th>Kode Transaksi</th>
           <th>Tanggal Transaksi</th>
           <th>Total Belanja</th>
           <th>Dibayarkan</th>
           <th>Status</th>
           <th>Piutang</th>
       </tr>
   </thead>
   <tbody>
    <tr>
        <td class="text-center"> {{$piutang->kode_transaksi}} </td>
        <td class="text-center"> {{$helpers->format_tanggal_transaksi($piutang->tanggal_transaksi)}} </td>
        <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan) }}</td>
        <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan - $piutang->jumlah) }}</td>
        <td class="text-center">{{ $piutang->visa }}</td>
        <td class="text-right">{{ $helpers->format_uang($piutang->jumlah) }}</td>
    </tr>
</tbody>
<tfoot>
    <tr>
        <td colspan="5" class="text-right">Total Belanja</td>
        <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan) }}</td>
    </tr>
    <tr>
        <td colspan="5" class="text-right">Diskon</td>
        <td class="text-right">{{  $helpers->format_uang($piutang->diskon) }}</td>
    </tr>
    @if($piutang->status_lunas === 'False' || $piutang->status_lunas === '0')
    <tr>
        <td colspan="5" class="text-right">Hutang</td>
        <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_piutang) }}</td>
    </tr>
        {{-- @else 
        <tr>
            <td colspan="5" class="text-right">Diterima</td>
            <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan - $piutang->jumlah) }}</td>
        </tr> --}}
        @endif

        @foreach($angsurans as $angsuran)
        <tr>
            <td colspan="5" class="text-right">Angsuran ke {{$angsuran->angsuran_ke}} {{$angsuran->angsuran_ke == 1 ? '(DP Awal)' : ''}}</td>
            <td class="text-right">{{ $helpers->format_uang($angsuran->bayar_angsuran) }}</td>
        </tr>
        @endforeach

        @if($piutang->status_lunas === 'False')
        <tr>
            <td colspan="5" class="text-right">Sisa Hutang:</td>
            <td class="text-right">{{ $helpers->format_uang($piutang->piutang_penjualan) }}</td>
        </tr>
        @else
        <tr>
            <td colspan="5" class="text-right">Kembali</td>
            <td class="text-right">{{ $helpers->format_uang($piutang->kembali) }}</td>
        </tr>
        @endif
    </tfoot>
</table>

<table width="100%" style="margin-top: 1.5rem;">
    <tr>
        <td class="text-right">
            <span style="font-weight: 800;border-top: 2px solid black;width: 10%;height: 12px;">
                <br>
                {{ strtoupper($piutang->operator) }}
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