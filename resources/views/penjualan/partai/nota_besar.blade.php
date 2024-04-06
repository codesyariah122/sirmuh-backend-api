<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$penjualan->visa !== "HUTANG" ? 'Nota Penjualan' : 'Nota Piutang Penjualan'}} -  {{$kode}}</title>

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
                <b>Kepada</b>
            </td>
            <td rowspan="4" width="40%" style="vertical-align: top;">
                <b>{{ $toko['name'] }}</b> <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="80">
                <br>
                <address>
                    {{ $toko['address'] }}
                </address>
                <br>
                {{$helpers->format_tanggal($penjualan['tanggal'])}}
                <br>
                <b>NO INVOICE : </b>
                <b>{{$penjualan->kode}}</b>
            </td>
        </tr>
        <tr>
            <td>
                {{$penjualan->pelanggan_nama}}({{$penjualan->pelanggan}})
            </td>
        </tr>
        <tr>
            <td>Kasir:  {{ strtoupper($penjualan->operator) }}</td>
        </tr>
         <tr>
            <td>
                Jenis : {{$penjualan->jenis}}
            </td>
        </tr>
    </table>

    <table class="data" width="100%" style="margin-top:15px;">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
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
                <td class="text-center">{{ $item->kode }}</td>
                <td class="text-center">{{ $item->kode_kas }}</td>
                <td class="text-center">{{$item->barang_nama}} / {{ $helpers->format_uang($item->harga) }}</td>
                <td class="text-right">{{ $item->qty." ".$item->satuan }}</td>
                <td class="text-right"> {{$helpers->format_uang($penjualan->biayakirim)}} </td>
                <td class="text-right">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><b>Total</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($penjualan->jumlah) }}</b></td>
            </tr>
            @if($penjualan->lunas === "True")
            <tr>
                <td colspan="6" class="text-right"><b>Total Bayar</b></td>
                <td class="text-right"><b>{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($penjualan->bayar) }}</b></td>
            </tr>
            @endif
            @if($penjualan->dikirim !== NULL)
            <tr>
                <td colspan="6" class="text-right"><b>Dikirim</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($penjualan->dikirim) }}</b></td>
            </tr>
            @endif
            @if($penjualan->lunas === "True")
            <tr>
                <td colspan="6" class="text-right"><b>Kembali</b></td>
                <td class="text-right"><b>{{ $penjualan->kembali ? $helpers->format_uang($penjualan->kembali) : $helpers->format_uang($penjualan->bayar - $penjualan->jumlah) }}</b></td>
            </tr>
            @else
            <tr>
                <td colspan="6" class="text-right"><b>Masuk Piutang</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($penjualan->piutang) }}</b></td>
            </tr>
            @endif
        </tfoot>
    </table>

      <table width="100%" style="margin-top: 2rem;">
        <tr>
            <td class="text-right">
                <h2>Kasir</h2>
                <br>
                <br>
                <b>{{ strtoupper($penjualan->operator) }}</b>
            </td>
        </tr>
    </table>

    <p style="text-align: center; margin-top: 20px;font-size:10px;">
        <p class="text-center">Semoga Lancar</p>
        <p class="text-center">&</p>
        <p class="text-center">Tetap Menjadi Langganan</p>
        <p class="text-center">*** TERIMA KASIH ****</p>
    </p>
</body>
</html>