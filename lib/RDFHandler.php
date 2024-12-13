<?php
namespace Lib;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;

class RDFHandler {
    private $graph;
    
    public function __construct() {
        $this->graph = new Graph();
        RdfNamespace::set('learn', 'http://www.semanticweb.org/learning#');
    }
    
    public function addVideo($videoData) {
        $videoUri = "http://www.semanticweb.org/learning#video_" . uniqid();
        $video = $this->graph->resource($videoUri);
        $video->setType('learn:VideoContent');
        $video->add('learn:title', $videoData['title']);
        $video->add('learn:videoUrl', $videoData['video_url']);
        
        return $videoUri;
    }
}