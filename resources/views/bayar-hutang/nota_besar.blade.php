<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembayaran Hutang -  {{$kode}}</title>
    {{-- @vite(['resources/css/app.css']) --}}
    <style>
        * {
            font-family: 'Courier New', Courier, monospace;
            margin-top: .1rem;
            letter-spacing: 1px;
            font-size: 11px;
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
                {{$hutang->nama_supplier}}({{$hutang->supplier}})
            </td>
        </tr>

        <tr>
            <td>
                NO INVOICE : 
                {{$hutang->kode}}
                <br>
                {{$helpers->format_tanggal($hutang['tanggal'])}}
            </td>
        </tr>
        <tr>
            <td>
                Jenis : {{$hutang->po == 'True' ? 'Pembelian P.O' : 'Pembelian Langsung'}}
            </td>
        </tr>
    </table>

    <table class="data" width="100%" style="margin-top: -1rem;">
        <thead>
            <tr>
                <th>Nama Barang</th>
                <!-- <th>Supplier</th> -->
                <th>Kode Barang</th>
                <th>Harga Beli</th>
                <th>Qty</th>
                <th>Jumlah</th>
                <th>Dibayarkan</th>
                <th>Hutang</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $hutang->nama_barang }}</td>
                <td>{{ $hutang->kode_barang }}</td>
                <!-- <td>{{ $hutang->nama_supplier }} ({{$hutang->supplier}})</td> -->
                <td class="text-right">{{ $helpers->format_uang($hutang->harga_beli) }}</td>
                <td class="text-right">{{ round($hutang->qty)." ".$hutang->satuan }}</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->qty * $hutang->harga_beli) }}</td>
                @if($hutang->po === "False")
                <td class="text-right">{{ $helpers->format_uang($hutang->bayar_pembelian) }}</td>
                @else
                @foreach($angsurans->first() ? [$angsurans->first()] : [] as $angsuran)
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian + $angsuran->bayar_angsuran) }}</td>
                @endforeach
                @endif
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah) }}</td>
            </tr>
        </tbody>
        <tfoot>
            @if($hutang->po === "False")
            <tr>
                <td colspan="6" class="text-right">Total Beli</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="6" class="text-right">Total Beli</td>
                <td class="text-right">{{$hutang->lunas === "True" ?  $helpers->format_uang($hutang->diterima) :  $helpers->format_uang($hutang->jumlah_pembelian)}}</td>
            </tr>
            @endif
            <tr>
                <td colspan="6" class="text-right">Diskon</td>
                <td class="text-right">{{  $helpers->format_uang($hutang->diskon) }}</td>
            </tr>
            <!-- <tr>
                <td colspan="6" class="text-right">Dp Awal</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian - $hutang->jumlah) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="text-right">Hutang</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah) }}</td>
            </tr> -->
            @foreach($angsurans as $angsuran)
            <tr>
                <td colspan="6" class="text-right">Angsuran ke {{$angsuran->angsuran_ke}} {{$angsuran->angsuran_ke == 1 ? '(DP Awal)' : ''}}</td>
                <td class="text-right">{{ $helpers->format_uang($angsuran->bayar_angsuran) }}</td>
            </tr>
            @endforeach
            @if($hutang->lunas === "True")
            <tr>
                <td colspan="6" class="text-right">Kembali:</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jml_hutang) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="6" class="text-right">Sisa Hutang:</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jml_hutang) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>
    <table width="100%" style="margin-top: -.1rem;">
        <tr>
            <td class="text-right">
                <h4>Kasir</h4>
                <br>
                <span style="margin-top:-.2rem;">{{ strtoupper($hutang->operator) }}</span>
            </td>
        </tr>
    </table>
  {{--   <table width="100%">
        <tr>
            <td>Terimakasih telah berbelanja dan sampai jumpa</td>
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