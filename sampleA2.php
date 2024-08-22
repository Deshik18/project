<?php
ob_start(); // Start output buffering

require_once 'tcpdf/tcpdf.php';
require_once 'scripts/functions.php';
require_once 'scripts/compute_spi_cpi.php';

$rollno = "1401CH13";
$studentData = getsemgrades($rollno);
$nameArray = getnamefromroll($rollno);

$processedData = processStudentData($studentData, $gradePoints);

// Extract number of semesters
$semesters = array_unique(array_column($studentData, 'semno'));
$numSemesters = count($semesters);

$name = 'Unknown'; // Default value
if (!empty($nameArray) && isset($nameArray[0]['name'])) {
    // Take the first index of the array
    $firstNameEntry = $nameArray[0]['name'];
    // Replace commas with spaces
    $name = str_replace(',', ' ', $firstNameEntry);
}

// echo '<pr>';
// print_r($nameArray);
// echo '</pr>';

// Query the database to get subject details
function getSubjectDetails($subno) {
    // Replace with your actual database query logic
    // Example using PDO:
    $pdo = new PDO('mysql:host=localhost:3308;dbname=erpportal', 'root', '');
    $stmt = $pdo->prepare("SELECT subname, ltp FROM subject_master WHERE subno = :subno");
    $stmt->execute(['subno' => $subno]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Determine the year of admission
$yearOfAdmission = '20' . substr($rollno, 0, 2);

// Determine the program
$programMap = [
    '0' => 'Bachelor of Technology',
    '1' => 'Master of Technology',
    '2' => 'Doctor of Philosophy'
];
$program = isset($programMap[$rollno[2]]) ? $programMap[$rollno[2]] : 'Unknown Program';

// Determine the course
$courseMap = [
    'CS' => 'Computer Science and Engineering',
    'CB' => 'Chemical and Biochemical Engineering',
    'CE' => 'Civil Engineering',
    'EE' => 'Electrical Engineering',
    'ME' => 'Mechanical Engineering',
    'MM' => 'Metallurgical and Materials Engineering',
    'AI' => 'Artificial Intelligence and Data Science',
    'MC' => 'Mathematics and Computing',
    'EP' => 'Engineering Physics',
    'CH' => 'Chemical Science and Technology',
];
$courseCode = substr($rollno, 4, 2);
$course = isset($courseMap[$courseCode]) ? $courseMap[$courseCode] : 'Unknown Course';

ob_end_clean(); // Clear any output before generating the PDF

// Create TCPDF object
$pdf = new TCPDF('L', 'mm', 'A2');

// Remove default header and footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Define the cell dimensions
$margin = 10;
$cellWidth = 55;
$cellHeight = 65; // auto height
$pageWidth = $pdf->getPageWidth();

// Set cell padding to minimal
$pdf->setCellPaddings(0, 10, 0, 0);

// Set cell height ratio to minimal
$pdf->setCellHeightRatio(1.0);

// Define the HTML content for left and right parts
$htmlContent = '
<div style="text-align: center; color: black;">
    <img src="sample_data/iitp_logo.jpg" alt="Logo" width="100" height="80">
    <h2 style="font-size: 13px; margin: 0;">INTERIM TRANSCRIPT</h2>
    <hr width="80%" style="margin: 0;">
</div>';

// Define the HTML content for the middle part
$middleContent = '
<div style="text-align: center; color: black;">
    <h1 style="font-size: 26px; margin: 0;">भारतीय प्रौद्योगिकी संस्थान पटना</h1>
    <h2 style="font-size: 22px; margin: 0;">Indian Institute of Technology, Patna</h2>
    <h2 style="font-size: 20px; margin: 0;">Transcript</h2>
</div>';

// Left part
$pdf->writeHTMLCell($cellWidth, $cellHeight, $margin, '', $htmlContent, 1, 0, 0, true, 'C', true );

// Middle part (spanning the rest of the page width)
$middleCellWidth = $pageWidth - 2 * ($margin + $cellWidth);
$pdf->writeHTMLCell($middleCellWidth, $cellHeight, $margin + $cellWidth, '', $middleContent, 1, 0, 0, true, 'C', true);

// Right part
$pdf->writeHTMLCell($cellWidth, $cellHeight, $pageWidth - $margin - $cellWidth, '', $htmlContent, 1, 1, 0, true, 'C', true);

// Calculate the big cell dimensions
$bigCellWidth = $pageWidth - 2 * $margin;
$pageHeight = $pdf->getPageHeight();
$bottomMargin = 20; // Margin from the bottom where the big cell should end
$bigCellStartY = $margin + $cellHeight; // Y-coordinate where the big cell starts
$bigCellHeight = 320; // Calculate the height

// Write the big cell below the three tables
$pdf->writeHTMLCell($bigCellWidth, $bigCellHeight, $margin, $bigCellStartY, '', 1, 1, 0, true, 'L', true);

//additional Content
$additionalContent = '
<div style="color: black; text-align: center;">
    <p style="font-size: 18px; margin: 0; padding: 0;">
        Roll No: ' . $rollno . ' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Name: ' . $name . '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Year of Admission: ' . $yearOfAdmission . '
    </p>
    <p style="font-size: 18px; margin: 0; padding: 0;">
        Programme: ' . $program . ' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Course: ' . $course . '
    </p>
</div>';

$pdf->writeHTMLCell(280, 20, 160, 79, $additionalContent, 1, 1, 0, true, 'C', true);

function generateSemesterTable($semesterData, $semesterNumber) {
    $htmlTable = '
    <h1 style="font-weight: bold; text-decoration: underline; margin-bottom: 10px; font-size: 18px;">Semester ' . $semesterNumber . '</h1>
    <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; text-align: center; font-size: 12px;">
        <tr style="background-color: #f2f2f2;">
            <th style="width: 13%; text-align: center; font-weight: bold;">Sub. Code</th>
            <th style="width: 61%; text-align: center; font-weight: bold;">Subject Name</th>
            <th style="width: 10%; text-align: center; font-weight: bold;">L-T-P</th>
            <th style="width: 8%; text-align: center; font-weight: bold;">CRD</th>
            <th style="width: 8%; text-align: center; font-weight: bold;">GRD</th>
        </tr>';
    
    foreach ($semesterData as $subject) {
        $subjectDetails = getSubjectDetails($subject['subno']);
        $subname = $subjectDetails['subname'];
        $ltp = $subjectDetails['ltp'];
        $htmlTable .= '
        <tr>
            <td style="text-align: center; font-size: 12px; font-weight: bold; padding: 10px;">' . $subject['subno'] . '</td>
            <td style="text-align: center; font-size: 12px; font-weight: bold; padding: 10px;">' . $subname . '</td>
            <td style="text-align: center; font-size: 12px; font-weight: bold; padding: 10px;">' . $ltp . '</td>
            <td style="text-align: center; font-size: 12px; font-weight: bold; padding: 10px;">' . $subject['crd'] . '</td>
            <td style="text-align: center; font-size: 12px; font-weight: bold; padding: 10px;">' . $subject['grade'] . '</td>
        </tr>';
    }
    
    $htmlTable .= '</table>';
    return $htmlTable;
}

// Generate tables for each semester
$semesterTables = [];
foreach ($semesters as $semester) {
    $semesterData = array_filter($studentData, function($subject) use ($semester) {
        return $subject['semno'] == $semester;
    });
    $semesterTables[] = generateSemesterTable($semesterData, $semester);
}

// Set table positions and dimensions
$tableX = 15; // X-coordinate for the first table
$tableY = 125; // Y-coordinate for all tables
$tableWidth = ($pageWidth - 2 * $tableX - 20) / 3; // Table width divided by 3 for side by side with extra space in between
$tableHeight = 120; // Adjust this height as needed

// Set spacing between tables
$spacing = 10;
$currentY = $tableY;

for ($i = 0; $i < $numSemesters; $i += 3) {
    // Determine the number of tables in the current row
    $tablesInRow = min(3, $numSemesters - $i);
    
    // Write the tables for each semester side by side within the big cell
    for ($j = 0; $j < $tablesInRow; $j++) {
        $pdf->writeHTMLCell($tableWidth, $tableHeight, $tableX + $j * ($tableWidth + $spacing), $currentY, $semesterTables[$i + $j], 0, 0, 0, true, 'L', true);
    }
    
    // Move to the next row
    $currentY += $tableHeight + 3;

    // Write the details below each table
    $detailsFontSize = 16; // Font size for the details
    $detailsHeight = 15; // Increased height for the details to fit the content better
    $pdf->SetFont('helvetica', '', $detailsFontSize); // Set the font size for details

    for ($j = 0; $j < $tablesInRow; $j++) {
        $semesterNumber = $i + $j + 1;
        $details = $processedData[$semesterNumber]; // Extract details from processedData

        $spi = number_format((float)$details['spi'], 2, '.', '');
        $cpi = number_format((float)$details['cpi'], 2, '.', '');

        $detailsText = 'Credits Taken: ' . $details['credits_taken'] . ' &nbsp; &nbsp; Credits Cleared: ' . $details['credits_cleared'] . ' &nbsp; &nbsp; SPI: ' . $spi . ' &nbsp; &nbsp; CPI: ' . $cpi;
        $pdf->writeHTMLCell($tableWidth, $detailsHeight, $tableX + $j * ($tableWidth + $spacing), $currentY, $detailsText, 1, 0, 0, true, 'C', true);
    }

    // Move to the next row
    $currentY += $detailsHeight + 5;

    // Draw a bottom line after each row of tables
    $pdf->Line($margin, $currentY, $margin + $bigCellWidth, $currentY);

    $currentY += 3;

}

// Output
$pdf->Output('interim_transcript.pdf', 'I');
?>
