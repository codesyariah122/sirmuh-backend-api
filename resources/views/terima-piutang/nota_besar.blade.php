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
            line-height: 5px;
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
    <table width="100%" style="border-collapse: collapse; margin-top: -.5rem;">
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
                {{$piutang->nama_supplier}}({{$piutang->supplier}})
            </td>
        </tr>

        <tr>
            <td>
                NO INVOICE : 
                {{$piutang->kode}}
                <br>
                {{$helpers->format_tanggal($piutang['tanggal'])}}
            </td>
        </tr>
        <tr>
            <td>
                Jenis : {{$piutang->po == 'True' ? 'Pembelian P.O' : 'Pembelian Langsung'}}
            </td>
        </tr>
    </table>
    
    <table class="data" width="100%" style="margin-top: -.3rem;">
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

    <table width="100%" style="margin-top: .5rem;">
        <tr>
            <td class="text-right">
                <h4>Kasir</h4>
                <br>
                <span style="margin-top:-.2rem;">{{ strtoupper($piutang->operator) }}</span>
            </td>
        </tr>
    </table>
</body>
</html>