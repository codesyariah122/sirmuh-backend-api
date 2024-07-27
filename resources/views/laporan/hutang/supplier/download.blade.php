<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan hutang {{$supplier}}</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td {
            /* font-family: Arial, Helvetica, sans-serif; */
            font-family: 'Courier New', monospace,
            font-size: 13px;
        }
        th, td {
            border: none;
            padding: 8px;
            text-align: left;
            max-width: 130px;
            overflow: hidden;
            white-space: nowrap;
            font-size: 9.5px; 
        }

        th {
            background: rgb(239, 239, 240);
        }

        tbody th, tbody td {
            border: none;
        }

        tfoot {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 11px;
            font-weight: 900;
        }

        tfoot tr, td {
            border: none;
        }
    </style>
</head>
<body>

    <img src="{{ public_path('storage/tokos/' . $perusahaan['logo']) }}" alt="{{$perusahaan['logo']}}" width="100">
    <h5>{{$perusahaan->name}}</h5>
    <address style="margin-top: -23px;font-size:9px;">
        {{$perusahaan->address}}
    </address>


    <h3>Laporan Hutang</h3>
    <hr style="margin-top:-.7rem;">
    <ul style="list-style: none; margin-left:-2rem;margin-top:-.2rem;">
        <li style="font-size: 10px;">Supplier : {{$supplier}}</li>
    </ul>
    <hr style="margin-top:-.7rem;">

    <table>
        <thead>
            <tr>
                <th width="50">Tanggal</th>
                <th width="50">No Faktur</th>
                <th>Kode Hutang</th>
                <th>Supplier</th>
                <th>Operator</th>
                <th>Pembayaran</th>
                <th>Disc</th>
                <th>Jumlah</th>
                <!-- Add more columns based on your query -->
            </tr>
        </thead>
        <tbody>
            @foreach ($hutangs as $index => $pembelian)
            <tr>
                <td>{{ $helpers->format_tanggal_transaksi($pembelian->tanggal) }}</td>
                <td>{{$pembelian->kd_beli}}</td>
                <td>{{$pembelian->kode}}</td>
                <td>{{$pembelian->nama_supplier}}</td>
                <td>{{ $pembelian->operator }}</td>
                <td>{{$pembelian->visa}}</td>
                <td>{{ round($pembelian->diskon) }}</td>
                <td style="text-align: right;">{{$helpers->format_uang($pembelian->jumlah)}}</td>
                <!-- Add more columns based on your query -->
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"></td>
                <td>Total</td>
                <td style="text-align: right;">Rp. {{ $helpers->format_uang($hutangs->sum('jumlah')) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
