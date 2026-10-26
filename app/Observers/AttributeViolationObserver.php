<?php

namespace App\Observers;

use App\Models\AttributeViolation;
use App\Models\CharacterScoreLog;

class AttributeViolationObserver
{
    public function created(AttributeViolation $attributeViolation): void
    {
        if ($attributeViolation->attribute_type === 'Lengkap') {
            return; // Do not penalize if attributes are complete
        }

        CharacterScoreLog::create([
            'student_id' => $attributeViolation->student_id,
            'source' => 'guru_piket',
            'category' => 'kedisiplinan', // Or something else
            'description' => 'Pelanggaran atribut: ' . $attributeViolation->attribute_type . ' - ' . $attributeViolation->jenis_pelanggaran,
            'point_change' => -1,
            'tanggal' => $attributeViolation->tanggal,
        ]);
        // The CharacterScoreLogObserver will automatically update the student's total score.
    }
}
