<?php

require_once __DIR__ . '/../Database/Tables/Log.php';

class Display {
    const RECORDS_PER_PAGE = 10;
    private $log;
    
    public function __construct() {
        $this->log = new Log();
    }
    
    public function displayGrid($currentPage = 1, $filters = []) {
        $skip = ($currentPage - 1) * self::RECORDS_PER_PAGE;
        $result = $this->log->get($skip, self::RECORDS_PER_PAGE, $filters);
        $totalPages = ceil($result['total'] / self::RECORDS_PER_PAGE);
        
        $html = '<div class="visitor-grid">';
        $html .= $this->generateFilterForm($filters);
        $html .= '<table border="1" cellpadding="5">
                    <thead>
                        <tr>
                            <th>OS</th>
                            <th>Browser</th>
                            <th>Screen</th>
                            <th>Client Type</th>
                            <th>URI</th>
                            <th>Email</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($result['data'] as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= $this->generatePaginationControls($currentPage, $totalPages);
        $html .= '</div>';
        return $html;
    }
    
    private function generateFilterForm($filters) {
        $os = isset($filters['Os']) ? htmlspecialchars($filters['Os']) : '';
        $browser = isset($filters['Browser']) ? htmlspecialchars($filters['Browser']) : '';
        $Type = isset($filters['Type']) ? htmlspecialchars($filters['Type']) : '';
        $Who = isset($filters['Who']) ? htmlspecialchars($filters['Who']) : '';
        $html = '<form method="GET" class="filter-form">
                    <input type="text" name="os" placeholder="Filter OS" value="' . $os . '">
                    <input type="text" name="browser" placeholder="Filter Browser" value="' . $browser . '">
                    <input type="text" name="type" placeholder="Filter Type" value="' . $Type . '">
                    <input type="text" name="email" placeholder="Filter Email" value="' . $Who . '">
                    <button type="submit">Filter</button>
                </form>';
        return $html;
    }
    
    private function generatePaginationControls($currentPage, $totalPages) {
        $html = '<div class="pagination">';
        
        // First page button
        $html .= '<a href="?page=1" class="' . ($currentPage == 1 ? 'disabled' : '') . '">⟪</a>';
        
        // Previous page button
        $html .= '<a href="?page=' . ($currentPage - 1) . '" class="' . ($currentPage == 1 ? 'disabled' : '') . '">←</a>';
        
        // Page numbers
        for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
            $html .= '<a href="?page=' . $i . '" class="' . ($i == $currentPage ? 'active' : '') . '">' . $i . '</a>';
        }
        
        // Next page button
        $html .= '<a href="?page=' . ($currentPage + 1) . '" class="' . ($currentPage == $totalPages ? 'disabled' : '') . '">→</a>';
        
        // Last page button
        $html .= '<a href="?page=' . $totalPages . '" class="' . ($currentPage == $totalPages ? 'disabled' : '') . '">⟫</a>';
        
        $html .= '</div>';
        return $html;
    }
}

?>