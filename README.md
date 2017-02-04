# Patreon RSS

This is a quick hack to fetch the recent posts a creator made at Patreon.com.

Public (text) posts are available as full text, restricted posts only show the title.

This uses an unoffical API (the same that actually powers the Patreon website) and there are no guarantees that it won't break at some point.

This is in a very rough state but works good enough for me. Someone could easily take this and make a proper project from it:

* no need to edit the source to set the creator id
* find a way to use a creator name instead of the id
* composerize this
* use a proper RSS generator lib
* use a proper HTTP library (or abstraction)
* make filters and fields settable
* basically build a real API client
