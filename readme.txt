PWAMP MyBB 1.8 Readme
################
version: 0.1.0
last updated: Wed., May 15, 2019


Description
++++++++++++++++
Transcodes MyBB 1.8 into both first load cache-enabled of PWA and lightning fast load time of AMP style.


Highlight
++++++++++++++++
AMP can only work based on HTTPS -- you need to update your server to support SSL/HTTPS.


Open Issues
++++++++++++++++
-- Do NOT support reCAPTCHA so far.


Download
++++++++++++++++
-- GitHub: https://github.com/rickey29/pwamp-mybb18


Infrastructure
++++++++++++++++
This version is developed based on MyBB version from 1.8.0 to 1.8.20.


Installation
++++++++++++++++

step 0:
================
Assume your MyBB 1.8 web site is located at: "http://yoursite.com".

step 1:
================
Extract PWAMP MyBB 1.8 zip package on your PC, you will get a "pwampmybb18" directory.

step 2:
================
Upload following directory and file from above "pwampmybb18" directory
-- pwamp
-- pwamp.php

to your web site "inc/plugins" directory, so you will have follow new directory and file:
-- http://yoursite.com/inc/plugins/pwamp
-- http://yoursite.com/inc/plugins/pwamp.php

step 3:
================
Browse to "Admin Control Panel", select "Plugins" menu under "Configuration" category, click the "Activate" link to install/activate "PWAMP MyBB 1.8".


Configuration
++++++++++++++++
None.


Upgrade Notice
++++++++++++++++
None.


Frequently Asked Questions
++++++++++++++++

How to make web browser switch to AMP compliant pages?
================
Enter "https://yoursite.com/?amp" in your web browser address bar.

How to make web browser switch to original theme/style pages?
================
Enter "https://yoursite.com/?desktop" in your web browser address bar.


History
++++++++++++++++
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
