<?php
ob_start(); // Start output buffering

require_once 'tcpdf/tcpdf.php';
require_once 'scripts/functions.php';
require_once 'compute_spi_cpi.php';

$rollno = "1401CE02";
$studentData = getsemgrades($rollno);
$nameArray = getnamefromroll($rollno);

$processedData = processStudentData($studentData, $gradePoints);

// Extract number of semesters
$semesters = array_unique(array_column($studentData, 'semno'));
$numSemesters = count($semesters);


echo '<pre>';
print_r($processedData[1]);
print_r($semesters);
print_r($numSemesters);
echo '<pre>';