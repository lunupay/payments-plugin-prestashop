# Installing Lunu Widget in PrestaShop 8.1.x

1. Download lunu.zip

2. Go to your PrestaShop admin panel » **Modules** » **Module Manager**.

3. Click **Upload a module**, then click **Select file**, find the file you just downloaded, select it and click **Open**.

4. When the installation is completed, click **Configure**.  

In the **Configure Settings** tab of the Lunu Module, you should enter your API credentials (tokens: Api secret, App ID) and then click **Save**.



## API credentials

You can get your credentials in your account on the console.lunu.io website
in the section https://console.lunu.io/developer-options  


For debugging, you can use the following credentials:  

  - sandbox mode:
    - App Id: 8ce43c7a-2143-467c-b8b5-fa748c598ddd
    - API Secret: f1819284-031e-42ad-8832-87c0f1145696

  - production mode:
    - App Id: a63127be-6440-9ecd-8baf-c7d08e379dab
    - API Secret: 25615105-7be2-4c25-9b4b-2f50e86e2311


# Attention!

Keep in mind that if your website where you are testing payments is not publicly
accessible for requests from the Internet - the notifications with the payment status
from our processing service would not reach your online store, and as a result
the status of orders in your store will not be changed.
