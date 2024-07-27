<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Laporan Kas {{$param}}
    </title>
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
    
    <div style="margin-top: -1rem;">
     <h5>{{$perusahaan->name}}</h5>
     <address style="margin-top: -23px;font-size:9px;">
        {{$perusahaan->address}}
    </address>
</div>

<h4>Laporan Kas {{$param}}</h4>
<hr style="margin-top:-.7rem;">
<table>
    <thead>
        <tr>
            <th width="50">Kode Kas</th>
            <th width="50">Nama Kas</th>
            <th>Saldo Awal</th>
            <th>Saldo Terkini</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($cashes as $index => $cash)
        <tr style="border-collapse: collapse;">
            <td>{{$cash->kode}}</td>
            <td>{{$cash->nama}}</td>
            <td>{{$helpers->format_uang($cash->saldo_awal)}}</td>
            <td>{{$helpers->format_uang($cash->saldo_kas)}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
