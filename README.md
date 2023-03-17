# Social Login for Magento 2 

With the [Social Login For Magento 2](https://github.com/DevCrew-io/Social-Login) extension, one can log in / sign up quickly to the store without going through all the complicated and unnecessary registration steps. [Social Login For Magento 2](https://github.com/DevCrew-io/Social-Login) is developed to integrate Facebook and Google accounts with the Magento customer account by simply logging in to these platforms. With the help of this extension, one can also easily log in to the Magento store as well.

## Key Features

 - Quickly login with the most common (Facebook and Google) social channels.
 - After registering, it is simple to change the personal information.
 - Save the time of page reloads and get login/registration details from customers using a popup menu.
 - Customers' basic information is auto-filled from their social media accounts.

## How to install

You can purchase this extension via Magento market place by simply adding this product to cart and placing the order as it is free. Or you can manually install this using following steps: 

```
1.  Put code from main branch under app/code/Devcrew/SocialLogin directory.
2.  php bin/magento Module:enable Devcrew_SocialLogin
3.  php bin/magento setup:upgrade  
4.  php bin/magento setup:di:compile
```

## How to Configure

From the Admin Panel, navigate to `Stores > Settings > Configuration`. From the left navigation, expand the **DevCrew** tab select the **Social Login** extension.

![config-main](https://i.imgur.com/ZhnZQcM.png)

### General Configuration

In the **General Configuration** section, all configurations are listed.

![config-general](https://i.imgur.com/krLL88V.png)

- **Enable Social Login**: Select **Yes** to use the module's features.
- **Show Social Login Buttons on**:
    - You can select a page where to show social login buttons.
    - Social login buttons can be shown on either Customer Login page or Customer Registration page, or on both pages at the same time.

### Facebook

![config-facebbok](https://i.imgur.com/vlXUGzV.png)

- **Enable**: Select **Yes** to enable the Facebook login feature.
- **App ID**: Enter the Facebook App ID.
- **App Secret**: Enter the Facebook App Secret.
- **OAuth redirect URIs**: This URL will be generated automatically and needs to be added to Facebook App configurations.

**Note:** Please create the Facebook App first before using this feature. To create the app, click on the link provided under the "Enable" field.

### Google

![config-google](https://i.imgur.com/3LnpNzY.png)

- **Enable**: Select **Yes** to enable the Google login feature.
- **Client ID**: Enter the Google App Client ID.
- **Client Secret**: Enter the Google App Client Secret.
- **OAuth redirect URIs**: This URL will be generated automatically and needs to be added to Google App configurations.

**Note:** Please create the Google App first before using this feature. To create the app, click on the link provided under the "Enable" field.

## Preview on Front End 

After enabling and setting up all configurations, the social login buttons will be shown on the frontend pages:

### Customer Login Page

![preview-1](https://i.imgur.com/kcJ5Rbr.png)

### Customer Registration Page

![preview-2](https://i.imgur.com/XQkU2nb.png)

### Login By Facebook and Google

When you click on the "Sign in with Facebook" or "Sign in with Google" button, a pop-up will be shown in which you will be able to enter your relevant social account credentials, and after authentication, your account will be created and you will be redirected to the Magento customer account page as well.

![prewiew-3](https://i.imgur.com/xUwUPiY.png)

![prewiew-4](https://i.imgur.com/ZlhhZcS.png)


## Feature Request and Bug Report
If you want to include any feature or found any bugs, you can contact us at below email address.

``hello@devcrew.io``

``ismail@devcrew.io``
