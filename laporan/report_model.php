<?php
class ReportModel {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAllReports() {
        $query = "SELECT * FROM laporan ORDER BY id DESC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getReportById($id) {
        $query = "SELECT * FROM laporan WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createReport($id, $jenis_laporan, $tanggal_awal, $tanggal_akhir, $generated_by) {
        $query = "INSERT INTO reports (id, jenis_laporan, tanggal_awal, tanggal_akhir, generated_by) 
                  VALUES (:id, :jenis_laporan, :tanggal_awal, :tanggal_akhir, :generated_by)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':jenis_laporan', $jenis_laporan, PDO::PARAM_STR);
        $stmt->bindParam(':tanggal_awal', $tanggal_awal, PDO::PARAM_STR);
        $stmt->bindParam(':tanggal_akhir', $tanggal_akhir, PDO::PARAM_STR);
        $stmt->bindParam(':generated_by', $generated_by, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function updateReport($id, $jenis_laporan, $tanggal_awal, $tanggal_akhir, $generated_by) {
        $query = "UPDATE laporan 
        SET jenis_laporan = :jenis_laporan, 
            tanggal_awal = :tanggal_awal, 
            tanggal_akhir = :tanggal_akhir, 
            generated_by = :generated_by 
        WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':jenis_laporan', $jenis_laporan, PDO::PARAM_STR);
        $stmt->bindParam(':tanggal_awal', $tanggal_awal, PDO::PARAM_STR);
        $stmt->bindParam(':tanggal_akhir', $tanggal_akhir, PDO::PARAM_STR);
        $stmt->bindParam(':generated_by', $generated_by, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function deleteReport($id) {
        $query = "DELETE FROM laporan WHERE id = :id"; // Ubah 'reports' menjadi 'laporan'
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>