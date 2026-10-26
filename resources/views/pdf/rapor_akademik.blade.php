<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>E-Rapor Akademik</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header h3 { margin: 5px 0 0; font-weight: normal; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .grade-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .grade-table th, .grade-table td { border: 1px solid #000; padding: 8px; text-align: center; }
        .grade-table th { background-color: #f0f0f0; }
        .grade-table .left { text-align: left; }
        .footer { margin-top: 40px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SMP ISLAM BAITUL MAKMUR</h1>
        <h3>E-RAPOR AKADEMIK SISWA</h3>
    </div>

    <table class="info-table">
        <tr>
            <td width="20%">Nama Siswa</td>
            <td width="30%">: {{ $student->name }}</td>
            <td width="20%">Kelas</td>
            <td width="30%">: {{ $student->classroom->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>NIS</td>
            <td>: {{ $student->nis }}</td>
            <td>Semester</td>
            <td>: Ganjil</td>
        </tr>
    </table>

    <table class="grade-table">
        <thead>
            <tr>
                <th width="10%">No</th>
                <th width="40%" class="left">Mata Pelajaran</th>
                <th width="25%">Jenis Nilai</th>
                <th width="25%">Nilai (0-100)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $index => $grade)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="left">{{ $grade->subject->name ?? '-' }}</td>
                <td>{{ ucfirst($grade->type) }}</td>
                <td>{{ $grade->score }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4">Belum ada data nilai akademik.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Wali Kelas,</p>
        <br><br><br>
        <p>____________________</p>
    </div>
</body>
</html>
