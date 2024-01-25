<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Penjualan - {{$kode}}</title>

    <style>
        table td {
            /* font-family: Arial, Helvetica, sans-serif; */
            font-size: 14px;
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
    <table width="100%">
        <tr>
            <td rowspan="4" width="60%">
                <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="100">
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
                No
            </td>
            <td>: {{ $penjualan->kode }}</td>
        </tr>
        <tr>
            <td>Type</td>
            <td>: Penjualan Toko</td>
        <tr>
            <td>
                Pelanggan
            </td>
            <td>: {{ $penjualan->pelanggan_nama }}
                @if($penjualan->pelanggan_alamat)
               <br>
               <address>
                {{ $penjualan->pelanggan_alamat ?? '-' }}
                </address>
                @endif
                <br>
            </td>
        </tr>
    </table>

    <table class="data" width="100%" style="margin-top:15px;">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Kode Kas</th>
                <th>Barang</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Diskon</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
                <td>{{ $item->kode }}</td>
                <td>{{ $item->kode_kas }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td class="text-right">{{ $helpers->format_uang($item->hpp) }}</td>
                <td class="text-right">{{ round($item->qty)." ".$item->satuan }}</td>
                <td class="text-right">{{ $item->diskon }}%</td>
                <td class="text-right">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><b>Total Harga</b></td>
                <td class="text-right"><b>{{ $helpers->format_uang($penjualan->jumlah) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diskon</b></td>
                <td class="text-right"><b>{{  $helpers->format_uang($penjualan->diskon) }}%</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Total Bayar</b></td>
                <td class="text-right"><b>{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($penjualan->bayar) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Diterima</b></td>
                <td class="text-right"><b>{{ $penjualan->diterima ? $helpers->format_uang($penjualan->diterima) : $helpers->format_uang($penjualan->bayar) }}</b></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><b>Kembali</b></td>
                <td class="text-right"><b>{{ $penjualan->kembali ? $helpers->format_uang($penjualan->kembali) : $helpers->format_uang($penjualan->bayar - $penjualan->jumlah) }}</b></td>
            </tr>
        </tfoot>
    </table>

    <p style="text-align: center; margin-top: 20px;">
        <p class="text-center">Semoga Lancar</p>
        <p class="text-center">&</p>
        <p class="text-center">Tetap Menjadi Langganan</p>
        <p class="text-center">*** TERIMA KASIH ****</p>
    </p>

    <p style="text-align: right;">
        <b>Kasir</b>
        <br>
        <br>
        {{ strtoupper($penjualan->operator) }}
    </p>
</body>
</html>