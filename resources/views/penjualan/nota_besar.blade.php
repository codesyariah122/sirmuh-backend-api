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
    <table width="100%" style="border-collapse: collapse; margin-top: .5rem;">
        <tr>
            <td style="vertical-align: top;">
                Kepada
            </td>
            <td rowspan="6" width="40%" style="vertical-align: top;">
               <span style="font-weight: 800; font-size: 14px;">{{ $toko['name'] }}</span>  @if($toko['name'] === 'CV Sangkuntala Jaya Sentosa')
               <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="60" />
               @else
               <img src="{{ public_path('storage/tokos/' . $toko['logo']) }}" alt="{{$toko['logo']}}" width="120" />
               @endif
               <br>
               <span>{{ $toko['name'] }} </span>                
               <br>
               <address>
                {{ $toko['address'] }}
            </address>
            <br>
            @php
            use Carbon\Carbon;
            $currentDate = Carbon::now()->format('d-m-Y');
            @endphp
            Tanggal : {{$helpers->format_tanggal_transaksi($currentDate)}}
            <br>
            NO INVOICE : 
            <b>{{$penjualan->kode}}</b>
        </td>
    </tr>

    <tr>
        <td>
            {{ucfirst($penjualan->nama_pelanggan)}}({{$penjualan->pelanggan}})
            <br>
            <address>
                {{$penjualan->pelanggan_alamat !== NULL ? $penjualan->pelanggan_alamat : 'Belum ada alamat'}}
            </address>
        </td>
    </tr>
</table>

<table class="data" width="100%" style="margin-top:-.5rem;">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal Transaksi</th>
            <th>Barang / Harga Satuan</th>
            <th>Jumlah</th>
            <th>Saldo Piutang</th>
            <th>Biaya Kirim</th>
            <th>Sub Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($barangs as $key => $item)
        <tr>
            <td class="text-center">{{ $key+1 }}</td>
            <td class="text-center">
                {{$helpers->format_tanggal_transaksi($penjualan['tanggal'])}}
            </td>
            <td class="text-right">{{$item->barang_nama}} / {{ $helpers->format_uang($item->harga) }}</td>
            <td class="text-center">{{ round($item->qty, 2)."".$item->satuan }}</td>
            <td class="text-right">{{$helpers->format_uang($item->saldo_piutang)}}</td>
            @if(count($barangs) > 0)
            <td class="text-right"> {{$helpers->format_uang($penjualan->biayakirim)}} </td>
            @else
            <td class="text-right"> {{$helpers->format_uang($penjualan->biayakirim)}} </td>
            @endif
            <td class="text-right">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($item->subtotal) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="text-right">Total</td>
            <td class="text-right">{{ $helpers->format_uang($penjualan->jumlah) }}</td>
        </tr>
        @if($penjualan->lunas === "True")
        <tr>
            <td colspan="6" class="text-right">Total Bayar</td>
            <td class="text-right">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($penjualan->bayar) }}</td>
        </tr>
        @else
        <tr>
            <td colspan="6" class="text-right">Dibayar</td>
            <td class="text-right">{{ $helpers->format_uang($penjualan->bayar) }}</td>
        </tr>
        @endif
            {{-- @if($penjualan->dikirim !== NULL)
            <tr>
                <td colspan="6" class="text-right">Dikirim</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->dikirim) }}</td>
            </tr>
            @endif --}}
            @if($penjualan->lunas === "True")
            <tr>
                <td colspan="6" class="text-right">Kembali</td>
                <td class="text-right">{{ $penjualan->kembali ? $helpers->format_uang($penjualan->kembali) : $helpers->format_uang($penjualan->bayar - $penjualan->jumlah) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="6" class="text-right">Masuk Piutang</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->piutang) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    <table width="100%" style="margin-top: 1.5rem;">
        <tr>
            <td class="text-right">
                <span style="font-weight: 800;border-top: 2px solid black;width: 10%;height: 12px;">
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