<?php

$gradePoints = [
    'AU' => 10,
    'PP' => 10,
    'AA' => 10,
    'AB' => 9,
    'BB' => 8,
    'BC' => 7,
    'CC' => 6,
    'CD' => 5,
    'DD' => 4,
    'F' => 0,
    'I' => 0,
    'NP' => 0,
    'NU' => 0,
    'X' => 0
];

function calculateSPI($grades, $credits, $gradePoints) {
    $totalPoints = 0;
    $totalCredits = 0;

    foreach ($grades as $index => $grade) {
        $totalPoints += $gradePoints[$grade] * $credits[$index];
        $totalCredits += $credits[$index];
    }

    return $totalCredits ? $totalPoints / $totalCredits : 0;
}

function calculateCPI($allGrades, $allCredits, $gradePoints) {
    $totalPoints = 0;
    $totalCredits = 0;

    foreach ($allGrades as $semester => $grades) {
        foreach ($grades as $index => $grade) {
            $totalPoints += $gradePoints[$grade] * $allCredits[$semester][$index];
            $totalCredits += $allCredits[$semester][$index];
        }
    }

    return $totalCredits ? $totalPoints / $totalCredits : 0;
}

function processStudentData($studentData, $gradePoints) {
    $result = [];
    $allGrades = [];
    $allCredits = [];

    foreach ($studentData as $data) {
        $semno = $data['semno'];

        if (!isset($result[$semno])) {
            $result[$semno] = [
                'credits_taken' => 0,
                'credits_cleared' => 0,
                'spi' => 0,
                'cpi' => 0
            ];
        }

        if (!isset($allGrades[$semno])) {
            $allGrades[$semno] = [];
            $allCredits[$semno] = [];
        }

        $grade = $data['grade'];
        $credits = $data['crd'];

        $result[$semno]['credits_taken'] += $credits;
        if ($grade != 'F') {
            $result[$semno]['credits_cleared'] += $credits;
        }

        $allGrades[$semno][] = $grade;
        $allCredits[$semno][] = $credits;
    }

    $cumulativeGrades = [];
    $cumulativeCredits = [];

    foreach ($result as $semno => &$semResult) {
        $semResult['spi'] = calculateSPI($allGrades[$semno], $allCredits[$semno], $gradePoints);

        $cumulativeGrades = array_merge($cumulativeGrades, $allGrades[$semno]);
        $cumulativeCredits = array_merge($cumulativeCredits, $allCredits[$semno]);

        $semResult['cpi'] = calculateCPI([$cumulativeGrades], [$cumulativeCredits], $gradePoints);
    }

    return $result;
}
