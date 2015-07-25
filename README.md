# php-url-shortener
Simple url shortener in php

Currently using base64.  
Basic counters of times shorted and times used for each link.  
Support http and https (will be updated to regex).  
Very basic anti-spam.
basic info page.

"config" file required in index.php and info.php should have the ddbb connection settings.  
"mysql commands" file contains the scripts to create the tables necessary to use this example.

Adding the character "$" at the emd of a shortened url allow you to visit the info page.
Info page now load a preview of the target page in a iframe.
