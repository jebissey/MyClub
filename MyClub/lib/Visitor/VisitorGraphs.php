<?php
require_once __DIR__ . '/../jpgraph/jpgraph.php';
require_once __DIR__ . '/../jpgraph/jpgraph_pie.php';
require_once __DIR__ . '/../jpgraph/jpgraph_bar.php';

class VisitorGraph{
    private $log;
    
    public function __construct() {
        $this->log = new Log();
    }

    function draw(){
        self::drawPieClientType();
        self::drawBarOs();
    }

    private function drawPieClientType(){
        $groupedTypes = $this->log->getGroup('Type');
        $types = array();
        $typeCounts = array();
        foreach ($groupedTypes as $type) {
            $types[] = $type['Type'];
            $typeCounts[] = $type['count'];
        }
        
        $graph = new PieGraph(400, 300);
        $graph->SetShadow();
        $graph->title->Set("Client Types Distribution");
        
        $pie = new PiePlot($typeCounts);
        $pie->SetLegends($types);
        $pie->SetCenter(0.4, 0.5);
        $pie->ShowBorder();
        $pie->SetSliceColors(array('#FF9999','#66B3FF','#99FF99','#FFCC99'));
        
        $graph->Add($pie);
        $graph->Stroke(CLIENT_TYPES_GRAPH);
    }

    private function drawBarOs(){
        $groupedOs = $this->log->getGroup('Os');
        $osNames = array();
        $osCounts = array();
        foreach ($groupedOs as $os) {
            $osNames[] = $os['Os'];
            $osCounts[] = $os['count'];
        }
        
        $graph = new Graph(600, 400);
        $graph->SetScale("textlin");
        $graph->SetBox(false);
        $graph->SetMargin(40, 30, 50, 100);
        
        $graph->title->Set("Operating System Distribution");
        $graph->xaxis->SetTickLabels($osNames);
        $graph->xaxis->SetLabelAngle(45);
        
        $bar = new BarPlot($osCounts);
        $bar->SetFillColor("#66B3FF");
        $bar->SetWidth(0.7);
        
        $graph->Add($bar);
        $graph->Stroke("OS_GRAPH");
    }
}
?>