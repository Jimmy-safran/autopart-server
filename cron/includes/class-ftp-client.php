<?php
class FTPClient {
    private $host;
    private $user;
    private $pass;
    private $conn;
    
    public function __construct($host, $user, $pass) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
    }
    
    public function downloadFile($remotePath, $localPath) {
        $this->connect();
        
        if (!ftp_get($this->conn, $localPath, $remotePath, FTP_ASCII)) {
            throw new Exception("Failed to download file: $remotePath");
        }
        
        $this->disconnect();
    }
    
    private function connect() {
        $this->conn = ftp_connect($this->host);
        if (!$this->conn) {
            throw new Exception("Could not connect to FTP server");
        }
        
        if (!ftp_login($this->conn, $this->user, $this->pass)) {
            throw new Exception("FTP login failed");
        }
        
        ftp_pasv($this->conn, true);
    }
    
    private function disconnect() {
        if ($this->conn) {
            ftp_close($this->conn);
            $this->conn = null;
        }
    }
    
    public function __destruct() {
        $this->disconnect();
    }
}