<?php
class ReportModel {
    private $database;

    public function __construct($db) {
        $this->database = $db;
    }

    // Create Report
    public function createReport($id, $jenis_laporan, $tanggal_awal, $tanggal_akhir, $generated_by) {
        $stmt = $this->database->prepare("INSERT INTO laporan (id, jenis_laporan, tanggal_awal, tanggal_akhir, generated_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $id, $jenis_laporan, $tanggal_awal, $tanggal_akhir, $generated_by);
        
        return $stmt->execute();
    }

    // Read All Reports
    public function getAllReports() {
        $result = $this->database->query("SELECT * FROM laporan");
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    // Read Single Report
    public function getReportById($id) {
        $stmt = $this->database->prepare("SELECT * FROM laporan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Update Report
    public function updateReport($id, $jenis_laporan, $tanggal_awal, $tanggal_akhir, $generated_by) {
        $stmt = $this->database->prepare("UPDATE laporan SET jenis_laporan = ?, tanggal_awal = ?, tanggal_akhir = ?, generated_by = ? WHERE id = ?");
        $stmt->bind_param("sssii", $jenis_laporan, $tanggal_awal, $tanggal_akhir, $generated_by, $id);
        
        return $stmt->execute();
    }

    // Delete Report
    public function deleteReport($id) {
        $stmt = $this->database->prepare("DELETE FROM laporan WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
}
?>