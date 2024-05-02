<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- <meta http-equiv="X-UA-Compatible" content="ie=edge"> --}}
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{$penjualan->visa !== "HUTANG" ? 'Nota Penjualan' : 'Nota Piutang Penjualan'}} -  {{$kode}}</title>
    <style>
        * {
            /* font-family: 'Courier New', Courier, monospace; */
            font-family: 'Draft Condensed', sans-serif;
            margin-top: .1rem;
            letter-spacing: 1.5px;
            font-size: 12px;
        }
        
        table.data td,
        table.data th {
            border: 1px solid #ccc;
            padding: 3px;
            font-size: 13px;
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
    </style>
</head>
<body>
    <h4 style="margin-top: 2rem;">INVOICE</h4>
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

                {{ $toko['name'] }}                 
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
                {{$helpers->format_tanggal($penjualan['tanggal'])}}
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

    <table class="data" width="100%" style="margin-top:-1.5rem;">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Kas</th>
                <th>Barang / Harga Satuan</th>
                <th>Pelanggan</th>
                <th>Saldo Piutang</th>
                <th>Jumlah</th>
                <th>Biaya Kirim</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barangs as $key => $item)
            <tr>
                <td class="text-center">{{ $key+1 }}</td>
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
        <tfoot>
            <tr>
                <td colspan="7" class="text-right">Total</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->jumlah) }}</td>
            </tr>
            @if($penjualan->lunas === "True")
            <tr>
                <td colspan="7" class="text-right">Total Bayar</td>
                <td class="text-right">{{ $item->diskon ? $helpers->format_uang($item->diskon_rupiah) : $helpers->format_uang($penjualan->bayar) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="7" class="text-right">Dibayar</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->bayar) }}</td>
            </tr>
            @endif
            @if($penjualan->dikirim !== NULL)
            <tr>
                <td colspan="7" class="text-right">Dikirim</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->dikirim) }}</td>
            </tr>
            @endif
            @if($penjualan->lunas === "True")
            <tr>
                <td colspan="7" class="text-right">Kembali</td>
                <td class="text-right">{{ $penjualan->kembali ? $helpers->format_uang($penjualan->kembali) : $helpers->format_uang($penjualan->bayar - $penjualan->jumlah) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="7" class="text-right">Masuk Piutang</td>
                <td class="text-right">{{ $helpers->format_uang($penjualan->piutang) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    <table width="100%" style="margin-top: .5rem;">
        <tr>
            <td class="text-right">
                <h4>Kasir</h4>
                <br>
                <span>{{ strtoupper($penjualan->operator) }}</span>
            </td>
        </tr>
    </table>

    <p style="text-align: center; font-size:10px; margin-top: -.7rem;">
        <p class="text-center">Semoga Lancar</p>
        <p class="text-center">&</p>
        <p class="text-center">Tetap Menjadi Langganan</p>
        <p class="text-center">*** TERIMA KASIH ****</p>
    </p>
</body>
</html>