<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: none;
            padding: 8px;
            text-align: left;
            max-width: 130px; /* Set a maximum width for each cell */
            overflow: hidden;
            white-space: nowrap;
            /*text-overflow: ellipsis;*/
            font-size: 9.5px; 
        }

        th {
            background-color: #8a8a8a; /* Header background color */
        }

        tbody th, tbody td {
            border: none; /* Remove border for table rows */
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
        <li style="font-size: 10px;">PERIODE : {{$periode['start_date']}} S/D {{$periode['end_date']}}</li>
    </ul>
    <hr style="margin-top:-.7rem;">

    <table>
        <thead>
            <tr>
                <th width="50">Tanggal</th>
                <th width="50">No Faktur</th>
                <th>Supplier</th>
                <th>Jumlah</th>
                <th>Tempo</th>
                <th>Kode Kas</th>
                <th>Operator</th>
                <!-- Add more columns based on your query -->
            </tr>
        </thead>
        <tbody>
            @foreach ($hutangs as $index => $hutang)
            <tr>
                <td>{{ $hutang->tanggal }}</td>
                <td>{{$hutang->kode}}</td>
                <td>{{$hutang->supplier}}</td>
                <td>Rp. {{number_format($hutang->jumlah,  0, ',', '.')}}</td>
                <td>{{round($hutang->jatuh_tempo)}} Hari</td>
                <td>{{$hutang->kode_kas}}</td>
                <td>{{$hutang->operator}}</td>
                <!-- Add more columns based on your query -->
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"></td>
                <td>Total</td>
                <td>Rp. {{ $hutangs->sum('jumlah') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
