PWA+AMP MyBB
################
version: 2.4.0
last updated: Sat., May 01, 2020

Description
++++++++++++++++
Converts MyBB into Progressive Web Apps and Accelerated Mobile Pages styles.

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
-- GitHub: https://github.com/rickey29/pwamp-mybb
-- MyBB Plugins Libraries: https://community.mybb.com/mods.php?action=view&pid=1364

Installation
++++++++++++++++

step 0:
================
Assume your MyBB website is located at: "http://yoursite.com".

step 1:
================
Extract PWA+AMP MyBB zip package on your PC, you will get a "pwamp" directory.

step 2:
================
Upload following file and directory within above "pwamp" directory
-- pwamp.php
-- pwamp (folder with other files & sub-folders)

to your website "/inc/plugins" directory, so you will have follow new file and directory:
-- http://yoursite.com/inc/plugins/pwamp.php
-- http://yoursite.com/inc/plugins/pwamp (folder with other files & sub-folders)

step 3:
================
Browse to Admin Control Panel -> Configuration -> Plugins, find "PWA+AMP MyBB" plugin, click the "Activate" link to activate it.

Configuration
++++++++++++++++
None.

Upgrade Notice
++++++++++++++++
None.

Uninstallation
++++++++++++++++

step 0:
================
Assume your MyBB website is located at: "http://yoursite.com".

step 1:
================
Navigate to Admin Control Panel -> Configuration -> Plugins, find "PWA+AMP MyBB" plugin, click the "Deactivate" link to deactivate it.

step 2:
================
If you want to uninstall the plugin permanently, you should delete following file and directory within your website:
-- http://yoursite.com/inc/plugins/pwamp.php
-- http://yoursite.com/inc/plugins/pwamp (folder with other files & sub-folders)

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
version 2.4.0 (Sat., May 01, 2020)
-- code optimization

version 2.3.0 (Wed., Apr. 15, 2020)
-- improvement: Support more form post functions.

version 2.2.0 (Sat., Apr. 11, 2020)
-- bug fix: Add support for language file to other languages, instead of only to English.

version 2.1.0 (Fri., Apr. 10, 2020)
-- bug fix: Corrupt PNG image causes non-recoverable fatal PHP error

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
