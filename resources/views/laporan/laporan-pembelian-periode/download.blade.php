<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembelian Periode</title>
</head>
<body>
    <img src="{{ public_path('storage/tokos/' . $perusahaan['logo']) }}" alt="{{$perusahaan['logo']}}" width="100">
    <br>
    <h4>{{$perusahaan->name}}</h4>
    <address style="margin-top: -23px;">
        {{$perusahaan->address}}
    </address>

    <h2>Laporan Pembelian</h2>
    <table border="1" style="margin-top: 15px;">
        <thead>
            <tr>
                <th>No</th>
                <th width="100">Tanggal</th>
                <th width="100">No Faktur</th>
                <th>Supplier</th>
                <th>Operator</th>
                <th>Pembayaran</th>
                <th>Disc</th>
                <th>PPN</th>
                <th>Jumlah</th>
                <!-- Add more columns based on your query -->
            </tr>
        </thead>
        <tbody>
            @foreach ($pembelians as $index => $pembelian)
                <tr>
                    <td>{{$index += 1}}</td>
                    <td>{{ $pembelian->tanggal }}</td>
                    <td>{{$pembelian->kode}}</td>
                    <td>{{$pembelian->nama_supplier}}</td>
                    <td>{{ $pembelian->operator }}</td>
                    <td>{{$pembelian->lunas ? "Lunas" : "Hutang"}}</td>
                    <td>{{ round($pembelian->diskon) }}</td>
                    <td>{{round($pembelian->tax)}}</td>
                    <td>{{$pembelian->jumlah}}</td>
                    <!-- Add more columns based on your query -->
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
