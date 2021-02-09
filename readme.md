# Cardinity Opencart module version 1.1.0 for opencart 2.3
This module is to use cardinity payment method via hosted payment for opencart 2.3.x
# Technical SameSite issue info
If `SameSite` attribute of cookie is not set to `none` and cookie duration is `0` (session) During the 3D secure redirection user session is lost and order is left unpaid ('payment pending').
# Requirements
Opencart > 2.3.x , PHP > 5.6
HTTPS
# Installation
1. Download the ocmod installer from 
    https://github.com/cardinity/cardinity-opencart/releases
2. Make a backup of your system first.
3. Remove previous version of module from opencart admin
    admin/index.php?route=extension/extension
4. Install new module using ocmod installer
    admin/index.php?route=extension/installer
5. Refresh modifications to clear previous cache
6. Setup API project key and secret on module dashboard
    admin/index.php?route=extension/payment/cardinity

# Contacts
If you get any problems feel free to ask for help via <a href="mailto:techsupport@cardinity.com">techsupport@cardinity.com</a>