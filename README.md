# MyClub – The ultra-light, fully customizable CMS for your association

![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-blue)  
![SQLite](https://img.shields.io/badge/SQLite-Supported-green)  
![License](https://img.shields.io/badge/license-MIT-lightgrey)  

**MyClub** is a lightweight CMS tailored for associations.  
It helps clubs manage **articles, events, and members** with an intuitive interface, user preferences, and built-in access control.  

---

## 📑 Table of Contents
- [MyClub – The ultra-light, fully customizable CMS for your association](#myclub--the-ultra-light-fully-customizable-cms-for-your-association)
  - [📑 Table of Contents](#-table-of-contents)
  - [✨ Features](#-features)
  - [🔐 Security \& Authorizations](#-security--authorizations)
    - [Available Authorizations](#available-authorizations)
  - [🧪 Automated Route Testing](#-automated-route-testing)
  - [👁️ Examples](#️-examples)
- [🚀 Getting Started](#-getting-started)
  - [1) Download the latest release](#1-download-the-latest-release)
  - [2) Upload to your hosting](#2-upload-to-your-hosting)
  - [3) Log in with the administrator account and change email/password](#3-log-in-with-the-administrator-account-and-change-emailpassword)
  - [4) Create groups with authorizations](#4-create-groups-with-authorizations)
    - [Initial account \& Webmaster group (fresh installs)](#initial-account--webmaster-group-fresh-installs)
- [❓ FAQ](#-faq)
  - [If you encounter this error…](#if-you-encounter-this-error)
    - [“Error: could not find driver”](#error-could-not-find-driver)
    - [Internal error: Class ”IntlDateFormatter" not found](#internal-error-class-intldateformatter-not-found)

---

## ✨ Features

1. **Articles** – Members can read and share articles written by other members.  
2. **Activity Management** – Sign up for activities, export events to your calendar with iCal.  
3. **User Preferences** – Save your favorite event types and weekly availability to filter events.  
4. **Secure Identification** – Users log in via email; the first login requires using the "forgotten password" option to create a password.  
5. **Mini CMS** – Includes articles, events, and customizable pages.  
6. **Database** – All data is stored in an **SQLite** database.  

---

## 🔐 Security & Authorizations  

Security is based on **groups**. There are three types:  
- Groups with authorization  
- Groups without authorization  
- Self-registration groups  

### Available Authorizations  

| Authorization       | Description                                                                 |
| ------------------- | --------------------------------------------------------------------------- |
| **Webmaster**       | Full administrative access to all features and settings of the application. |
| **PersonManager**   | Manage members and profiles (create, edit, delete, import/export).          |
| **EventManager**    | Create, edit, and manage events (scheduling, location, participants).       |
| **Redactor**        | Write content and publish for restricted audiences (not public).            |
| **Editor**          | Review, approve, publish/unpublish any content, including public articles.  |
| **HomeDesigner**    | Customize the homepage layout, banners, and featured sections.              |
| **EventDesigner**   | Define event types and their attributes, assign to groups.                  |
| **VisitorInsights** | Access visitor statistics and analytics.                                    |

---

## 🧪 Automated Route Testing  

- **150+ routes** are automatically discovered and tested.  
- For routes with parameters (`@`), the **JsonGetParameters** column must exist in the test database.  
- For **POST** routes, the **JsonPostParameters** column must be filled.  
- **Authentication** can be simulated via the **JsonConnectedUser** column.  
- Results can be validated with **Query** and **QueryExpectedResponse**.  
- Each test row must define **ExpectedResponseCode**.  

---

## 👁️ Examples  

👉 Demo site: [https://myclub.alwaysdata.net/](https://myclub.alwaysdata.net/)  { test account = user ＠ myclub ․ foo ( user2345 ) }

👉 Nordic walking site: [https://bnw-dijon.fr/](https://bnw-dijon.fr/) 

👉 Static site: [https://peinturesbribri.alwaysdata.net/navbar/show/article/3](https://peinturesbribri.alwaysdata.net/navbar/show/article/3)

---

# 🚀 Getting Started

> This quick guide will take you from a freshly downloaded archive to a running and secured instance.

## 1) Download the latest release
- Get the last relase [(https://github.com/jebissey/MyClub/releases)]here.  

## 2) Upload to your hosting
- Unpack the archive **locally** and then **upload** *WebSite* files to your hosting.

## 3) Log in with the administrator account and change email/password
- Open your site’s URL and log 🫥 in using the **initial administrator account** { admin account = webmaster ＠ myclub ․ foo ( admin1234 ) }.
- Go to **your avatar > Account** 🤔🧑‍💼:
  - Immediately change your **name** and **email address**.
  - **Sign out** and use **forgotten password** to create your password.

## 4) Create groups with authorizations
- Navigate to **Administration zone > Webmaster**:
  1. **Create groups** with their **authorizations** 🧑‍🤝‍🧑.
  2. **Assign authorizations** to user(s) 🎟️.

### Initial account & Webmaster group (fresh installs)
On a fresh installation the application creates **one initial user account** and **one group** named **Webmaster**.  
- The initial administrator account **cannot be deleted** and is the **only member** of the `Webmaster` group by default.  
- The `Webmaster` group holds the highest level of access and should be treated as a recovery/guardian account.

**Recommended first step:** create a new group (e.g., `Full`) that is granted **all available authorizations**, then add your regular admin user(s) to that group. This lets you use a daily admin account for routine tasks while keeping the original initial account reserved for emergency recovery and sensitive operations.

---

# ❓ FAQ

## If you encounter this error…

### “Error: could not find driver”
- **Causes**:
  - PDO driver not installed/activated.
- **Fix**:
  - Add/Enable `extension=pdo_sqlite`.


### Internal error: Class ”IntlDateFormatter" not found
- **Causes**:
  - intl extension not installed/activated.
- **Fix**:
  - Add/Enable `extension=intl`.

---

💡 **Tip**  
After each installation or update, log in with a **non-admin account** and go through the key screens (event creation, writing, publishing) to validate that **roles and authorizations** are properly set up.
