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

Security is based on groups. There are three types of group. Groups with authorisation, groups without authorisation and self-registration groups. There are different authorisations: 

| Authorization  | Description |
|--------------------|-------------|
| **Webmaster**      | Full administrative access to all features and settings of the web application, including technical and structural management. |
| **PersonManager**  | Can manage members and user profiles, including creating, editing, deleting, and importing/exporting user data. |
| **EventManager**   | Can create, edit, and manage events, including scheduling, location, and participant limits. |
| **Redactor**       | Can write articles and other content, and publish them if the audience is restricted to club members or to a specific group. Cannot publish publicly visible content. |
| **Editor**         | Can review, approve, publish, and unpublish any content, including content intended for public visibility. |
| **HomeDesigner**   | Can customize the home page layout, banners, and featured sections. |
| **EventDesigner**  | Can create event types, define their attributes, and optionally assign them to groups. |
| **VisitorInsights**| Can view visitor statistics and insights, including visit counts, pages viewed, and other analytics data. |


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
- Update records in Settings table.

## How to fix

- If you have this : "Error : could not find driverFatal error in file .../app/helpers/database/Database.php at line 38", you must add "extension=pdo_sqlite.so" in your php.ini
- If you have this : "Internal error: Class "IntlDateFormatter" not found", you must add "extension=intl" in your php.ini

## Examples
https://bnw-dijon.fr/

https://peinturesbribri.alwaysdata.net/navbar/show/article/3


