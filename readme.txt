PWA+AMP MyBB 1.8
################
version: 2.0.0
last updated: Fri., Apr. 10, 2020

Description
++++++++++++++++
Converts MyBB 1.8 into Progressive Web Apps and Accelerated Mobile Pages styles.

Highlight
++++++++++++++++
AMP can only work based on HTTPS -- you need to update your server to support SSL/HTTPS.

Open Issues
++++++++++++++++
* https://github.com/GoogleChrome/lighthouse/issues/7158
* https://github.com/SuperPWA/Super-Progressive-Web-Apps/issues/105

Infrastructure
++++++++++++++++
This version is developed based on MyBB version from 1.8.0 to 1.8.22.

Download
++++++++++++++++
-- GitHub: https://github.com/rickey29/pwamp-mybb18

Installation
++++++++++++++++

step 0:
================
Assume your MyBB 1.8 web site is located at: "http://yoursite.com".

step 1:
================
Extract PWA+AMP MyBB 1.8 zip package on your PC, you will get a "pwamp-mybb18" directory.

step 2:
================
Upload following directory and files from above "pwamp-mybb18" directory
-- languages/english/pwamp.lang.php
-- plugins/pwamp
-- plugins/pwamp.php

to your web site "inc" directory, so you will have follow new directory and files:
-- http://yoursite.com/inc/languages/english/pwamp.lang.php
-- http://yoursite.com/inc/plugins/pwamp
-- http://yoursite.com/inc/plugins/pwamp.php

step 3:
================
Browse to "Admin Control Panel", select "Plugins" menu under "Configuration" category, click the "Activate" link to install/activate "PWA+AMP MyBB 1.8".

Configuration
++++++++++++++++
None.

Upgrade Notice
++++++++++++++++
None.

Frequently Asked Questions
++++++++++++++++

How to check my website AMP validation status?
================
I use Chrome AMP Validator Extension: https://chrome.google.com/webstore/detail/amp-validator/nmoffdblmcmgeicmolmhobpoocbbmknc .  You can Google to find more solution.

How to audit my website PWA validation status?
================
I use Chrome Lighthouse Extension: https://chrome.google.com/webstore/detail/lighthouse/blipmdconlkpinefehnmjammfjpmpbjk .  You can Google to find more solution.

How to add website to mobile device home screen?
================
You can Google to find the solution, for example, this one: https://www.howtogeek.com/196087/how-to-add-websites-to-the-home-screen-on-any-smartphone-or-tablet/ .

History
++++++++++++++++
version 2.0.0 (Fri., Apr. 10, 2020)
-- improvement: re-write transcoding section

version 1.0.0 (Fri., Jun. 28, 2019)
-- new feature: inline with PWAMP phpBB 3.2

version 0.1.0 (Wed., May 15, 2019)
-- prototype development

Support
++++++++++++++++
author: Rickey Gu
web: https://flexplat.com
email: rickey29@gmail.com
twitter: @rickey29

Copyright and Disclaimer
++++++++++++++++
This application is open-source software released under the GNU Lesser General Public License Version 3: "http://fsf.org/".
