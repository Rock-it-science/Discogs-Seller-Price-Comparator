# Discogs-Seller-Price-Comparator
Find what Discogs sellers are selling items from your wantlist for the cheapest. This program looks at the items for sale from given sellers from your wantlist, and outputs a spreadsheet comparing the sellers' prices with the mean prices of each release.

See spreadsheet-output branch for a working (but slow) version done in Java without the Discogs API

What it does now:
- Enter username: Downloads record IDs of all items on wantlist from that user
- Enter seller: Downloads record IDs of all items for sale by that user
- Analyze: Finds items that are in both the user's wantlist, and for sale by the seller

Future Plans/ideas:
- Allow for sellers with bigger inventories (handle 429)
- Allow for comparing prices between sellers
- Enter a specific list of items in addition to wantlist
  - List contains higher priority items from wantlist
    - Get items from high-priority list and find sellers selling those, but then check which of those sellers is also selling the most items from the wantlist
- Track updates
