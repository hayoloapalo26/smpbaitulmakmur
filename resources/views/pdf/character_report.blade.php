<!DOCTYPE html>
<html>
<head>
    <title>Rapor Karakter - {{ $student->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2 style="text-align: center;">Rapor Karakter Siswa</h2>
    <hr>
    <p><strong>Nama:</strong> {{ $student->name }}</p>
    <p><strong>NIS:</strong> {{ $student->nis }}</p>
    <p><strong>Total Skor Karakter:</strong> {{ $student->character_score }}</p>
    
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Sumber</th>
                <th>Kategori</th>
                <th>Perubahan Poin</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->tanggal }}</td>
                <td>{{ $log->source }}</td>
                <td>{{ ucfirst($log->category) }}</td>
                <td>{{ $log->point_change > 0 ? '+'.$log->point_change : $log->point_change }}</td>
                <td>{{ $log->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
