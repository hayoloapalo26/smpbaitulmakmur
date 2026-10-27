<?php

namespace App\Observers;

use App\Models\AttributeViolation;
use App\Models\CharacterScoreLog;

class AttributeViolationObserver
{
    public function created(AttributeViolation $attributeViolation): void
    {
        $attrText = is_array($attributeViolation->attribute_type)
            ? implode(', ', $attributeViolation->attribute_type)
            : (string) $attributeViolation->attribute_type;

        if (empty($attrText) || $attrText === 'Lengkap') {
            return; // Jangan beri penalti jika atribut lengkap
        }

        $desc = 'Pelanggaran atribut: ' . $attrText;
        if (!empty($attributeViolation->jenis_pelanggaran)) {
            $desc .= ' - ' . $attributeViolation->jenis_pelanggaran;
        }

        CharacterScoreLog::create([
            'student_id' => $attributeViolation->student_id,
            'source' => 'guru_piket',
            'category' => 'kedisiplinan',
            'description' => $desc,
            'point_change' => -1,
            'tanggal' => $attributeViolation->tanggal,
        ]);
        // CharacterScoreLogObserver akan memperbarui total skor siswa secara otomatis.
    }
}
