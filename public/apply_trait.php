<?php
$modelsDir = __DIR__.'/../app/Models';
$files = glob($modelsDir . '/*.php');
$modelsToUpdate = [
    'AlumniTracer', 'AttributeViolation', 'BkReport', 'BkWorkProgram', 'CharacterScoreLog', 
    'Classroom', 'DailyAttendance', 'DcmResponse', 'DocumentArchive', 'DutySchedule', 
    'DutySwapRequest', 'EmptyClassLog', 'ExtracurricularAttendance', 'Extracurricular', 
    'Grade', 'HumasProgram', 'Inventory', 'LearningPlan', 'MandatoryTask', 'MeetingMinute', 
    'OptionalTask', 'OsisProgram', 'ParentSummon', 'PrayerAttendance', 'ProgramKepsek', 
    'ProgramKesiswaan', 'ProgramKurikulum', 'ProgramSarpras', 'QuranAchievement', 
    'ReadingQualityScore', 'Rppm', 'Schedule', 'SchoolWorkPlan', 'StudentAchievement', 
    'StudentCase', 'StudentHalangan', 'StudentLeaveRequest', 'Student', 'SubjectAttendance', 
    'Subject', 'TeacherAbsenceReport', 'TeacherAttendance', 'TeacherLeaveRequest', 
    'TeacherProfile', 'TeacherSubjectClass', 'TeacherSupervision', 'TeacherTodoList', 
    'TeachingJournal', 'TeachingMaterial', 'TodoTask', 'WorkProgram', 'YayasanProgram'
];

foreach ($files as $file) {
    $basename = basename($file, '.php');
    if (in_array($basename, $modelsToUpdate)) {
        $content = file_get_contents($file);
        if (strpos($content, 'BelongsToInstitution') === false) {
            $pattern = '/class\s+' . $basename . '.*?\{/s';
            if (preg_match($pattern, $content, $matches)) {
                $insert = "\n    use \App\Models\Traits\BelongsToInstitution;\n";
                $content = str_replace($matches[0], $matches[0] . $insert, $content);
                file_put_contents($file, $content);
            }
        }
    }
}
echo 'SUCCESS';
