<?php
# Timeline Links takes your Twitter home timeline, filters out only the content
# containing links, expands them to their canonical form, and then returns
# an Atom feed that contains proper HTML hyperlinks.
#
# Build so I can use my Twitter feed as a Sparks source in Fever°

# Settings

define(TLL_POSTCOUNT, 100);
define(TLL_VERSION, '0.1.0');

# Get HTTPBasicAuth credentials

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Authenticate with the Twitter API"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'You have to provide your Twitter authentication details to access the Twitter API; Username and password is never stored.';
    exit;
} else {
    $user = $_SERVER['PHP_AUTH_USER'];
    $pass = $_SERVER['PHP_AUTH_PW'];
}

# URL parser

# Use curl follow-location to resolve 30* redirects to the canonical URL:
# Use this to find whether media is hosted on the Tumblr server or at a
# public location.
function resolve_redirects($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, TL_USERAGENT);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_exec($ch);
    $rsp = curl_getinfo($ch);
    curl_close($ch);
    return $rsp['url'];
}

function parse_urls($text) {
    return preg_replace(
        '@(http://[^ \)\]]+)([\)\]:;"\'+-—\.,]| |$)@e',
        "'<a href=\"' . resolve_redirects('\\1') . '\">\\1</a>\\2'",
        $text
    );
}


# New Twitter
require('lib/twitterlibphp/twitter.lib.php');
$t = new Twitter($user, $pass);

$timeline_data = $t->getHomeTimeline(
    array(
        'count' => TLL_POSTCOUNT
    )
);
if($timeline_data) {
   $feed = new SimpleXmlElement($timeline_data);
   $statuses = $feed->status;

   header("Content-Type: application/atom+xml");
?>
<?php echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; ?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="en">
    <title>Twitter Links Timeline / <?php echo $user ?></title>
    <link rel="alternate" type="text/html" href="http://twitter.com/home"/>
    <id>tag:net.benapps.timelinelinks,/twitter.com/<?php echo $user ?>/home</id>
    <updated><?php echo date("c", strtotime($statuses[0]->created_at)) ?></updated>
    <generator uri="http://github.com/benward/timelinelinks">Timelinelinks/<?php echo TLL_VERSION . ' (' . $_SERVER['HTTP_HOST'] . ')' ?></generator>
<?php
    foreach($statuses as $status) {

        # Handle Retweets as first-hand posts.
        if($status->retweeted_status) {
            $parent = $status;
            $status = $parent->retweeted_status;
        }
        else {
            $parent = false;
        }

        if(false === strpos($status->text, 'http://')) {
            continue;
        }

        ?>
        <entry>
            <id>http://twitter.com/<?php
                    echo $status->user->screen_name; ?>/<?php
                    echo $status->id ?></id>
            <link rel="alternate" type="text/html" href="http://twitter.com/<?php
                    echo $status->user->screen_name; ?>/<?php
                    echo $status->id ?>"/>
            <author>
                <name><?php echo $status->user->name ?></name>
                <uri>http://twitter.com/<?php echo $status->user->screen_name ?></uri>
            </author>
            <updated><?php echo date("c", strtotime($status->created_at)) ?></updated>
            <title type="html"><![CDATA[<?php
                if($parent) {
                    echo "<a href=\"http://twitter.com/{$parent->user->screen_name}\">{$parent->user->name}</a> ";
                    echo " retweeted ";
                }
                echo "<a href=\"http://twitter.com/{$status->user->screen_name}\">@{$status->user->screen_name}</a>";
                echo "/{$status->id}"; ?>]]></title>
            <content type="html"><![CDATA[<?php
                echo "<p>".parse_urls($status->text)."</p>"; ?>]]></content>
        </entry>
        <?php
    }
    ?>
</feed>
<?php
}

?>