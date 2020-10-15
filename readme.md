# Cardinity Opencart module temporary fix for samesite issues
This is a fix for OpenCart payment related to Chrome and Firefox update related to SameSite cookies attribute (https://www.chromium.org/updates/same-site).
# Technical SameSite issue info
If `SameSite` attribute of cookie is not set to `none` and cookie duration is `0` (session) During the 3D secure redirection user session is lost and order is left unpaid ('payment pending').
# Requirements
Opencart > 2.3.x , PHP > 5.6
HTTPS
# Installation
1. Download this repository as zip file.
2. Make a backup of admin and catalog folders
2. Extract contents of zip into your opencart directory.
# Contacts
If you get any problems feel free to ask for help via <a href="mailto:techsupport@cardinity.com">techsupport@cardinity.com</a>