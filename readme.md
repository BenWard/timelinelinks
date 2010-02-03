# Timeline Links

Timeline Links takes your Twitter home timeline, filters the posts containing links, expands those links to their canonical form, and then returns an Atom feed that contains proper HTML hyperlinks.

Built so I can use my Twitter timeline as a Spark source in Fever°

# Requires

* TwitterLibPHP <http://github.com/polotek/twitterlibphp> — with the getHomeTimeline method.

# History

## v0.1.0

* First working version
* Auths using HTTP BasicAuth (that's what feed readers tend to support)
* URLs get expanded to canonical form by following 301 redirects.
* Unlike the official Twitter feeds, entries have `title`s (not duplicate `content`)
* Retweets are rendered as originals (with a retweet note in the `title`.)
* Each entry `id` should be the same as the `guid` from the Twitter RSS feed
  (the no-www, no trailing-/ URL of the tweet.); good feed readers should not
  detect duplicate posts.
* Reads 100 posts from Twitter at a time. Set the `TLL_POSTCOUNT` constant as
  you see fit.

(c) 2010 Ben Ward