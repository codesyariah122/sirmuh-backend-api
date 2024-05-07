<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    {{-- <meta http-equiv="X-UA-Compatible" content="ie=edge"> --}} 
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
    <title>{{$penjualan->visa !== "HUTANG" ? 'Nota Penjualan' : 'Nota Piutang Penjualan'}} - {{$kode}}</title> 
    <style> 
        * { 
            font-family: 'Draft Condensed', sans-serif; margin-top: .1rem; 
            letter-spacing: 1.5px; 
            font-size: 12px; 
        }
        table.data th {
            background: rgb(239, 239, 240);
        }
        table.data td,
        table.data th {
            border: 1px solid #ccc;
            padding: 3px;
            font-size: 11px;
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
        table tfoot ul li {
            list-style: none;
            margin-left: -3rem;
        }
    </style>
</head> 
<body> 
    <h4 style="margin-top: .5rem;">INVOICE</h4> 
    <table width="100%" style="border-collapse: collapse; margin-top: -.5rem;">
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
                <span style="font-weight: 800; font-size: 14px;">{{ $toko['name'] }}</span>              
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
                {{$helpers->format_tanggal(date('d-m-Y'))}}
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

    <table class="data" width="100%" style="margin-top:-2.5rem;">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Transaksi</th>
                <th>Kode Kas</th>
                <th>Barang / Harga Satuan</th>
                <th>Pelanggan</th>
                <th>Saldo Piutang</th>
                <th>Jumlah</th>
                <th>Biaya Kirim</th>
                <th>Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
                <td class="text-left">
                    {{$helpers->format_tanggal_transaksi($penjualan['tanggal'])}}
                </td>
                <td class="text-center">{{$item->nama_kas}} ({{ $item->kode_kas }})</td>
                <td class="text-left">{{$item->barang_nama}} / {{ $helpers->format_uang($item->harga) }}</td>
                <td class="text-center">{{$item->pelanggan_nama}}</td>
                <td class="text-right">{{$helpers->format_uang($item->saldo_piutang)}}</td>
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
        <tfoot style="width: 150px;">
            <tr>
                <!-- Bagian kiri -->
                <td style="text-align: left; padding: 10px; border: none;" colspan="5">
                    <h3>Pembayaran</h3>
                    <ul>
                        <li>Nama : {{$toko['name']}} </li>
                        <li>No. Rek : {{$helpers->generate_norek($penjualan['no_rek'])}} </li>
                    </ul>
                </td>
                
                <!-- Bagian kanan -->
                <td style="border: none;" colspan="8">
                    <!-- Konten bagian kanan -->
                    <table style="width: 70%; border: none; float: right; margin-top:-.3rem;">
                        <tr>
                            <td style="border: none;" colspan="8" class="text-right">Subtotal</td>
                            <td class="text-right" style="margin-top: -1rem; height: 20px;">{{ $helpers->format_uang($penjualan->jumlah) }}</td>
                        </tr>
                        @if($penjualan->lunas === "True")
                        <tr>
                            <td style="border: none;" colspan="8" class="text-right">Total</td>
                            <td class="text-right" style="height: 20px;">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($penjualan->bayar) }}</td>
                        </tr>
                        @else
                        <tr>
                            <td style="border: none;" colspan="8" class="text-right">Dibayar</td>
                            <td class="text-right" style="height: 20px;">{{ $helpers->format_uang($penjualan->bayar) }}</td>
                        </tr>
                        @endif
                        @if($penjualan->dikirim !== NULL)
                        <tr>
                            <td style="border: none;" colspan="8" class="text-right">Dikirim</td>
                            <td class="text-right" style="height: 20px;">{{ $helpers->format_uang($penjualan->dikirim) }}</td>
                        </tr>
                        @endif
                        @if($penjualan->lunas === "True")
                        <tr>
                            <td style="border: none;" colspan="8" class="text-right">Kembali</td>
                            <td class="text-right" style="height: 20px;">{{ $penjualan->kembali ? $helpers->format_uang($penjualan->kembali) : $helpers->format_uang($penjualan->bayar - $penjualan->jumlah) }}</td>
                        </tr>
                        @else
                        <tr>
                            <td style="border: none;" colspan="8" class="text-right">Masuk Piutang</td>
                            <td class="text-right" style="height: 20px;">{{ $helpers->format_uang($penjualan->piutang) }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </tfoot>
    </table>

    <table width="100%" style="margin-top: 3rem;">
        <tr>
            <td class="text-right">
                <span style="font-weight: 800;border-top: 2px solid black;width: 10%;">
                    <br>
                    {{ strtoupper($penjualan->operator) }}
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <span style="margin-top: -3rem; font-weight: 800;">TERIMA KASIH ATAS PEMBELIANNYA</span>
            </td>
        </tr>
    </table>

</body> 
</html>