<?php
namespace Lib;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class BiologyOnlineScraper {
    private $client;
    private $baseUrl = 'https://www.biologyonline.com';
    
    public function __construct() {
        $this->client = HttpClient::create([
            'verify_peer' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
            ]
        ]);
    }
    
    public function scrapeCellContent() {
        try {
            $url = $this->baseUrl . '/tutorials/cell-biology';
            echo "Mengakses URL: " . $url . "<br>";
            
            $response = $this->client->request('GET', $url);
            $content = $response->getContent();
            
            $crawler = new Crawler($content);
            
            $articles = [];
            
            // Scrape artikel dan video
            $crawler->filter('article')->each(function (Crawler $node) use (&$articles) {
                try {
                    // Ambil video dari iframe YouTube jika ada
                    $videoUrl = '';
                    $node->filter('iframe')->each(function (Crawler $iframe) use (&$videoUrl) {
                        $src = $iframe->attr('src');
                        if (strpos($src, 'youtube.com') !== false) {
                            $videoUrl = $src;
                        }
                    });

                    $article = [
                        'title' => $this->cleanText($node->filter('h1.entry-title')->text('')),
                        'content' => $this->cleanText($node->filter('.entry-content p')->text('')),
                        'video_url' => $videoUrl,
                        'original_url' => $node->filter('a.read-more')->attr('href'),
                        'image_url' => $node->filter('img')->attr('src'),
                        'topic' => 'Cell Biology'
                    ];
                    
                    if (!empty($article['title'])) {
                        $articles[] = $article;
                        echo "Found article: " . $article['title'] . "<br>";
                        if (!empty($article['video_url'])) {
                            echo "Found video for: " . $article['title'] . "<br>";
                        }
                    }
                } catch (\Exception $e) {
                    echo "Error pada item: " . $e->getMessage() . "<br>";
                }
            });
            
            return $articles;
            
        } catch (\Exception $e) {
            echo "Error utama: " . $e->getMessage() . "<br>";
            return [];
        }
    }
    
    private function cleanText($text) {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}