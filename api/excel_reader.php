<?php
// Simple Excel reader for VTU verification
// This is a basic CSV-compatible reader. For full Excel support, you'd need PhpSpreadsheet

class VTUExcelReader {
    
    public static function readStudentFiles() {
        $students = [];
        $studentDir = __DIR__ . '/../data/vtu_data/students/';
        
        if (is_dir($studentDir)) {
            // Read CSV files (prioritize larger datasets)
            $files = glob($studentDir . '*.csv');
            
            // Sort files to prioritize larger ones
            usort($files, function($a, $b) {
                return filesize($b) - filesize($a);
            });
            
            foreach ($files as $file) {
                $data = self::readCSVFile($file);
                $students = array_merge($students, $data);
            }
        }
        
        return $students;
    }
    
    public static function readStaffFiles() {
        $staff = [];
        $staffDir = __DIR__ . '/../data/vtu_data/staff/';
        
        if (is_dir($staffDir)) {
            // Only read CSV files for now (Excel files need PhpSpreadsheet)
            $files = glob($staffDir . '*.csv');
            foreach ($files as $file) {
                $data = self::readCSVFile($file);
                $staff = array_merge($staff, $data);
            }
        }
        
        return $staff;
    }
    
    private static function readCSVFile($filename) {
        $data = [];
        
        if (file_exists($filename)) {
            if (($handle = fopen($filename, "r")) !== FALSE) {
                $header = fgetcsv($handle); // Skip header row
                
                while (($row = fgetcsv($handle)) !== FALSE) {
                    if (count($row) >= 3) {
                        $data[] = [
                            'id' => trim($row[0]), // USN or Staff_ID (Column A)
                            'name' => trim($row[1]), // Name (Column B)
                            'expiry_date' => trim($row[2]) // Expiry Date (Column C)
                        ];
                    }
                }
                fclose($handle);
            }
        }
        
        return $data;
    }
    
    public static function verifyVTUID($name, $vtu_id, $user_type) {
        $data = [];
        
        if ($user_type === 'student') {
            $data = self::readStudentFiles();
        } else {
            $data = self::readStaffFiles();
        }
        
        foreach ($data as $record) {
            if (strtolower(trim($record['name'])) === strtolower(trim($name)) && 
                trim($record['id']) === trim($vtu_id)) {
                
                // Check if not expired - handle DD-MM-YYYY format
                $expiryDateStr = $record['expiry_date'];
                // Convert DD-MM-YYYY to YYYY-MM-DD for strtotime
                if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $expiryDateStr, $matches)) {
                    $expiryDateStr = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                }
                $expiryDate = strtotime($expiryDateStr);
                $currentDate = time();
                
                if ($expiryDate > $currentDate) {
                    return [
                        'valid' => true,
                        'name' => $record['name'],
                        'id' => $record['id'],
                        'expiry_date' => $record['expiry_date']
                    ];
                } else {
                    return [
                        'valid' => false,
                        'error' => 'VTU ID has expired on ' . $record['expiry_date']
                    ];
                }
            }
        }
        
        return [
            'valid' => false,
            'error' => 'VTU ID not found or name does not match'
        ];
    }
}
?>