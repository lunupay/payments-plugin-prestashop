# Installing Lunu Widget in PrestaShop 8.1.x

1. Download lunu.zip

2. Go to your PrestaShop admin panel » **Modules** » **Module Manager**.

3. Click **Upload a module**, then click **Select file**, find the file you just downloaded, select it and click **Open**.

4. When the installation is completed, click **Configure**.  

In the **Configure Settings** tab of the Lunu Module, you should enter your API credentials (tokens: Api secret, App ID) and then click **Save**.



## API credentials

You can get your credentials in your account on the console.lunupay.com website
in the section https://console.lunupay.com/developer-options

To configure the module:
1. Log in to your Lunu account at https://console.lunupay.com
2. Navigate to Developer Options to get your API credentials
3. Copy your App ID and API Secret
4. Paste them in the module configuration in PrestaShop


# Attention!

Keep in mind that if your website where you are using sandbox payments is not publicly
accessible for requests from the Internet - the notifications with the payment status
from our processing service would not reach your online store, and as a result
the status of orders in your store will not be changed.
