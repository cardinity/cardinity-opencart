# Cardinity module for Opencart v 3.0.x

Accept card payment using Cardinity payment gateway on your opencart 3.x shop.

This also includes the temporary fix of opencart version 3.0.x. The new versions of Chrome and Firefox browsers have made <a href="https://www.chromium.org/updates/same-site">security update</a> by adding new cookie parameter `SameSite`. Opencart haven't updated the core to handle cookies to hold session through the 3D secure redirection yet. This is the reason why website customer session is lost and orders are left in incomplete state. <a href="https://github.com/opencart/opencart/issues/7946"> Here is the issue ticket</a>.

# Requirements

Opencart > 3.0.x , PHP > 7.2.5

# How to Install

## Method 1
1. Download the installer "oc-cardinity.ocmod.zip"
2. In your admin panel , Go to "Extensions" -> "Installer" and upload
3. After the upload you need to refresh the modifications here "Extensions" -> "Modifications" -> press refresh

## Method 2
1. Download a .zip file of this repository.
https://github.com/cardinity/cardinity-opencart/releases/download/3.0.x-Samesite/3.0-sessionfix-withexternal.zip
2. Extract contents of this .zip file in to main folder of your Opencart shop on the server.

# Setup
1. Go to "Extensions" > "Extensions" and select "Payment" from dropdown
2. Find cardinity on the list and click install "+" and edit afterwards
3. Fill up your cardinity key, secret, project ID, and project Secret, you can find them here 
https://my.cardinity.com/integration/api

# Downloads

https://github.com/cardinity/cardinity-opencart/releases/tag/3.0.x-patch-0.2


# Troubleshooting

If you do not have HTTPS enabled in your server and having problem with cookie uncomment these 2 lines 
        //$secure = false;
        //$samesite = 'Lax';
in here "catalog/controller/extension/payment/cardinity.php"


# Contacts

If you get any problems feel free to ask for help via <a href="mailto:techsupport@cardinity.com">techsupport@cardinity.com</a>


# Changelog

January 2021 Transaction log added
December 2020 Installer added
November 2020 3dsv2 update