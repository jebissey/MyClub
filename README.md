# MyClub

This web application is designed to make life easier for members of an association. It offers several key features:

**Viewing articles:** Members can read and share articles written by other members of the association.

**Activity management:** It's easy to sign up for the various activities offered by the association. You can also retrieve an iCal file to update your personal diary.

**User preferences:** Save your preferences for the types of events you like and your weekly availability. This allows you to filter the events on offer according to your preferences and schedule.

**Secure identification:** Users are identified by their e-mail address. When they log in for the first time, they must use the ‘forgotten password’ option to create a password.


This is a generic application.

It behaves like a **mini CMS** with articles and events.

All data is stored in a **SQLight** database.

## How to install

### From source: 

- clone
- update references with ```composer update```
- test localy from WebSite folder with ```php -S localhost:8000 ../dev/router.php```
- upload to the cloud
- enjoy

## How to customize

- Change WebSite/app/Images/home.png with your 48x48 image.
- Change WebSite/app/Images/logo.png with yours.
- Change WebSite/app/Images/favicon.ico with yours.
- You can add, change or remove emoji files. 48x48 image with name emoji...
- You can also change the other images. Keep size and name.
