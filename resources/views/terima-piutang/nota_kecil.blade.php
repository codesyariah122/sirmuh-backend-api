<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Pembayaran Hutang -  {{$kode}}</title>

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
       {{-- @php
       dd($piutang); die;
       @endphp --}}
        <div class="clear-both" style="clear: both;"></div>
        <p>No: {{ $piutang->kode }}</p>
        <p>Type: {{$piutang->po === 'True' ? "Penjualan P.O" : "Penjualan Langsung"}}</p>
        <p class="text-center">===================================</p>
        <p>
            <img src="{{  Storage::url('tokos/' . $toko['logo']) }}" width="70">
        </p>
        <p>{{ $toko['name'] }}</p>
        <p>{{ $toko['address'] }}</p>
        <br/>
        <p>No : {{ $piutang->kode }}</p>
        <p>Tgl Transaksi : {{ $piutang->tanggal }}</p>
        <p>Supplier : {{ $piutang->nama_supplier }}</p>
        <p>Kode Supplier : {{$piutang->supplier}}</p>
        <p>Alamat Supplier : {{ $piutang->alamat_supplier }}</p>
        <p>Operator : {{ strtoupper($piutang->operator) }}</p>
        {{-- @php
        var_dump($piutang->jumlah_hutang); 
        var_dump($piutang->jumlah);
        die;
        @endphp --}}
        <p class="text-center">===================================</p>
        <table width="100%" style="border: 0;">
            <tr>
                <td colspan="3">{{ $piutang->nama_barang }} - {{$piutang->kode_barang}}</td>
            </tr>
            <tr>
                <td>{{ round($piutang->qty) }} x {{ $helpers->format_uang($piutang->harga) }}</td>
                <td></td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan) }}</td>
            </tr>
        </table>
        <p class="text-center">-----------------------------------</p>
        <table width="100%" style="border: 0;">
            <tr>
                <td>Total Bayar:</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_pembelian) }}</td>
            </tr>
            <tr>
                <td>Total Item:</td>
                <td class="text-right">
                     {{round($piutang->qty)}} {{$piutang->satuan}}
                 </td>
            </tr>
            <tr>
                <td>Diterima:</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah_penjualan - $piutang->jumlah_piutang) }}</td>
            </tr>
            <tr>
                <td>Diskon:</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->diskon) }}</td>
            </tr>
            <tr>
                <td>Piutang:</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jumlah) }}</td>
            </tr>
            @foreach($angsurans as $angsuran)
            <tr>
                <td>Angsuran ke {{$angsuran->angsuran_ke}} {{$angsuran->angsuran_ke == 1 ? '(DP Awal)' : ''}} :</td>
                <td class="text-right">{{ $helpers->format_uang($angsuran->bayar_angsuran) }} </td>
            </tr>
            @endforeach
            <tr>
                <td>Sisa Piutang:</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->jml_hutang) }}</td>
            </tr>
            <tr>
                <td>Kembali:</td>
                <td class="text-right">{{ $helpers->format_uang($piutang->bayar - $piutang->hutang) }}</td>
            </tr>

            @if(count($angsurans) > 0)
            <tr>
                <td>Status:</td>
                <td class="text-right">{{ intval($piutang->jml_hutang) === 0 ? "Lunas" : "Angsuran" }}</td>
            </tr>
            @else
            <tr>
                <td>Status:</td>
                <td class="text-right">Hutang</td>
            </tr>
            @endif
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