<?php

namespace App\Observers;

use App\Models\CharacterScoreLog;
use App\Models\Student;

class CharacterScoreLogObserver
{
    public function creating(CharacterScoreLog $characterScoreLog): void
    {
        // point_change is now determined by the respective Form/Controller
        // based on explicit logic (e.g., negative behavior = -1, positive = 0/+1).
    }

    public function created(CharacterScoreLog $characterScoreLog): void
    {
        // Update character_score on students table
        $student = Student::find($characterScoreLog->student_id);
        if ($student) {
            $student->character_score += $characterScoreLog->point_change;
            $student->save();
        }
    }

    public function deleted(CharacterScoreLog $characterScoreLog): void
    {
        $student = Student::find($characterScoreLog->student_id);
        if ($student) {
            $student->character_score -= $characterScoreLog->point_change;
            $student->save();
        }
    }
}
