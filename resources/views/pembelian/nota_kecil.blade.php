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
        <p>No Transaksi: {{ $pembelian->kode }}</p>
        <p>Tgl Transaksi : {{ $pembelian->tanggal }}</p>
       {{--  <p>Supplier : {{ $pembelian->nama_supplier }}</p>
        <p>Kode Supplier : {{$pembelian->supplier}}</p>
        <p>Alamat Supplier : {{ $pembelian->alamat_supplier }}</p> --}}
        <p>Operator : {{ strtoupper($pembelian->operator) }}</p>
        @if($pembelian->visa === 'HUTANG')
        <p>Pembayaran : {{$pembelian->visa}}</p>
        @endif
        <p class="text-center">===================================</p>
         <table width="100%" style="border: 0;">
        @if($pembelian->po == "False")
            @foreach ($barangs as $item)
                <tr>
                    <td colspan="3">({{$pembelian->nama_supplier}}/{{$pembelian->supplier}})</td>
                </tr>
                <tr>
                    <td colspan="3">{{ $item->nama_barang }} - {{$item->kode_barang}}</td>
                </tr>
                <tr>
                    <td>{{ round($item->qty) }} x {{ $helpers->format_uang($item->harga_beli) }}</td>
                    <td></td>
                    <td class="text-right">{{ $helpers->format_uang($item->qty * $item->harga_beli) }}</td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                </tr>
            @endforeach
        @else
            @foreach ($barangs as $item)
                <tr>
                    <td colspan="3">({{$pembelian->nama_supplier}}/{{$pembelian->supplier}})</td>
                </tr>
                <tr>
                    <td colspan="3">{{ $item->nama_barang }} - {{$item->kode_barang}}</td>
                </tr>
                <tr>
                    <td>{{ round($orders) }} x {{ $helpers->format_uang($item->harga_beli) }}</td>
                    <td></td>
                    <td class="text-right">{{ $helpers->format_uang($orders * $item->harga_beli) }}</td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                </tr>
            @endforeach
        @endif
    </table>
        <p class="text-center">===================================</p>
        <table width="100%" style="border: 0;">
            <tr>
                <td>Diskon:</td>
                <td class="text-right">{{ $helpers->format_uang($pembelian->diskon) }}</td>
            </tr>
           <!--  @if($pembelian->po == 'False')
                <tr>
                    <td>Total Pembelian : </td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->jumlah) }}</td>
                </tr>
                <tr>
                    <td>Dibayar:</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->bayar) }}</td>
                </tr>
            @endif -->
            {{-- @if(count($barangs) > 0)
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
            @endif --}}
        
            @if($pembelian->visa === 'HUTANG')
                @if($pembelian->po == 'False')
                    <tr>
                        <td>Angsuran Awal:</td>
                        <td class="text-right">{{ $helpers->format_uang($pembelian->bayar) }}</td>
                    </tr>
                @else
                    <tr>
                        <td>
                            {{$pembelian->visa === "DP AWAL" ? "DP Awal" : "Total DP"}}
                        </td>
                        <td class="text-right">{{ $helpers->format_uang($pembelian->bayar) }}</td>
                    </tr>
                @endif
            @endif
            @if($pembelian->visa === 'HUTANG')
                @if($pembelian->po == 'False')
                <tr>
                    <td>Hutang:</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->hutang) }}</td>
                </tr>
                @else
                    @if($pembelian->visa === 'HUTANG')
                        <tr>
                            <td>Masuk Hutang:</td>
                            <td class="text-right">{{ $helpers->format_uang($pembelian->hutang) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>Sisa DP:</td>
                            <td class="text-right">{{ $helpers->format_uang($pembelian->hutang) }}</td>
                        </tr>
                    @endif
                @endif
            @else
            @if($pembelian->po == 'True')
                <!-- @if($pembelian->lunas == "True")
                    <tr>
                        <td>Dibayar:</td>
                        <td class="text-right">{{ $helpers->format_uang($pembelian->bayar) }}</td>
                    </tr>
                @endif -->
                    <tr>
                        <td>Dp Awal:</td>
                        <td class="text-right">{{ $helpers->format_uang($pembelian->jumlah) }}</td>
                    </tr>
                    <tr>
                        <td>Dibayar:</td>
                        <td class="text-right">{{ $helpers->format_uang($pembelian->bayar) }}</td>
                    </tr>
                    <tr>
                        <td>Biaya Bongkar:</td>
                        <td class="text-right">{{ $helpers->format_uang($pembelian->biayabongkar) }}</td>
                    </tr>

                    <tr>
                        <td>Grand Total:</td>
                        <td class="text-right">{{ $helpers->format_uang($pembelian->bayar + $pembelian->biayabongkar) }}</td>
                    </tr>
                    <tr>
                        <td>Sisa DP:</td>
                        <td class="text-right">{{ $helpers->format_uang($pembelian->bayar - $pembelian->diterima) }}</td>
                    </tr>
                @else
                <tr>
                    <td>Dibayar:</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->bayar) }}</td>
                </tr>
                <tr>
                    <td>Kembali:</td>
                    <td class="text-right">{{ $helpers->format_uang($pembelian->diterima - $pembelian->jumlah) }}</td>
                </tr>
                @endif
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