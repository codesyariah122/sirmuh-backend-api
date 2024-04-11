<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$penjualan->visa !== "HUTANG" ? 'Nota Penjualan' : 'Nota Piutang Penjualan'}} -  {{$kode}}</title>

    <?php
    $style = '
    <style>
        * {
            font-family: "consolas", sans-serif;
        }
        p {
            display: block;
            margin: 3px;
            font-size: 10pt;
        }
        table td {
            font-size: 9pt;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }

        @media print {
            @page {
                margin: 0;
                size: 75mm 
                ';
                ?>
                <?php 
                $style .= 
                ! empty($_COOKIE['innerHeight'])
                ? $_COOKIE['innerHeight'] .'mm; }'
                : '}';
                ?>
                <?php
                $style .= '
                html, body {
                    width: 70mm;
                }
                .btn-print {
                    display: none;
                }
            }
        </style>
        ';
        ?>

        {!! $style !!}
    </head>
    <body onload="window.print()">
        <button class="btn-print" style="position: absolute; right: 1rem; top: rem;" onclick="window.print()">Print</button>

        <div class="clear-both" style="clear: both;"></div>
        <p>No: {{ $penjualan->kode }}</p>
        <p>Type: {{ $penjualan->jenis }}</p>
        <p class="text-center">===================================</p>
        <p>
            <img src="{{  Storage::url('tokos/' . $toko['logo']) }}" width="70">
        </p>
        <p>{{ $toko['name'] }}</p>
        <p>{{ $toko['address'] }}</p>
        <br/>
        <p>No : {{ $penjualan->kode }}</p>
        <p>Tgl Transaksi : {{ $penjualan->tanggal }}</p>
        <p>Pelanggan : {{ $penjualan->pelanggan_nama }}({{$penjualan->pelanggan}})</p>
        <p>
            <address>
                {{ $penjualan->alamat_supplier }}
            </address>
        </p>
        <p>Operator : {{ strtoupper($penjualan->operator) }}</p>
        <p>Jenis Pembayaran : {{$penjualan->visa}}</p>
        <p class="text-center">===================================</p>
         <table width="100%" style="border: 0;">
        @foreach ($barangs as $item)
            <tr>
                <td colspan="3">{{ $item->nama_barang }} - ({{$item->kode_barang}}|{{$item->supplier}})</td>
            </tr>
            <tr>
                <td>{{ $item->qty }} x {{ $helpers->format_uang($item->harga_toko) }}</td>
                <td></td>
                <td class="text-right">{{ $helpers->format_uang($item->qty * $item->harga_toko) }}</td>
            </tr>
        @endforeach
    </table>
        <p class="text-center">===================================</p>
        <table width="100%" style="border: 0;">
            <tr>
                <td>Total Harga:</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->jumlah) }}</td>
            </tr>
            <tr>
                <td>Diskon:</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->diskon) }}%</td>
            </tr>
            @if($penjualan->lunas === "True")
            <tr>
                <td>Total Bayar:</td>
                <td class="text-right">{{$item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($penjualan->bayar) }}</td>
            </tr>
            @if($penjualan->dikirim !== NULL)
            <tr>
                <td><b>Dikirim</b></td>
                <td class="text-right"><b>{{ $penjualan->dikirim ? $helpers->format_uang($penjualan->dikirim) : $helpers->format_uang($penjualan->bayar) }}</b></td>
            </tr>
            @endif
            @else
            <tr>
                <td>Diterima:</td>
                <td class="text-right">{{ $penjualan->diterima ? $helpers->format_uang($penjualan->diterima) : $helpers->format_uang($penjualan->bayar)}}</td>
            </tr>
            @endif
            @if($penjualan->lunas !== "True")
            <tr>
                <td>Piutang:</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->piutang) }}</td>
            </tr>
            @else
            <tr>
                <td>Kembali:</td>
                <td class="text-right">{{ $penjualan->kembali ? $helpers->format_uang($penjualan->kembali) : $helpers->format_uang($penjualan->bayar - $penjualan->jumlah) }}</td>
            </tr>
            @endif
        </table>

        <p class="text-center">===================================</p>
        <p class="text-center">Semoga Lancar</p>
        <p class="text-center">&</p>
        <p class="text-center">Tetap Menjadi Langganan</p>
        <p class="text-center">*** TERIMA KASIH ****</p>

        <script>
            let body = document.body;
            let html = document.documentElement;
            let height = Math.max(
                body.scrollHeight, body.offsetHeight,
                html.clientHeight, html.scrollHeight, html.offsetHeight
                );

            document.cookie = "innerHeight=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "innerHeight="+ ((height + 50) * 0.264583);
        </script>
    </body>
    </html>