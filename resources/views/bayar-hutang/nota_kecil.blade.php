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
       dd($hutang); die;
       @endphp --}}
        <div class="clear-both" style="clear: both;"></div>
        <p>No: {{ $hutang->kode }}</p>
        <p>Type: {{$hutang->po === 'True' ? "Pembelian P.O" : "Pembelian Langsung"}}</p>
        <p class="text-center">===================================</p>
        <p>
            <img src="{{  Storage::url('tokos/' . $toko['logo']) }}" width="70">
        </p>
        <p>{{ $toko['name'] }}</p>
        <p>{{ $toko['address'] }}</p>
        <br/>
        <p>No : {{ $hutang->kode }}</p>
        <p>Tgl Transaksi : {{ $hutang->tanggal }}</p>
        <p>Supplier : {{ $hutang->nama_supplier }}</p>
        <p>Kode Supplier : {{$hutang->supplier}}</p>
        <p>Alamat Supplier : {{ $hutang->alamat_supplier }}</p>
        <p>Operator : {{ strtoupper($hutang->operator) }}</p>
        <p class="text-center">===================================</p>
        <table width="100%" style="border: 0;">
            <tr>
                <td colspan="3">{{ $hutang->nama_barang }} - {{$hutang->kode_barang}}</td>
            </tr>
            <tr>
                <td>{{ round($hutang->qty) }} x {{ $helpers->format_uang($hutang->harga_beli) }}</td>
                <td></td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian) }}</td>
            </tr>
        </table>
        <p class="text-center">-----------------------------------</p>
        <table width="100%" style="border: 0;">
            <tr>
                <td>Total Bayar:</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian) }}</td>
            </tr>
            <tr>
                <td>Total Item:</td>
                <td class="text-right">
                     {{round($hutang->qty)}} {{$hutang->satuan}}
                 </td>
            </tr>
            <tr>
                <td>Diterima:</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah_pembelian - $hutang->jumlah) }}</td>
            </tr>
            <tr>
                <td>Diskon:</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->diskon) }}</td>
            </tr>
            <tr>
                <td>Hutang:</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jumlah) }}</td>
            </tr>
            @foreach($angsurans as $angsuran)
            <tr>
                <td>Angsuran ke {{$angsuran->angsuran_ke}} {{$angsuran->angsuran_ke == 1 ? '(DP Awal)' : ''}} :</td>
                <td class="text-right">{{ $helpers->format_uang($angsuran->bayar_angsuran) }} </td>
            </tr>
            @endforeach
            @if($hutang->lunas === 'False' || $hutang->lunas === '0')
            <tr>
                <td>Sisa Hutang:</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->jml_hutang) }}</td>
            </tr>
            @else
            <tr>
                <td>Kembali:</td>
                <td class="text-right">{{ $helpers->format_uang($hutang->bayar - $hutang->hutang) }}</td>
            </tr>
            @endif

            @if(count($angsurans) > 0)
            <tr>
                <td>Status:</td>
                <td class="text-right">{{ intval($hutang->jml_hutang) === 0 ? "Lunas" : "Angsuran" }}</td>
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