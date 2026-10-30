<?php

namespace App\Services;

use App\Filament\Imports\StudentImporter;
use App\Models\Classroom;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentImportService
{
    /**
     * Import students from an uploaded file (CSV, TXT, or XLSX).
     */
    public function importFromFile(string $filePath, ?int $targetClassroomId = null, ?int $institutionId = null): array
    {
        $rows = $this->extractRowsFromFile($filePath);
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'File tidak memiliki data atau tidak dapat dibaca.',
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];
        }

        return $this->parseAndImportRows($rows, $targetClassroomId, $institutionId);
    }

    /**
     * Import students directly from CSV / text string content.
     */
    public function importFromContent(string $content, ?int $targetClassroomId = null, ?int $institutionId = null): array
    {
        $rows = $this->extractRowsFromString($content);
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'File tidak memiliki data atau tidak dapat dibaca.',
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];
        }

        return $this->parseAndImportRows($rows, $targetClassroomId, $institutionId);
    }

    /**
     * Extract raw tabular rows from CSV, TXT, or XLSX file.
     */
    public function extractRowsFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Check if file is an XLSX file (by extension or ZIP binary magic bytes PK\x03\x04)
        $isZipOrXlsx = in_array($ext, ['xlsx', 'xls']);
        if (!$isZipOrXlsx && file_exists($filePath)) {
            $handle = @fopen($filePath, 'rb');
            if ($handle) {
                $magic = fread($handle, 4);
                fclose($handle);
                if ($magic === "PK\x03\x04") {
                    $isZipOrXlsx = true;
                }
            }
        }

        if ($isZipOrXlsx) {
            // First attempt: OpenSpout reader
            if (class_exists(\OpenSpout\Reader\XLSX\Reader::class)) {
                try {
                    $reader = new \OpenSpout\Reader\XLSX\Reader();
                    $reader->open($filePath);
                    $rows = [];
                    foreach ($reader->getSheetIterator() as $sheet) {
                        foreach ($sheet->getRowIterator() as $row) {
                            $cells = $row->toArray();
                            $rows[] = array_map(fn ($c) => is_string($c) ? trim($c) : ($c instanceof \DateTimeInterface ? $c->format('Y-m-d') : (string) $c), $cells);
                        }
                        break; // Read first sheet
                    }
                    $reader->close();
                    if (!empty($rows)) {
                        return $rows;
                    }
                } catch (\Throwable $e) {
                    Log::warning('OpenSpout XLSX read failed: ' . $e->getMessage() . '. Falling back to direct ZipArchive reader.');
                }
            }

            // Fallback / High-compatibility attempt: Direct ZipArchive XML reader
            $zipRows = $this->extractRowsFromXlsxZip($filePath);
            if (!empty($zipRows)) {
                return $zipRows;
            }
        }

        // CSV / TXT Reading
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        return $this->extractRowsFromString($content);
    }

    /**
     * High-compatibility direct ZipArchive reader for XLSX files.
     * Bypasses OS ACL limitations and works cross-platform with zero external dependencies.
     */
    public function extractRowsFromXlsxZip(string $filePath): array
    {
        if (!class_exists(\ZipArchive::class) || !file_exists($filePath)) {
            return [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Read Shared Strings if present
        $sharedStrings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent !== false) {
            $xml = @simplexml_load_string($sharedXmlContent);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read first sheet XML
        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXmlContent === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheetXmlContent = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $rows = [];
        if ($sheetXmlContent !== false) {
            $xml = @simplexml_load_string($sheetXmlContent);
            if ($xml !== false && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $rowCells = [];
                    $colIndex = 0;
                    foreach ($row->c as $c) {
                        $cellRef = (string) $c['r'];
                        // Convert column letters (A, B, ... AA, AB) to 0-based column index
                        preg_match('/([A-Z]+)(\d+)/', $cellRef, $matches);
                        $targetCol = 0;
                        if (!empty($matches[1])) {
                            $letters = $matches[1];
                            for ($l = 0; $l < strlen($letters); $l++) {
                                $targetCol = $targetCol * 26 + (ord($letters[$l]) - 64);
                            }
                            $targetCol -= 1;
                        } else {
                            $targetCol = $colIndex;
                        }

                        while (count($rowCells) < $targetCol) {
                            $rowCells[] = '';
                        }

                        $type = (string) $c['t'];
                        $val = (string) $c->v;

                        if ($type === 's' && isset($sharedStrings[(int) $val])) {
                            $val = $sharedStrings[(int) $val];
                        } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                            $val = (string) $c->is->t;
                        }

                        $rowCells[$targetCol] = trim($val);
                        $colIndex = $targetCol + 1;
                    }
                    $rows[] = $rowCells;
                }
            }
        }

        $zip->close();
        return $rows;
    }

    /**
     * Extract rows from CSV / text string.
     */
    public function extractRowsFromString(string $content): array
    {
        // Strip UTF-8 BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [];
        }

        // Determine delimiter from first few lines
        $sample = implode("\n", array_slice($lines, 0, 10));
        $semicolons = substr_count($sample, ';');
        $commas = substr_count($sample, ',');
        $tabs = substr_count($sample, "\t");

        $delimiter = ',';
        if ($semicolons > $commas && $semicolons > $tabs) {
            $delimiter = ';';
        } elseif ($tabs > $commas && $tabs > $semicolons) {
            $delimiter = "\t";
        }

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line, $delimiter);
            $rows[] = array_map(fn ($cell) => is_string($cell) ? trim($cell) : (string) $cell, $row);
        }

        return $rows;
    }

    /**
     * Parse rows and import to students table.
     */
    public function parseAndImportRows(array $rawRows, ?int $targetClassroomId = null, ?int $institutionId = null): array
    {
        if (empty($institutionId) || $institutionId == 1) {
            $institutionId = 2; // Default to SMP Islam Baitul Makmur
        }

        // 1. Locate the header row
        $headerIndex = -1;
        $subHeaderIndex = -1;

        foreach ($rawRows as $idx => $row) {
            $lowerRow = array_map('strtolower', $row);
            $joined = implode(' ', $lowerRow);

            // Check if this row looks like a header
            $hasName = str_contains($joined, 'nama') || str_contains($joined, 'name');
            $hasId = str_contains($joined, 'nis') || str_contains($joined, 'nipd') || str_contains($joined, 'nisn') || str_contains($joined, 'nik') || str_contains($joined, 'jk') || str_contains($joined, 'jenis kelamin');

            if ($hasName && $hasId) {
                $headerIndex = $idx;
                break;
            }
        }

        if ($headerIndex === -1) {
            return [
                'success' => false,
                'message' => 'Header tabel siswa tidak ditemukan. Pastikan berkas memiliki kolom NAMA dan NIS/NISN.',
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];
        }

        // 2. Check if the next row is a sub-header (e.g. Format 2 & 3 with merged Ayah / Ibu / Wali headers)
        $headerRow = $rawRows[$headerIndex];
        $subHeaderRow = [];

        if (isset($rawRows[$headerIndex + 1])) {
            $nextRow = $rawRows[$headerIndex + 1];
            $nextJoined = strtolower(implode(' ', $nextRow));
            if (
                str_contains($nextJoined, 'jenjang pendidikan') ||
                str_contains($nextJoined, 'tanggal lahir') ||
                str_contains($nextJoined, 'penghasilan') ||
                (str_contains($nextJoined, 'nama') && str_contains($nextJoined, 'pekerjaan'))
            ) {
                $subHeaderIndex = $headerIndex + 1;
                $subHeaderRow = $nextRow;
            }
        }

        // 3. Build smart column mapping
        $columnMap = $this->buildColumnMap($headerRow, $subHeaderRow);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $startRow = ($subHeaderIndex !== -1) ? $subHeaderIndex + 1 : $headerIndex + 1;

        for ($i = $startRow; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];

            // Filter out empty rows or summary/footer lines
            if ($this->isSummaryOrEmptyRow($row, $columnMap)) {
                $skipped++;
                continue;
            }

            $name = $this->getVal($row, $columnMap, 'name');
            if (empty($name)) {
                $skipped++;
                continue;
            }

            $nis = $this->getVal($row, $columnMap, 'nis');
            if (empty($nis)) {
                $nis = 'NIS-' . strtoupper(Str::random(6));
            }

            $nisn = $this->getVal($row, $columnMap, 'nisn');
            $nik = $this->getVal($row, $columnMap, 'nik');
            $noKk = $this->getVal($row, $columnMap, 'no_kk');
            $tempatLahir = $this->getVal($row, $columnMap, 'tempat_lahir');
            $tglLahir = StudentImporter::parseDateValue($this->getVal($row, $columnMap, 'tanggal_lahir'));

            $rawJk = $this->getVal($row, $columnMap, 'jenis_kelamin');
            $jk = 'L';
            if (!empty($rawJk)) {
                $upperJk = strtoupper($rawJk);
                if (str_starts_with($upperJk, 'P') || str_contains($upperJk, 'PEREMPUAN') || str_contains($upperJk, 'WANITA')) {
                    $jk = 'P';
                }
            }

            $agama = $this->getVal($row, $columnMap, 'agama') ?: 'Islam';
            $anakKe = $this->getVal($row, $columnMap, 'anak_ke');
            $jumlahSaudara = $this->getVal($row, $columnMap, 'jumlah_saudara');
            $asalSekolah = $this->getVal($row, $columnMap, 'asal_sekolah');

            // Synthesize address if separated
            $alamat = $this->resolveAddress($row, $columnMap);

            // Ayah
            $namaAyah = $this->getVal($row, $columnMap, 'nama_ayah');
            $tempatLahirAyah = $this->getVal($row, $columnMap, 'tempat_lahir_ayah');
            $tglLahirAyah = StudentImporter::parseDateValue($this->getVal($row, $columnMap, 'tanggal_lahir_ayah'));
            $noHpAyah = $this->getVal($row, $columnMap, 'no_hp_ayah');
            $pendidikanAyah = $this->getVal($row, $columnMap, 'pendidikan_ayah');
            $pekerjaanAyah = $this->getVal($row, $columnMap, 'pekerjaan_ayah');
            $penghasilanAyah = $this->getVal($row, $columnMap, 'penghasilan_ayah');

            // Ibu
            $namaIbu = $this->getVal($row, $columnMap, 'nama_ibu');
            $tempatLahirIbu = $this->getVal($row, $columnMap, 'tempat_lahir_ibu');
            $tglLahirIbu = StudentImporter::parseDateValue($this->getVal($row, $columnMap, 'tanggal_lahir_ibu'));
            $noHpIbu = $this->getVal($row, $columnMap, 'no_hp_ibu');
            $pendidikanIbu = $this->getVal($row, $columnMap, 'pendidikan_ibu');
            $pekerjaanIbu = $this->getVal($row, $columnMap, 'pekerjaan_ibu');
            $penghasilanIbu = $this->getVal($row, $columnMap, 'penghasilan_ibu');

            // Wali
            $namaWali = $this->getVal($row, $columnMap, 'nama_wali');
            $noHpWali = $this->getVal($row, $columnMap, 'no_hp_wali');
            $pendidikanWali = $this->getVal($row, $columnMap, 'pendidikan_wali');
            $pekerjaanWali = $this->getVal($row, $columnMap, 'pekerjaan_wali');
            $penghasilanWali = $this->getVal($row, $columnMap, 'penghasilan_wali');

            // Classroom resolution
            $classroomId = $this->resolveClassroomId($row, $columnMap, $targetClassroomId, $institutionId);

            $studentQuery = Student::withoutGlobalScopes()->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                  ->orWhere('institution_id', 1)
                  ->orWhereNull('institution_id');
            });

            $student = (clone $studentQuery)->where('nis', $nis)->first();
            if (!$student && !empty($nisn)) {
                $student = (clone $studentQuery)->where('nisn', $nisn)->first();
            }

            $dataToSave = [
                'institution_id' => $institutionId,
                'name' => $name,
                'nisn' => $nisn,
                'nik' => $nik,
                'no_kk' => $noKk,
                'tempat_lahir' => $tempatLahir,
                'tanggal_lahir' => $tglLahir,
                'jenis_kelamin' => $jk,
                'agama' => $agama,
                'anak_ke' => !empty($anakKe) && is_numeric($anakKe) ? (int) $anakKe : null,
                'jumlah_saudara_kandung' => !empty($jumlahSaudara) && is_numeric($jumlahSaudara) ? (int) $jumlahSaudara : null,
                'jumlah_saudara' => !empty($jumlahSaudara) && is_numeric($jumlahSaudara) ? (int) $jumlahSaudara : null,
                'asal_sekolah' => $asalSekolah,
                'alamat_siswa' => $alamat,

                'nama_ayah' => $namaAyah,
                'tempat_lahir_ayah' => $tempatLahirAyah,
                'tanggal_lahir_ayah' => $tglLahirAyah,
                'no_hp_ayah' => $noHpAyah,
                'pendidikan_ayah' => $pendidikanAyah,
                'pekerjaan_ayah' => $pekerjaanAyah,
                'penghasilan_ayah' => $penghasilanAyah,

                'nama_ibu' => $namaIbu,
                'tempat_lahir_ibu' => $tempatLahirIbu,
                'tanggal_lahir_ibu' => $tglLahirIbu,
                'no_hp_ibu' => $noHpIbu,
                'pendidikan_ibu' => $pendidikanIbu,
                'pekerjaan_ibu' => $pekerjaanIbu,
                'penghasilan_ibu' => $penghasilanIbu,

                'nama_wali' => $namaWali,
                'no_hp_wali' => $noHpWali,
                'pendidikan_wali' => $pendidikanWali,
                'pekerjaan_wali' => $pekerjaanWali,
                'penghasilan_wali' => $penghasilanWali,
            ];

            if ($classroomId) {
                $dataToSave['classroom_id'] = $classroomId;
            }

            if ($student) {
                $student->update($dataToSave);
                $updated++;
            } else {
                $dataToSave['nis'] = $nis;
                $dataToSave['character_score'] = 80;
                Student::create($dataToSave);
                $created++;
            }
        }

        return [
            'success' => true,
            'total' => $created + $updated,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Build smart column index map from primary header and optional sub-header.
     */
    protected function buildColumnMap(array $headerRow, array $subHeaderRow = []): array
    {
        $map = [];
        $hasSub = !empty($subHeaderRow);

        $currentGroup = null;

        foreach ($headerRow as $colIdx => $headerCell) {
            $h = strtolower(trim($headerCell));
            $sub = $hasSub && isset($subHeaderRow[$colIdx]) ? strtolower(trim($subHeaderRow[$colIdx])) : '';

            // Group tracking for multi-column parent groups (Data Ayah, Data Ibu, Data Wali)
            if (str_contains($h, 'data ayah') || str_contains($h, 'ayah')) {
                $currentGroup = 'ayah';
            } elseif (str_contains($h, 'data ibu') || str_contains($h, 'ibu')) {
                $currentGroup = 'ibu';
            } elseif (str_contains($h, 'data wali') || str_contains($h, 'wali')) {
                $currentGroup = 'wali';
            } elseif (!empty($h) && !str_contains($h, 'nomor hp') && !str_contains($h, 'no hp') && !str_contains($h, 'no telepon')) {
                if (!in_array($currentGroup, ['ayah', 'ibu', 'wali'])) {
                    $currentGroup = null;
                }
            }

            // Direct headers or group+sub headers mapping
            $this->mapFieldIfMatches($map, 'kelas', $colIdx, $h, $sub, ['kelas', 'tingkat']);
            $this->mapFieldIfMatches($map, 'name', $colIdx, $h, $sub, ['nama', 'nama siswa', 'nama lengkap', 'name']);
            $this->mapFieldIfMatches($map, 'nis', $colIdx, $h, $sub, ['nis', 'nipd/nis', 'nipd', 'nomor induk siswa', 'no induk']);
            $this->mapFieldIfMatches($map, 'jenis_kelamin', $colIdx, $h, $sub, ['jenis kelamin', 'jenis kelamin.1', 'jk', 'gender']);
            $this->mapFieldIfMatches($map, 'nisn', $colIdx, $h, $sub, ['nisn', 'nomor induk siswa nasional']);
            $this->mapFieldIfMatches($map, 'tempat_lahir', $colIdx, $h, $sub, ['tempat lahir', 'tempat_lahir']);
            $this->mapFieldIfMatches($map, 'tanggal_lahir', $colIdx, $h, $sub, ['tanggal lahir', 'tanggal_lahir', 'tgl lahir']);
            $this->mapFieldIfMatches($map, 'nik', $colIdx, $h, $sub, ['nik', 'nomor induk kependudukan']);
            $this->mapFieldIfMatches($map, 'no_kk', $colIdx, $h, $sub, ['nomer kk (kartu keluarga)', 'no kk', 'nomor kk', 'nomer kk', 'no. kk']);
            $this->mapFieldIfMatches($map, 'agama', $colIdx, $h, $sub, ['agama']);
            $this->mapFieldIfMatches($map, 'anak_ke', $colIdx, $h, $sub, ['anak ke-', 'anak ke', 'anak_ke']);
            $this->mapFieldIfMatches($map, 'jumlah_saudara', $colIdx, $h, $sub, ['dari saudara kandung ke-', 'jumlah saudara kandung', 'jumlah saudara', 'jumlah_saudara']);
            $this->mapFieldIfMatches($map, 'asal_sekolah', $colIdx, $h, $sub, ['asal sekolah', 'sekolah asal', 'asal_sekolah']);

            // Address parts
            $this->mapFieldIfMatches($map, 'alamat_full', $colIdx, $h, $sub, ['alamat rumah siswa', 'alamat rumah']);
            $this->mapFieldIfMatches($map, 'alamat_jalan', $colIdx, $h, $sub, ['alamat', 'jalan', 'jl']);
            $this->mapFieldIfMatches($map, 'rt', $colIdx, $h, $sub, ['rt']);
            $this->mapFieldIfMatches($map, 'rw', $colIdx, $h, $sub, ['rw']);
            $this->mapFieldIfMatches($map, 'kelurahan', $colIdx, $h, $sub, ['kelurahan', 'desa']);
            $this->mapFieldIfMatches($map, 'kecamatan', $colIdx, $h, $sub, ['kecamatan']);
            $this->mapFieldIfMatches($map, 'kode_pos', $colIdx, $h, $sub, ['kode pos', 'kodepos']);

            // AYAH
            if (
                str_contains($h, 'nama ayah') ||
                ($currentGroup === 'ayah' && ($sub === 'nama' || str_contains($sub, 'nama')))
            ) {
                if (!isset($map['nama_ayah'])) $map['nama_ayah'] = $colIdx;
            }

            if (
                str_contains($h, 'tempat lahir (ayah)') || str_contains($h, 'tempat lahir ayah') ||
                ($currentGroup === 'ayah' && str_contains($sub, 'tempat lahir'))
            ) {
                if (!isset($map['tempat_lahir_ayah'])) $map['tempat_lahir_ayah'] = $colIdx;
            }

            if (
                str_contains($h, 'tanggal lahir (ayah)') || str_contains($h, 'tanggal lahir ayah') ||
                ($currentGroup === 'ayah' && str_contains($sub, 'tanggal lahir'))
            ) {
                if (!isset($map['tanggal_lahir_ayah'])) $map['tanggal_lahir_ayah'] = $colIdx;
            }

            if (
                str_contains($h, 'telp/hp (ayah)') || str_contains($h, 'hp ayah') ||
                ($currentGroup === 'ayah' && (str_contains($h, 'nomor hp') || str_contains($h, 'no hp') || str_contains($sub, 'hp') || str_contains($sub, 'telepon')))
            ) {
                if (!isset($map['no_hp_ayah'])) $map['no_hp_ayah'] = $colIdx;
            }

            if (
                str_contains($h, 'pendidikan (ayah)') || str_contains($h, 'pendidikan ayah') ||
                ($currentGroup === 'ayah' && (str_contains($sub, 'pendidikan') || str_contains($sub, 'jenjang')))
            ) {
                if (!isset($map['pendidikan_ayah'])) $map['pendidikan_ayah'] = $colIdx;
            }

            if (
                str_contains($h, 'pekerjaan (ayah)') || str_contains($h, 'pekerjaan ayah') ||
                ($currentGroup === 'ayah' && str_contains($sub, 'pekerjaan'))
            ) {
                if (!isset($map['pekerjaan_ayah'])) $map['pekerjaan_ayah'] = $colIdx;
            }

            if (
                str_contains($h, 'penghasilan (ayah)') || str_contains($h, 'penghasilan ayah') ||
                ($currentGroup === 'ayah' && str_contains($sub, 'penghasilan'))
            ) {
                if (!isset($map['penghasilan_ayah'])) $map['penghasilan_ayah'] = $colIdx;
            }

            // IBU
            if (
                str_contains($h, 'nama ibu') ||
                ($currentGroup === 'ibu' && ($sub === 'nama' || str_contains($sub, 'nama')))
            ) {
                if (!isset($map['nama_ibu'])) $map['nama_ibu'] = $colIdx;
            }

            if (
                str_contains($h, 'tempat lahir (ibu)') || str_contains($h, 'tempat lahir ibu') ||
                ($currentGroup === 'ibu' && str_contains($sub, 'tempat lahir'))
            ) {
                if (!isset($map['tempat_lahir_ibu'])) $map['tempat_lahir_ibu'] = $colIdx;
            }

            if (
                str_contains($h, 'tanggal lahir (ibu)') || str_contains($h, 'tanggal lahir ibu') ||
                ($currentGroup === 'ibu' && str_contains($sub, 'tanggal lahir'))
            ) {
                if (!isset($map['tanggal_lahir_ibu'])) $map['tanggal_lahir_ibu'] = $colIdx;
            }

            if (
                str_contains($h, 'no. telp/hp') || str_contains($h, 'telp/hp (ibu)') || str_contains($h, 'hp ibu') ||
                ($currentGroup === 'ibu' && (str_contains($h, 'nomor hp') || str_contains($h, 'no hp') || str_contains($sub, 'hp') || str_contains($sub, 'telepon')))
            ) {
                if (!isset($map['no_hp_ibu'])) $map['no_hp_ibu'] = $colIdx;
            }

            if (
                ($h === 'pendidikan' && $currentGroup === 'ibu') || str_contains($h, 'pendidikan (ibu)') || str_contains($h, 'pendidikan ibu') ||
                ($currentGroup === 'ibu' && (str_contains($sub, 'pendidikan') || str_contains($sub, 'jenjang')))
            ) {
                if (!isset($map['pendidikan_ibu'])) $map['pendidikan_ibu'] = $colIdx;
            }

            if (
                ($h === 'pekerjaan' && $currentGroup === 'ibu') || str_contains($h, 'pekerjaan (ibu)') || str_contains($h, 'pekerjaan ibu') ||
                ($currentGroup === 'ibu' && str_contains($sub, 'pekerjaan'))
            ) {
                if (!isset($map['pekerjaan_ibu'])) $map['pekerjaan_ibu'] = $colIdx;
            }

            if (
                str_contains($h, 'penghasilan') && $currentGroup === 'ibu' || str_contains($h, 'penghasilan (ibu)') || str_contains($h, 'penghasilan ibu') ||
                ($currentGroup === 'ibu' && str_contains($sub, 'penghasilan'))
            ) {
                if (!isset($map['penghasilan_ibu'])) $map['penghasilan_ibu'] = $colIdx;
            }

            // WALI
            if (
                str_contains($h, 'nama wali') ||
                ($currentGroup === 'wali' && ($sub === 'nama' || str_contains($sub, 'nama')))
            ) {
                if (!isset($map['nama_wali'])) $map['nama_wali'] = $colIdx;
            }

            if (
                str_contains($h, 'no hp') && $currentGroup === 'wali' || str_contains($h, 'no telepon') && $currentGroup === 'wali' ||
                ($currentGroup === 'wali' && (str_contains($sub, 'hp') || str_contains($sub, 'telepon')))
            ) {
                if (!isset($map['no_hp_wali'])) $map['no_hp_wali'] = $colIdx;
            }

            if (
                str_contains($h, 'pendidikan2') || str_contains($h, 'pendidikan (wali)') ||
                ($currentGroup === 'wali' && (str_contains($sub, 'pendidikan') || str_contains($sub, 'jenjang')))
            ) {
                if (!isset($map['pendidikan_wali'])) $map['pendidikan_wali'] = $colIdx;
            }

            if (
                str_contains($h, 'pekerjaan2') || str_contains($h, 'pekerjaan (wali)') ||
                ($currentGroup === 'wali' && str_contains($sub, 'pekerjaan'))
            ) {
                if (!isset($map['pekerjaan_wali'])) $map['pekerjaan_wali'] = $colIdx;
            }

            if (
                str_contains($h, 'penghasilan2') || str_contains($h, 'penghasilan (wali)') ||
                ($currentGroup === 'wali' && str_contains($sub, 'penghasilan'))
            ) {
                if (!isset($map['penghasilan_wali'])) $map['penghasilan_wali'] = $colIdx;
            }
        }

        return $map;
    }

    protected function mapFieldIfMatches(array &$map, string $fieldKey, int $colIdx, string $header, string $subHeader, array $candidates): void
    {
        if (isset($map[$fieldKey])) {
            return;
        }

        foreach ($candidates as $cand) {
            if ($header === $cand || (!empty($subHeader) && $subHeader === $cand)) {
                $map[$fieldKey] = $colIdx;
                return;
            }
        }
    }

    /**
     * Get value from row by mapped column key.
     */
    protected function getVal(array $row, array $columnMap, string $fieldKey): ?string
    {
        if (!isset($columnMap[$fieldKey])) {
            return null;
        }

        $idx = $columnMap[$fieldKey];
        if (!isset($row[$idx])) {
            return null;
        }

        $val = trim((string) $row[$idx]);
        return $val !== '' ? $val : null;
    }

    /**
     * Synthesize address from full address or split parts (Jalan, RT, RW, Kelurahan, Kecamatan, Kode Pos).
     */
    protected function resolveAddress(array $row, array $columnMap): ?string
    {
        // 1. Direct full address
        $full = $this->getVal($row, $columnMap, 'alamat_full');
        if (!empty($full)) {
            return $full;
        }

        $jalan = $this->getVal($row, $columnMap, 'alamat_jalan');
        $rt = $this->getVal($row, $columnMap, 'rt');
        $rw = $this->getVal($row, $columnMap, 'rw');
        $kel = $this->getVal($row, $columnMap, 'kelurahan');
        $kec = $this->getVal($row, $columnMap, 'kecamatan');
        $kodepos = $this->getVal($row, $columnMap, 'kode_pos');

        $parts = [];
        if (!empty($jalan)) {
            $parts[] = $jalan;
        }
        if (!empty($rt) || !empty($rw)) {
            $rtRw = '';
            if (!empty($rt)) $rtRw .= 'RT ' . $rt;
            if (!empty($rw)) $rtRw .= ($rtRw !== '' ? ' ' : '') . 'RW ' . $rw;
            $parts[] = $rtRw;
        }
        if (!empty($kel)) {
            $parts[] = 'Kel. ' . $kel;
        }
        if (!empty($kec)) {
            $parts[] = 'Kec. ' . $kec;
        }
        if (!empty($kodepos)) {
            $parts[] = 'Kode Pos ' . $kodepos;
        }

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Resolve classroom ID based on target selection or row's KELAS value.
     */
    protected function resolveClassroomId(array $row, array $columnMap, ?int $targetClassroomId, int $institutionId): ?int
    {
        if (!empty($targetClassroomId)) {
            return $targetClassroomId;
        }

        $rawKelas = $this->getVal($row, $columnMap, 'kelas');
        if (empty($rawKelas)) {
            return null;
        }

        $cleanKelas = strtoupper(trim($rawKelas));
        $tingkat = match (true) {
            str_contains($cleanKelas, '7') || str_contains($cleanKelas, 'VII') => '7',
            str_contains($cleanKelas, '8') || str_contains($cleanKelas, 'VIII') => '8',
            str_contains($cleanKelas, '9') || str_contains($cleanKelas, 'IX') => '9',
            default => null,
        };

        if (!$tingkat) {
            return null;
        }

        $classroom = Classroom::withoutGlobalScopes()
            ->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                  ->orWhere('institution_id', 1)
                  ->orWhereNull('institution_id');
            })
            ->where('tingkat', $tingkat)
            ->first();

        return $classroom?->id;
    }

    /**
     * Check if row is empty or a summary/footer row (e.g. "LAKI LAKI", "JUMLAH", "TOTAL").
     */
    protected function isSummaryOrEmptyRow(array $row, array $columnMap): bool
    {
        $nonEmptyCells = array_filter($row, fn ($c) => trim((string) $c) !== '');
        if (count($nonEmptyCells) < 2) {
            return true;
        }

        $joined = strtoupper(implode(' ', $nonEmptyCells));
        $skipKeywords = [
            'LAKI LAKI', 'LAKI-LAKI', 'PEREMPUAN', 'JUMLAH', 'TOTAL',
            'DATA SEMENTARA', 'UPDATE ', 'TAHUN AJARAN',
        ];

        foreach ($skipKeywords as $keyword) {
            if (str_contains($joined, $keyword) && count($nonEmptyCells) <= 4) {
                return true;
            }
        }

        // If Name cell contains pure summary keywords
        $name = $this->getVal($row, $columnMap, 'name');
        if ($name) {
            $upperName = strtoupper($name);
            foreach ($skipKeywords as $keyword) {
                if (str_contains($upperName, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }
}
