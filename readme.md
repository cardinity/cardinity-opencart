# Cardinity Opencart module `samesite` fix 
This is the temporary fix of opencart version 3.0.x. The new versions of Chrome and Firefox browsers have made <a href="https://www.chromium.org/updates/same-site">security update</a> by adding new cookie parameter `SameSite`. Opencart haven't updated the core to handle cookies to hold session through the 3D secure redirection yet. This is the reason why website customer session is lost and orders are left in incomplete state. <a href="https://github.com/opencart/opencart/issues/7946"> Here is the issue ticket</a>.
# Requirements
Opencart > 3.0.x , PHP > 7.1
# Contacts
If you get any problems feel free to ask for help via <a href="mailto:techsupport@cardinity.com">techsupport@cardinity.com</a>