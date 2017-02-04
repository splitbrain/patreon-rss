<?php

// set your creator ID here - you have to figure it out from the patreon HTML source code
$CREATOR_ID = '3764669';


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
        $data = $this->getData();

        header('Content-Type: application/rss+xml');
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
        $json = file_get_contents($url);
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
        echo htmlspecialchars($item['title']);
        echo '</title>';
        echo '<description>';
        echo htmlspecialchars($item['content']);
        echo '</description>';
        echo '<link>';
        echo htmlspecialchars($item['url']);
        echo '</link>';
        echo '<guid>';
        echo htmlspecialchars($item['url']);
        echo '</guid>';
        echo '<pubDate>';
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
        echo htmlspecialchars($campaign['creation_name'] . ' Patreon Posts');
        echo '</title>';
        echo '<description>';
        echo htmlspecialchars(strip_tags($campaign['summary']));
        echo '</description>';
        echo '<link>';
        echo htmlspecialchars($user['url']);
        echo '</link>';
    }
}

// main
$patreon = new PatreonRSS($CREATOR_ID);
$patreon->rss();