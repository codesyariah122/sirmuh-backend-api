<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembelian -  {{$kode}}</title>

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
        <p>No: {{ $pembelian->kode }}</p>
        <p>Type: {{$pembelian->po === 'True' ? "Purchase Order" : "Pembelian Langsung"}}</p>
        <p class="text-center">===================================</p>
        <p>
            <img src="{{  Storage::url('tokos/' . $toko['logo']) }}" width="70">
        </p>
        <p>{{ $toko['name'] }}</p>
        <p>{{ $toko['address'] }}</p>
        <br/>
        <p>No : {{ $pembelian->kode }}</p>
        <p>Tgl Transaksi : {{ $pembelian->tanggal }}</p>
        <p>Supplier : {{ $pembelian->nama_supplier }}</p>
        <p>Kode Supplier : {{$pembelian->supplier}}</p>
        <p>
            <address>
                {{ $pembelian->alamat_supplier }}
            </address>
        </p>
        <p>Operator : {{ strtoupper($pembelian->operator) }}</p>
        <p class="text-center">===================================</p>
        <br>
        <p style="text-decoration: underline;">Daftar Barang :</p>
        <br>
         <table width="100%" style="border: 0;">
        @foreach ($barangs as $item)
            <tr>
                <td colspan="3">{{ $item->nama_barang }}</td>
            </tr>
            <tr>
                <td>{{ round($item->qty) }} x {{ $helpers->format_uang($item->jumlah) }}</td>
                <td></td>
                <td class="text-right">{{ $helpers->format_uang($item->qty * $item->harga_beli) }}</td>
            </tr>
        @endforeach
    </table>
        <p class="text-center">-----------------------------------</p>
        <table width="100%" style="border: 0;">
            <tr>
                <td>Total Harga:</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->jumlah) }}</td>
            </tr>
            @if(count($barangs) > 0)
                <tr>
                    <td>Total Item:</td>
                    <td>
                        @foreach ($barangs as $item)
                        <tr>
                            <td>{{ $item->nama_barang }} : </td>
                            <td>{{ round($item->qty)." ".$item->satuan }}</td>
                        </tr>
                        @endforeach
                    </td>
                </tr>
            @else
                <tr>
                    <td>Total Item:</td>
                    <td class="text-right">
                       {{round($pembelian->qty)}}
                    </td>
                </tr>
            @endif
            <tr>
                <td>Diskon:</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->diskon) }}</td>
            </tr>
            <tr>
                <td>Total Bayar:</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->bayar) }}</td>
            </tr>
            <tr>
                <td>Diterima:</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->diterima) }}</td>
            </tr>
            <tr>
                <td>Kembali:</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->diterima - $pembelian->jumlah) }}</td>
            </tr>
        </table>

        <p class="text-center">===================================</p>
        <p class="text-center">-- TERIMA KASIH --</p>

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