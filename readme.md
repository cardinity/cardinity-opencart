# Cardinity module for Opencart v 3.0.x Session fix and External Upgrades 

This add external payment feature to cardinity payment module.

This also includes the temporary fix of opencart version 3.0.x. The new versions of Chrome and Firefox browsers have made <a href="https://www.chromium.org/updates/same-site">security update</a> by adding new cookie parameter `SameSite`. Opencart haven't updated the core to handle cookies to hold session through the 3D secure redirection yet. This is the reason why website customer session is lost and orders are left in incomplete state. <a href="https://github.com/opencart/opencart/issues/7946"> Here is the issue ticket</a>.

# Instructions on how to update 

1. Download a .zip file of this repository.
https://github.com/cardinity/cardinity-opencart/releases/download/3.0.x-Samesite/3.0-sessionfix-withexternal.zip
2. Extract contents of this .zip file in to main folder of your Opencart shop on the server.

# Requirements

Opencart > 3.0.x , PHP > 7.1

# Troubleshooting

If you do not have HTTPS enabled in your server and having problem with cookie uncomment these 2 lines 
        //$secure = false;
        //$samesite = 'Lax';
in here "catalog/controller/extension/payment/cardinity.php"


# Contacts

If you get any problems feel free to ask for help via <a href="mailto:techsupport@cardinity.com">techsupport@cardinity.com</a>
