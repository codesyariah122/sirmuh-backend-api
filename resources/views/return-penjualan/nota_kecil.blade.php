<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Return Penjualan -  {{$kode}}</title>

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
        <p>Type: Return Pembelian</p>
        <p class="text-center">===================================</p>
        <p>
            <img src="{{  Storage::url('tokos/' . $toko['logo']) }}" width="70">
        </p>
        <p>{{ $toko['name'] }}</p>
        <p>{{ $toko['address'] }}</p>
        <br/>
        <p>No Faktur: {{ $pembelian->no_faktur }}</p>
        <p>Tgl Transaksi : {{ $pembelian->tanggal_pembelian }}</p>
        <p>Supplier : {{$pembelian->nama_supplier}} ({{$pembelian->supplier}})</p>
        <p>Alamat Supplier : {{ $pembelian->alamat_supplier }}</p>
        <p>Operator : {{ strtoupper($pembelian->operator) }}</p>
        <p class="text-center">===================================</p>
         <table width="100%" style="border: 0;">
            <tr>
                <td colspan="3"><b>Item Di Return</b></td>
            </tr>
            <tr>
                <td colspan="3">{{ $pembelian->nama_barang }} - {{$pembelian->kode_barang}}</td>
            </tr>
            <tr>
                <td>{{ round($pembelian->qty) }} x {{ $helpers->format_uang($pembelian->harga_beli) }}</td>
                <td></td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->qty * $pembelian->harga_beli) }}</td>
            </tr>
            <tr>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td colspan="3"></td>

    </table>
        <p class="text-center">===================================</p>
        <table width="100%" style="border: 0;">
            <tr>
                <td>Qty Pembelian:</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->last_qty) }}{{$pembelian->satuan}}</td>
            </tr>
            <tr>
                <td>Subtotal Pembelian:</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->last_qty * $pembelian->harga_beli) }}</td>
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