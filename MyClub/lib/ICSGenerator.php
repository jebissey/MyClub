<?php

class ICSGenerator {
    private $events = [];
    
    public function addEvent($summary, $description, $location, $startTime, $endTime) {
        $event = [
            'uid' => uniqid(),
            'summary' => $this->escapeString($summary),
            'description' => $this->escapeString($description),
            'location' => $this->escapeString($location),
            'startTime' => $this->formatTimestamp($startTime),
            'endTime' => $this->formatTimestamp($endTime),
            'created' => $this->formatTimestamp(time())
        ];
        
        $this->events[] = $event;
    }
    
    private function escapeString($string) {
        return preg_replace('/([\,;])/', '\\\$1', $string);
    }
    
    private function formatTimestamp($timestamp) {
        return date('Ymd\THis\Z', $timestamp);
    }
    
    public function generateICS() {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Burgundy Nordic Walking//My Club//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        
        foreach ($this->events as $event) {
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:" . $event['uid'] . "\r\n";
            $ics .= "DTSTAMP:" . $event['created'] . "\r\n";
            $ics .= "DTSTART:" . $event['startTime'] . "\r\n";
            $ics .= "DTEND:" . $event['endTime'] . "\r\n";
            $ics .= "SUMMARY:" . $event['summary'] . "\r\n";
            $ics .= "DESCRIPTION:" . $event['description'] . "\r\n";
            $ics .= "LOCATION:" . $event['location'] . "\r\n";
            $ics .= "END:VEVENT\r\n";
        }
        
        $ics .= "END:VCALENDAR\r\n";
        
        return $ics;
    }
    
    public function outputICS($filename = 'calendar.ics') {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $this->generateICS();
    }
}

// Exemple d'utilisation :
$icsGenerator = new ICSGenerator();

// Ajouter un événement
$icsGenerator->addEvent(
    'Réunion d\'équipe',
    'Réunion hebdomadaire de l\'équipe',
    'Salle de conférence',
    strtotime('2024-12-25 10:00:00'),
    strtotime('2024-12-25 11:30:00')
);

// Générer et télécharger le fichier ICS
$icsGenerator->outputICS('mon_calendrier.ics');
?>
