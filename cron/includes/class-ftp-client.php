<?php
class FTPClient {
    private $host;
    private $user;
    private $pass;
    private $conn;
    private $logger;
    
    public function __construct($host, $user, $pass, $logger) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->logger = $logger;
    }
    
    public function downloadFile($remotePath, $localPath) {
        try {
            $this->connect();
            
            if (!ftp_get($this->conn, $localPath, $remotePath, FTP_ASCII)) {
                throw new Exception("FTP download failed: $remotePath");
            }
            
            $this->logger->log("File downloaded: $remotePath");
            return true;
        } catch (Exception $e) {
            $this->logger->log($e->getMessage(), 'ERROR');
            return false;
        } finally {
            $this->disconnect();
        }
    }
    
    private function connect() {
        $this->conn = ftp_connect($this->host);
        if (!$this->conn) {
            throw new Exception("FTP connection failed: {$this->host}");
        }
        
        if (!ftp_login($this->conn, $this->user, $this->pass)) {
            throw new Exception("FTP login failed");
        }
        
        ftp_pasv($this->conn, true);
        $this->logger->log("FTP connected: {$this->host}");
    }
    
    private function disconnect() {
        if ($this->conn) {
            ftp_close($this->conn);
            $this->conn = null;
            $this->logger->log("FTP disconnected");
        }
    }
}