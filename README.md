# Discogs-Seller-Price-Comparator
Find what Discogs sellers are selling items from your wantlist for the cheapest.
This program looks at the items for sale from given sellers from your wantlist, and outputs a spreadsheet comparing the sellers' prices with the mean prices of each release.

Notes about current version:
- Viewing the items from your wantlist from a seller requires verification, so my current solution is to copy and paste the entire html of the sellers' "items from my wantlist" page into a file named [seller name].txt in the project directory
- Since this code sends a lot of http requests, http error 429 will be raised if you try to add too many records to the spreadsheet. Try not to exceed 20 items in the spreadsheet for now. It should automatically stop and export when the exception is raised.
- In the current layout, it compares between 2 different sellers' items from my wantlist. You can change change this by modifying the files using the aforementioned format.
