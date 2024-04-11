<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembelian - {{$kode}}</title>

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
                <b>NO INVOICE : </b>
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
                    <td colspan="6" class="text-right"><b>SubTotal</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($pembelian->jumlah) }}</b></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right"><b>Biaya Bongkar</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($pembelian->biayabongkar) }}</b></td>
                </tr>

                <tr>
                    <td colspan="6" class="text-right"><b>Grand Total Bayar</b></td>
                    <td class="text-right"><b>{{ $pembelian->biayabongkar !== NULL ? $helpers->format_uang($pembelian->jumlah + $pembelian->biayabongkar) : $helpers->format_uang($pembelian->jumlah) }}</b></td>
                </tr>
            @endif
            
            
            @if($pembelian->visa === 'HUTANG')
                <tr>
                    <td colspan="6" class="text-right"><b>
                        {{$pembelian->visa === "DP AWAL" ? "DP Awal" : "Total DP"}}
                    </b></td>
                    <td class="text-right"><b>{{ $pembelian->po === 'True' ? $helpers->format_uang($pembelian->bayar) : $helpers->format_uang($pembelian->diterima) }}</b></td>
                </tr>
                @if($pembelian->po === "True")
                    @if($pembelian->lunas == "True")
                        <tr>
                            <td colspan="6" class="text-right"><b>Sisa DP</b></td>
                            <td class="text-right"><b>{{ $helpers->format_uang($pembelian->hutang) }}</b></td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="6" class="text-right"><b>Masuk Hutang</b></td>
                            <td class="text-right"><b>{{ $helpers->format_uang($pembelian->hutang) }}</b></td>
                        </tr>
                    @endif
                @else
                    <tr>
                        <td colspan="6" class="text-right"><b>Hutang</b></td>
                        <td class="text-right"><b>{{ $helpers->format_uang($pembelian->hutang) }}</b></td>
                    </tr>
                @endif
            @else
            
            @if($pembelian->po === 'True')
            <tr>
                <td colspan="6" class="text-right"><b>DP Awal</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->jumlah) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diterima</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->diterima) }}</b></td>
            </tr>
            @if($pembelian->lunas === "True")
                <tr>
                    <td colspan="6" class="text-right"><b>Biaya Bongkar</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($pembelian->biayabongkar) }}</b></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right"><b>Grand Total Bayar</b></td>
                    <td class="text-right"><b>{{ $pembelian->biayabongkar !== NULL ? $helpers->format_uang($pembelian->bayar + $pembelian->biayabongkar) : $helpers->format_uang($pembelian->bayar) }}</b></td>
                </tr>
            @endif
            <tr>
                <td colspan="6" class="text-right">
                    @if($pembelian->visa === "LUNAS")
                    <b>Kembali</b> 
                    @else
                    <b>Sisa DP</b>
                    @endif
                </td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->bayar - $pembelian->diterima) }}</b></td>
            </tr>
            @else
            <tr>
                <td colspan="6" class="text-right"><b>Dibayar</b></td>
                <td class="text-right"><b>{{ $pembelian->biayabongkar !== NULL ? $helpers->format_uang($pembelian->bayar + $pembelian->biayabongkar) : $helpers->format_uang($pembelian->bayar) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Kembali</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($pembelian->diterima - $pembelian->jumlah) }}</b></td>
            </tr>
            @endif
            @endif
        </tfoot>
    </table>

  {{--   <table width="100%">
        <tr>
            <td><b>Terimakasih telah berbelanja dan sampai jumpa</b></td>
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
