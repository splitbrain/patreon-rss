<?php

// set your creator ID here - you have to figure it out from the patreon HTML source code
$CREATOR_ID = '6107512';


/**
 * Class PatreonRSS
 *
 * Very simple class to fetch posts from Patreon and create an RSS feed from it
 *
 * A bit hacky and cuold be improved a lot, but works
 */
class PatreonRSS
{

    /** @var array which fields to include in the response, for now we don't need much */
    protected $fields = array(
        'post' =>
            array(
                'post_type',
                'title',
                'content',
                //'comment_count',
                //'min_cents_pledged_to_view',
                //'like_count',
                //'post_file',
                //'image',
                //'thumbnail_url',
                //'embed',
                //'is_paid',
                'published_at',
                'url',
                //'pledge_url',
                //'patreon_url',
                //'current_user_has_liked',
                //'patron_count',
                //'current_user_can_view',
                //'current_user_can_delete',
                //'upgrade_url',
            ),
        'user' =>
            array(
                'image_url',
                'full_name',
                'url',
            )
    );

    /** @var array haven't really played with those, except the creator id */
    protected $filter = array(
        'is_by_creator' => true,
        'is_following' => false,
        'creator_id' => 'set by constructor',
        'contains_exclusive_posts' => true
    );

    /**
     * PatreonRSS constructor.
     * @param string $id
     */
    public function __construct($id)
    {
        $this->filter['creator_id'] = $id;
    }

    /**
     * Output the RSS directly to the browser
     */
    public function rss()
    {
        if($data = $this->getData())
        {
            echo '<?xml version="1.0"?>';
            echo '<rss version="2.0">';
            echo '<channel>';
            $this->printRssChannelInfo($data['campaign'], $data['user']);
            foreach ($data['posts'] as $item) {
                $this->printRssItem($item);
            }
            echo '</channel>';
            echo '</rss>';
        }
        else
            return FALSE;
    }

    /**
     * Output the RSS but use a cache
     *
     * Note: this does absolutely no error checking and will just ignore errors. You have
     * to make sure the given $dir exists and is writable. Otherwise there will be no caching
     *
     * @param string $dir directory in which to store cache files - has to be writable
     * @param int $maxage maximum age for the cache in seconds
     */
    public function cachedRSS($dir, $maxage)
    {
        $cachefile = $dir.'/'.$this->filter['creator_id'].'.xml';
        $lastmod = @filemtime($cachefile);
        if(time() - $lastmod < $maxage) {
            echo file_get_contents($cachefile);
            return;
        }
        ob_start();
        $this->rss();
        $rss = ob_get_clean();
        @file_put_contents($cachefile, $rss); // we just ignore any errors
        echo $rss;
    }

    /**
     * Constructs the URL based on the fields and filter config at the top
     *
     * @return string
     */
    protected function getURL()
    {
        $url = 'https://api.patreon.com/stream?json-api-version=1.0';

        foreach ($this->fields as $type => $set) {
            $url .= '&fields[' . $type . ']=' . rawurlencode(join(',', $set));
        }

        foreach ($this->filter as $key => $val) {
            if ($val === true) $val = 'true';
            if ($val === false) $val = 'false';

            $url .= '&filter[' . $key . ']=' . $val;
        }

        $url .= '&page[cursor]=null';

        return $url;
    }

    /**
     * Fetches the data from Patreon and cleans it up for our usecase
     *
     * @return array
     */
    protected function getData()
    {
        $url = $this->getURL();

        $opts = array(
            'http'=>array(
              'method'=>"GET",
              'header'=>"Content-Type: application/x-www-form-urlencoded\r\n".
                        "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:109.0) Gecko/20100101 Firefox/121.0\r\n" // Mimic Linux Firefox user agent
            )
          );
          
        $context = stream_context_create($opts);

        if($json = file_get_contents($url,false,$context))
        {
            $data = json_decode($json, true);

            $clean = array(
                'posts' => array(),
                'user' => array(),
                'campaign' => array()
            );
            foreach ($data['data'] as $item) {
                $clean['posts'][] = $item['attributes'];
            }
            foreach ($data['included'] as $item) {
                if ($item['type'] == 'user') {
                    $clean['user'] = $item['attributes'];
                    $clean['user']['id'] = $item['id'];
                    continue;
                }
                if ($item['type'] == 'campaign') {
                    $clean['campaign'] = $item['attributes'];
                    $clean['campaign']['id'] = $item['id'];
                }
            }
    
        }
        else
            return FALSE;

        return $clean;
    }

    /**
     * Print a single post as RSS item
     *
     * @param array $item
     */
    protected function printRssItem($item)
    {
        echo '<item>';
        echo '<title>';
        if(isset($item['title']))
            echo htmlspecialchars($item['title']);
        echo '</title>';
        echo '<description>';
        if(isset($item['content']))
            echo htmlspecialchars($item['content']);
        echo '</description>';
        echo '<link>';
        if(isset($item['url']))
            echo htmlspecialchars($item['url']);
        echo '</link>';
        echo '<guid>';
        if(isset($item['url']))
            echo htmlspecialchars($item['url']);
        echo '</guid>';
        echo '<pubDate>';
        if(isset($item['published_at']))
            echo date('r', strtotime($item['published_at']));
        echo '</pubDate>';
        echo '</item>';
    }

    /**
     * Print the channel info from our campaign and user data
     *
     * @param array $campaign
     * @param array $user
     */
    protected function printRssChannelInfo($campaign, $user)
    {
        echo '<title>';
        echo htmlspecialchars($user['full_name'] . '\'s Patreon Posts');
        echo '</title>';
        echo '<description>';
        if(isset($campaign['creation_name']))
        {
            echo htmlspecialchars(strip_tags($campaign['creation_name']));
            echo "\n".htmlspecialchars("<hr>")."\n";
        }
        if(isset($campaign['summary']))
            echo htmlspecialchars(strip_tags($campaign['summary']));
        echo '</description>';
        echo '<link>';
        echo htmlspecialchars($user['url']);
        echo '</link>';

        if(isset($campaign["avatar_photo_image_urls"]))
        {
            if(isset($campaign["avatar_photo_image_urls"]["default"]))
            {
                echo "<image>";
                echo "<link>".htmlspecialchars($user['url'])."</link>";
                echo "<title>".htmlspecialchars($user['full_name'] . '\'s Patreon Posts')."</title>";
                echo "<url>".htmlspecialchars($campaign["avatar_photo_image_urls"]["default"])."</url>";
                echo "</image>";
            }
        }
    }
}

// main
header('Content-Type: application/rss+xml');
$patreon = new PatreonRSS($CREATOR_ID);

//$patreon->rss();  // Output RSS Without Using Cache
$patreon->cachedRSS(__DIR__, 60*60); // cache for an hour