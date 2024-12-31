<?php
require_once __DIR__ . '/../../Database/Tables/Page.php';

class NavManager {
    private $page;
    private $imageBasePath = 'images/';
    
    public function __construct() {
        $this->page = new Page();
    }
    
    public function getNavItems() {
        return $this->page->getOrdered('Position');
    }
    
    public function updatePositions($positions) {
        
        foreach ($positions as $pos => $id) {
            $this->page->SetById($id, ['Position' => $pos]);
        }
        return true;
    }
    
    public function addNavItem($name, $file) {
        $position = $this->page->getMax('Position')['max'] + 1;
        
        return $this->page->set([
            'Name' => $name,
            'File' => $file,
            'Position' => $position,
            'Content' => ''
        ]);
    }

    public function updateNavItem($id, $name, $file, $content) {
        return $this->page->setById($id, [
            'Name' => $name,
            'File' => $file,
            'Content' => $content
        ]);
    }
    
    public function handleImageUpload($file) {
        $year = date('Y');
        $month = date('m');
        $path = $this->imageBasePath . $year . '/' . $month . '/';
        
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        
        $filename = uniqid() . '_' . $file['name'];
        $fullPath = $path . $filename;
        
        move_uploaded_file($file['tmp_name'], $fullPath);
        return $fullPath;
    }
    
    public function deleteImage($path) {
        if (file_exists($path)) {
            unlink($path);
            
            $monthDir = dirname($path);
            if (count(glob("$monthDir/*")) === 0) {
                rmdir($monthDir);
                
                $yearDir = dirname($monthDir);
                if (count(glob("$yearDir/*")) === 0) {
                    rmdir($yearDir);
                }
            }
        }
    }
}

?>