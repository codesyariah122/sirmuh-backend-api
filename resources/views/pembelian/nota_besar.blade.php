<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembelian - {{$kode}}</title>
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
                NO INVOICE :
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
            <td>Type: {{$pembelian->po == 'True' ? 'Pembelian P.O' : 'Pembelian Langsung'}}</td>
        </tr>
    </table>

    <br/>
    <table class="data" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Pembayaran</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        @if($pembelian->po == "False")
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
                <td>{{ $item->kode_barang }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->harga_beli) }}</td>
                <td class="text-right">{{ $item->qty." ".$item->satuan }}</td>
                <td class="text-right">{{ $pembelian->po === 'True' ? 'DP Awal' : $item->visa }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
        @else
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
                <td>{{ $item->kode_barang }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->harga_beli) }}</td>
                <td class="text-right">{{ $orders ." ".$item->satuan }}</td>
                <td class="text-right">{{ $item->visa }}</td>
                <td class="text-right">{{ $helpers->format_uang($orders * $item->harga_beli) }}</td>
            </tr>
            @endforeach
            @endif
            <tfoot>
                @if($pembelian->po === 'False')
                <tr>
                    <td colspan="6" class="text-right">SubTotal</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->jumlah) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right">Biaya Bongkar</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->biayabongkar) }}</td>
                </tr>

                <tr>
                    <td colspan="6" class="text-right">Grand Total Bayar</td>
                    <td class="text-right">{{ $pembelian->biayabongkar !== NULL ? $helpers->format_uang($pembelian->jumlah + $pembelian->biayabongkar) : $helpers->format_uang($pembelian->jumlah) }}</td>
                </tr>
                @endif


                @if($pembelian->visa === 'HUTANG')
                <tr>
                    <td colspan="6" class="text-right">
                        {{$pembelian->visa === "DP AWAL" ? "DP Awal" : "Total DP"}}
                    </td>
                    <td class="text-right">{{ $pembelian->po === 'True' ? $helpers->format_uang($pembelian->bayar) : $helpers->format_uang($pembelian->diterima) }}</td>
                </tr>
                @if($pembelian->po === "True")
                @if($pembelian->lunas == "True")
                <tr>
                    <td colspan="6" class="text-right">Sisa DP</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->hutang) }}</td>
                </tr>
                @else
                <tr>
                    <td colspan="6" class="text-right">Masuk Hutang</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->hutang) }}</td>
                </tr>
                @endif
                @else
                <tr>
                    <td colspan="6" class="text-right">Hutang</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->hutang) }}</td>
                </tr>
                @endif
                @else

                @if($pembelian->po === 'True')
                <tr>
                    <td colspan="6" class="text-right">DP Awal</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->jumlah) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right">Diterima</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->diterima) }}</td>
                </tr>
                @if($pembelian->lunas === "True")
                <tr>
                    <td colspan="6" class="text-right">Biaya Bongkar</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->biayabongkar) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right">Grand Total Bayar</td>
                    <td class="text-right">{{ $pembelian->biayabongkar !== NULL ? $helpers->format_uang($pembelian->bayar + $pembelian->biayabongkar) : $helpers->format_uang($pembelian->bayar) }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="6" class="text-right">
                        @if($pembelian->visa === "LUNAS")
                        Kembali 
                        @else
                        Sisa DP
                        @endif
                    </td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->bayar - $pembelian->diterima) }}</td>
                </tr>
                @else
                <tr>
                    <td colspan="6" class="text-right">Dibayar</td>
                    <td class="text-right">{{ $pembelian->biayabongkar !== NULL ? $helpers->format_uang($pembelian->bayar + $pembelian->biayabongkar) : $helpers->format_uang($pembelian->bayar) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right">Kembali</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->diterima - $pembelian->jumlah) }}</td>
                </tr>
                @endif
                @endif
            </tfoot>
        </table>

  {{--   <table width="100%">
        <tr>
            <td>Terimakasih telah berbelanja dan sampai jumpa</td>
            <td class="text-center">
                Kasir
                <br>
                <br>
                {{ strtoupper($pembelian->operator) }}
            </td>
        </tr>
    </table> --}}
</body>
</html>
