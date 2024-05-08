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
    <h1 style="margin-top: 2rem; font-size: 1.5rem;">INVOICE</h1> 
    
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="font-weight: 800;">Kepada</td>
            <td rowspan="4" width="40%" style="vertical-align: top;">
             <span style="font-weight: 800; font-size: 14px;">{{ $toko['name'] }}</span>  @if($toko['name'] === 'CV Sangkuntala Jaya Sentosa')
             <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="60" />
             @else
             <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="120" />
             @endif
             <br>

             <address>
                {{ $toko['address'] }}
            </address>
            <br>
            Tanggal : {{$helpers->format_tanggal_transaksi(date('d-m-y'))}}
            <br>
            NO INVOICE : 
            <b>{{$penjualan->kode}}</b>
        </td>
    </tr>
    <tr>
        <td>
           {{$penjualan->pelanggan_nama}}({{$penjualan->pelanggan}})
           <br>
           <address style="text-transform: capitalize;">
               {{$penjualan->pelanggan_alamat}}
           </address>
       </td>
   </tr>
</table>

<table class="data" width="100%" style="border-collapse: collapse;">
    <thead>
        <tr>
            <th>No</th>
            <th>Kode Kas</th>
            <th>Barang</th>
            <th>Harga</th>
            <th>Jumlah</th>
            <th>Biaya Kirim</th>
            <th>Sub Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($barangs as $key => $item)
        <tr>
            <td class="text-center">{{ $key+1 }}</td>
            {{-- <td class="text-left">
                {{$helpers->format_tanggal_transaksi($penjualan['tanggal'])}}
            </td> --}}
            <td class="text-center">{{ $item->kode_kas }}</td>
            <td class="text-center">{{$item->barang_nama}}</td>
            <td class="text-right">{{ $helpers->format_uang($item->harga) }}</td>
            <td class="text-center">{{ sprintf("%.2f", $item->qty)." ".$item->satuan }}</td>
            @if(count($barangs) > 0)
            <td class="text-right"> {{$helpers->format_uang($penjualan->biayakirim)}} </td>
            @else
            <td class="text-right"> {{$helpers->format_uang($penjualan->biayakirim)}} </td>
            @endif
            <td class="text-right">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($item->subtotal) }}</td>
        </tr>
        @endforeach
        <tr>
            <td class="text-center"></td>
            <td class="text-left" colspan="5">
                <span>PO. NO 450639945</span> <br>
                <span>Date: {{$helpers->format_tanggal_transaksi($penjualan['tanggal'])}}</span>
            </td>
            <td class="text-center"></td>
        </tr>
    </tbody>
</table>

<table class="data" width="100%" style="margin-top:-.1rem; border-collapse: collapse;">
    <tfoot>
        <tr>
            <!-- Bagian kiri -->
            <td style="text-align: left; padding: 35px; border: none;" colspan="4">
                <h3>Pembayaran</h3>
                <ul>
                    <li>Nama : {{$penjualan['nama_kas']}} </li>
                    <li>No. Rek : {{$helpers->generate_norek($penjualan['no_rek'])}} </li>
                </ul>
            </td>

            <!-- Bagian kanan -->
            <td style="border: none;" colspan="6">
                <!-- Konten bagian kanan -->
                <table style="width: 51%; border: none; margin-left:16.5rem; margin-top:-2.5rem; border-collapse: collapse;">
                    <tr>
                        <td style="border: none;" colspan="8" class="text-right">Subtotal</td>
                        <td class="text-right" style="height: 20px; border-top: 0;">{{ $helpers->format_uang($penjualan->jumlah) }}</td>
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