# MyClub

This web application is designed to make life easier for members of an association. It offers several key features:

**Viewing articles:** Members can read and share articles written by other members of the association.

**Activity management:** It's easy to sign up for the various activities offered by the association. You can also retrieve an iCal file to update your personal diary.

**User preferences:** Save your preferences for the types of events you like and your weekly availability. This allows you to filter the events on offer according to your preferences and schedule.

**Secure identification:** Users are identified by their e-mail address. When they log in for the first time, they must use the ‘forgotten password’ option to create a password.


This is a generic application.

It behaves like a **mini CMS** with articles.

But there are also events.

All data is stored in a **SQLight** database.

Security is based on groups. There are three types of group. Groups with authorisation, groups without authorisation and self-registration groups. There are five different authorisations: event manager, person manager, redactor, editor and webmaster.

## How to test
https://myclub.alwaysdata.net/

user@myclub.foo : user1234

## How to install

### From source: 

- clone
- update references with ```composer update``` from WebSite folder
- test locally from WebSite folder with ```php -S localhost:8000 ../dev/router.php```
- and in your browser http://localhost:8000/
- upload to the cloud to your host
- enjoy

## How to customize

- You must update the webmaster accout (webmaster@myclub.foo : admin1234)
- Create your groups with their authorizations
- Create your event types with their attributes
- Create your navigation bar
- Change WebSite/app/Images/home.png with your 48x48 image.
- Change WebSite/app/Images/logo.png with yours.
- Change WebSite/app/Images/favicon.ico with yours.
- You can add, change or remove emoji files. 48x48 image with name emoji...
- You can also change the other images. Keep size and name.
- Update records in Settings table.

## How to fix

- If you have this : "Error : could not find driverFatal error in file .../app/helpers/database/Database.php at line 38", you must add "extension=pdo_sqlite.so" in your php.ini
- If you have this : "Internal error: Class "IntlDateFormatter" not found", you must add "extension=intl" in your php.ini

## Examples
https://bnw-dijon.fr/

https://peinturesbribri.alwaysdata.net/navbar/show/article/3


