<!DOCTYPE html>
<html>
<head>
    <title>Laporan Program OSIS</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2 style="text-align: center;">Laporan Program Kerja OSIS & LPJ</h2>
    <hr>
    
    <table>
        <thead>
            <tr>
                <th>Nama Program</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Proposal</th>
                <th>LPJ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($programs as $prog)
            <tr>
                <td>{{ $prog->nama_program }}</td>
                <td>{{ $prog->tanggal }}</td>
                <td>{{ ucfirst($prog->status) }}</td>
                <td>{{ $prog->proposal_file ? 'Ada' : 'Tidak Ada' }}</td>
                <td>{{ $prog->lpj_file ? 'Ada' : 'Tidak Ada' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
