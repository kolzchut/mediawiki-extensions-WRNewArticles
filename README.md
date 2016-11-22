WikiRights New Articles extension for MediaWiki
===============================================

This MediaWiki extension adds a special page that shows new articles as
defined for the Kol-Zchut website: pages moved from NS_WR_DRAFTS to
NS_MAIN.

## Roadmap
- Add a feed

## Changelog

### 1.0.0, 2016-11-22
- Handle old-style log entries as well as newer ones (serialized params,
  log_page)
- If a page was renamed since being released, it will link to the
  current page but also show the original name as it was upon release.
- If the WikiRights ArticleType extension is installed, Portals 
  will be shown in **bold** and marked as such, to make new portals
  stand out more.

### 0.1.0a, 2015-06-23
First version ever.
