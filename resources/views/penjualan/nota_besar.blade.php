<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$penjualan->visa !== "HUTANG" ? 'Nota Penjualan' : 'Nota Piutang Penjualan'}} -  {{$kode}}</title>
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
    </style>
</head>
<body>
    <h4>INVOICE</h4>
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top;">
                Kepada
            </td>
            <td rowspan="4" width="40%" style="vertical-align: top;">
                {{ $toko['name'] }} <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="80" />
                <br>
                <address>
                    {{ $toko['address'] }}
                </address>
                <br>
                {{$helpers->format_tanggal($penjualan['tanggal'])}}
                <br>
                NO INVOICE : 
                {{$penjualan->kode}}
            </td>
        </tr>
        <tr>
            <td>
                {{$penjualan->pelanggan_nama}}({{$penjualan->pelanggan}})
            </td>
        </tr>
       <!--  <tr>
            <td>Kasir:  {{ strtoupper($penjualan->operator) }}</td>
        </tr> -->
        <tr>
            <td>
                Jenis : {{$penjualan->jenis}}
            </td>
        </tr>

        <tr>
            <td>
                Status : {{$penjualan->status}}
            </td>
        </tr>
    </table>

    <table class="data" width="100%" style="margin-top:15px;">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Kas</th>
                <th>Barang / Harga Satuan</th>
                <th>Jumlah</th>
                <th>Biaya Kirim</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
                <td class="text-center">{{$item->nama_kas}} ({{ $item->kode_kas }})</td>
                <td class="text-left">{{$item->barang_nama}} / {{ $helpers->format_uang($item->harga) }}</td>
                <td class="text-center">{{ $item->qty." ".$item->satuan }}</td>
                @if(count($barangs) > 0)
                <td class="text-right"> {{$helpers->format_uang($penjualan->biayakirim)}} </td>
                @else
                <td class="text-right"> {{$helpers->format_uang($penjualan->biayakirim)}} </td>
                @endif
                <td class="text-right">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">Total</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->jumlah) }}</td>
            </tr>
            @if($penjualan->lunas === "True")
            <tr>
                <td colspan="5" class="text-right">Total Bayar</td>
                <td class="text-right">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($penjualan->bayar) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="5" class="text-right">Dibayar</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->bayar) }}</td>
            </tr>
            @endif
            @if($penjualan->dikirim !== NULL)
            <tr>
                <td colspan="5" class="text-right">Dikirim</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->dikirim) }}</td>
            </tr>
            @endif
            @if($penjualan->lunas === "True")
            <tr>
                <td colspan="5" class="text-right">Kembali</td>
                <td class="text-right">{{ $penjualan->kembali ? $helpers->format_uang($penjualan->kembali) : $helpers->format_uang($penjualan->bayar - $penjualan->jumlah) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="5" class="text-right">Masuk Piutang</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->piutang) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    <table width="100%" style="margin-top: 1rem;">
        <tr>
            <td class="text-right">
                <h2>Kasir</h2>
                <br>
                {{ strtoupper($penjualan->operator) }}
            </td>
        </tr>
    </table>

    <p style="text-align: center; font-size:10px;">
        <p class="text-center">Semoga Lancar</p>
        <p class="text-center">&</p>
        <p class="text-center">Tetap Menjadi Langganan</p>
        <p class="text-center">*** TERIMA KASIH ****</p>
    </p>
</body>
</html>