<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;

use Videouri\Entities\Sitemap;
use Videouri\Entities\Video;
use Videouri\Entities\SearchHistory;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videouri:sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sitemap based on data registered in the database';


    // VIDEOURI

    /**
     * @var Video
     */
    protected $videos;

    /**
     * @var SearchHistory
     */
    protected $searchHistory;


    /**
     * [$videoDumpPath description]
     * @var string
     */
    protected $videoDumpPath = 'storage/app/videoDumpPath.json';
    
    protected $sitemapsDirectory = 'public/sitemaps';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Video $videos, SearchHistory $searchHistory)
    {
        parent::__construct();

        $this->videos = $videos;
        $this->searchHistory = $searchHistory;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ///
        $this->info('Initialized sitemap generating tool');
        ///
        
        $mainSitemapPath = $this->sitemapsDirectory . '/main.xml';

        $xmlHeading = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
    <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    </sitemapindex>
EOF;

        $xml = new \SimpleXMLElement($xmlHeading);

        // $sitemapindex = $xml->sitemapindex;

        self::generateVideoSitemap();

        ///
        $this->info('Appending video sitemap(s) to main sitemap');
        ///
        
        $sitemaps = Sitemap::all();
        foreach ($sitemaps as $sitemap) {
            $updated_at = $sitemap->updated_at;
            $filename = $sitemap->filename;
            
            if (env('APP_ENV') === 'local')
                $sitemapUrl = 'https://local.videouri.com/sitemaps/' . $filename;
            else
                $sitemapUrl = 'https://videouri.com/sitemaps/' . $filename;


            $sitemap = $xml->addChild('sitemap');
            $sitemap->addChild('loc', $sitemapUrl);
            $sitemap->addChild('lastmod', $updated_at);
        }

        ///
        $this->info('Saving main sitemap at ' . $mainSitemapPath);
        ///
        
        $xml->asXML($mainSitemapPath);
    }

    private function generateVideoSitemap()
    {
        ///
        $this->info("Started processing videos into sitemap");
        ///

        $fields = ['id', 'original_id', 'provider', 'title', 'description', 'thumbnail', 'duration', 'updated_at', 'created_at'];

        
        // Initialize base Video eloquent query
        $videos = $this->videos->whereNotNull('title');

        // Default limit value
        $limit = 5000;

        // Retrieve last sitemap create, if there is one
        $lastSitemap = Sitemap::orderBy('id', 'desc')->take(1)->first();

        // Load last video sitemap or create a new one
        if ($lastSitemap &&
            File::exists($lastSitemap['path']) &&
            $lastSitemap->items_count < 50000)
        {
            $xml = simplexml_load_file($lastSitemap['path']);

            if (($xml->count() + $limit === 50000) && (50000 - $xml->count() < $limit))
                $limit = 50000 - $xml->count();
        } else {
            $xmlHeading = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
    <urlset
            xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xhtml="http://www.w3.org/1999/xhtml"
            xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
            xsi:schemaLocation="
                http://www.sitemaps.org/schemas/sitemap/0.9
                http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" />
EOF;
            $xml = new \SimpleXMLElement($xmlHeading);
        }

        // Get videos where id > than last one
        if (File::exists($this->videoDumpPath)) {
            $lastVideoDump = unserialize(File::get($this->videoDumpPath));
            $videos = $videos->where('id', '>', $lastVideoDump['id']);
        }

        $videos = $videos->limit($limit)->get($fields);

        ///
        $this->info("Appending videos to video sitemap");
        ///

        // Add videos to main xml
        $videos = $videos->toArray();
        foreach ($videos as $video) {
            $xmlUrl = $xml->addChild('url');

            if ($video['provider'] == 'Dailymotion')
                $key = 'd';
            elseif ($video['provider'] == 'Vimeo')
                $key = 'v';
            elseif ($video['provider'] == 'Youtube')
                $key = 'y';

            $customId = substr($video['original_id'], 0, 1) . $key . substr($video['original_id'], 1);


            if (env('APP_ENV') === 'local')
                $videoUrl = 'https://local.videouri.com/video/' . $customId;
            else
                $videoUrl = 'https://videouri.com/video/' . $customId;

            $description = htmlspecialchars(str_limit($video['description'], 2040));

            $xmlUrl->addChild('loc', $videoUrl);
            $xmlUrl->addChild('lastmod', $video['updated_at']);
            $xmlUrl->addChild('changefreq', 'monthly');
            // $xmlUrl->addChild('priority', '1.0');

            $videoGroup = $xmlUrl->addChild('video:video', null);
            $videoGroup->addChild('video:thumbnail_loc', $video['thumbnail']);
            $videoGroup->addChild('video:title', htmlspecialchars($video['title']));
            $videoGroup->addChild('video:description', $description);
            $videoGroup->addChild('video:player_loc', $videoUrl);
            $videoGroup->addChild('video:duration', $video['duration']);
            $videoGroup->addChild('video:publication_date', $video['created_at']);
            // $videoGroup->addChild('video:tag', $video['tags']);
        }


        // Dump last video information into a file
        $videosCount = count($videos);
        $lastVideo = $videos[$videosCount - 1];

        // Save report file
        if ($lastSitemap && $lastSitemap->items_count < 50000) {
            $sitemapId = $lastSitemap->id;
        } elseif ($lastSitemap && $lastSitemap->items_count == 50000) {
            $sitemapId = $lastSitemap->id + 1;
        } else {
            $sitemapId = 1;
        }

        // var_dump($sitemapId);
        // die;



        ///
        $this->info("Saving videoDump, video sitemap and updating db registry");
        ///


        $videoSitemapName = 'video-sitemap-' . $sitemapId . '.xml';
        $videoSitemapPath = $this->sitemapsDirectory . '/' . $videoSitemapName;

        $videoDumpPath = File::put($this->videoDumpPath, serialize($lastVideo));

        
        // Save xml file
        Header('Content-type: text/xml; charset=utf-8');
        $xml->asXML($videoSitemapPath);


        // Save sitemap info into DB
        if ($lastSitemap &&
            File::exists($lastSitemap['path']) &&
            $lastSitemap->items_count < 50000)
        {
            $lastSitemap->items_count = $xml->count();
            $lastSitemap->save();
        } else {
            Sitemap::create([
                'path'        => $videoSitemapPath,
                'filename'    => $videoSitemapName,
                'items_count' => $xml->count()
            ]);
        }
        
        // $this->info("Sitemap create at $this->videoSitemapPath");
        // $this->error("Couldn't save sitemap at $this->videoSitemapPath");
    }
}

