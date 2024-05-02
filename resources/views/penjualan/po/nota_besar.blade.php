<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Penjualan - {{$kode}}</title>

    <style>
        * {
            /* font-family: 'Courier New', Courier, monospace; */
            font-family: 'Draft Condensed', sans-serif;
            margin-top: .1rem;
            letter-spacing: 1.5px;
            font-size: 12px;
        }
        
        table.data td,
        table.data th {
            border: 1px solid #ccc;
            padding: 3px;
            font-size: 13px;
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
                {{$penjualan->pelanggan_nama}}({{$penjualan->pelanggan}})
            </td>
        </tr>

        <tr>
            <td>
                <br>
                {{$helpers->format_tanggal($penjualan['tanggal'])}}
                <br>
                NO INVOICE : 
                {{$penjualan->kode}}
            </td>
        </tr>
        <tr>
            <td>
                Jenis : {{$penjualan->jenis}}
            </td>
        </tr>
    </table>

    <table class="data" width="100%"  style="margin-top: -1.5rem;">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Biaya Kirim</th>
                <th>Pembayaran</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        @if($penjualan->po == "False")
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
                <td>{{ $item->kode_barang }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->harga) }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->qty)."".$item->satuan }}</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->biayakirim) }}</td>
                <td class="text-right">{{ $penjualan->po === 'True' ? 'DP Awal' : $item->visa }}</td>
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
                <td class="text-right">{{ $helpers->format_uang($item->harga) }}</td>
                <td class="text-right">{{ $orders ." ".$item->satuan }}</td>
                <td class="text-right"> {{$helpers->format_uang($penjualan->biayakirim)}} </td>
                <td class="text-right">{{ $item->visa }}</td>
                <td class="text-right">{{ $helpers->format_uang($orders * $item->harga) }}</td>
            </tr>
            @endforeach
            @endif
            <tfoot>
                @if($penjualan->po === 'False')
                <tr>
                    <td colspan="7" class="text-right"><b>Total Bayar</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->jumlah) }}</b></td>
                </tr>
                @endif
                
                
                @if($penjualan->visa === 'PIUTANG')
                <tr>
                    <td colspan="7" class="text-right"><b>
                        {{$penjualan->visa === "DP AWAL" ? "DP Awal" : "Total DP"}}
                    </b></td>
                    <td class="text-right"><b>{{ $penjualan->po === 'True' ? $helpers->format_uang($penjualan->bayar) : $helpers->format_uang($penjualan->dikirim) }}</b></td>
                </tr>
                @if($penjualan->po === "True")
                @if($penjualan->lunas == "True")
                <tr>
                    <td colspan="7" class="text-right"><b>Sisa DP</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->piutang) }}</b></td>
                </tr>
                @else
                <tr>
                    <td colspan="7" class="text-right"><b>Masuk PIUTANG</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->piutang) }}</b></td>
                </tr>
                @endif
                @else
                <tr>
                    <td colspan="7" class="text-right"><b>PIUTANG</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->piutang) }}</b></td>
                </tr>
                @endif
                @else
                
                @if($penjualan->po === 'True')
                <tr>
                    <td colspan="7" class="text-right"><b>DP Awal</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->jumlah) }}</b></td>
                </tr>
                <tr>
                    <td colspan="7" class="text-right"><b>dikirim</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->dikirim) }}</b></td>
                </tr>
                @if($penjualan->lunas === "True")
                <tr>
                    <td colspan="7" class="text-right"><b>Dibayar</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->bayar) }}</b></td>
                </tr>
                @endif
                <tr>
                    <td colspan="7" class="text-right">
                        @if($penjualan->visa === "LUNAS")
                        <b>Kembali</b> 
                        @else
                        <b>Sisa DP</b>
                        @endif
                    </td>
                    @if($penjualan->visa === "LUNAS")
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->kembali) }}</b></td>
                    @else
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->bayar - $penjualan->dikirim) }}</b></td>
                    @endif
                </tr>
                @else
                <tr>
                    <td colspan="7" class="text-right"><b>dikirim</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->dikirim) }}</b></td>
                </tr>
                <tr>
                    <td colspan="7" class="text-right"><b>Kembali</b></td>
                    <td class="text-right"><b>{{ $helpers->format_uang($penjualan->kembali) }}</b></td>
                </tr>
                @endif
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
