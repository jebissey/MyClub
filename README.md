# MyClub – The ultra-light, fully customizable CMS for your association

![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-blue)  
![SQLite](https://img.shields.io/badge/SQLite-Supported-green)  
![License](https://img.shields.io/badge/license-MIT-lightgrey)  

**MyClub** is a lightweight CMS tailored for associations.  
It helps clubs manage **articles, events, and members** with an intuitive interface, user preferences, and built-in access control.

👍 [Tutorials](https://www.youtube.com/channel/UC3EmmKasCGdM2_fP2ZNdAzg)🇫🇷

📖 [Manuals](https://myclub.alwaysdata.net/)🇫🇷

---

## 📑 Table of Contents
- [MyClub – The ultra-light, fully customizable CMS for your association](#myclub--the-ultra-light-fully-customizable-cms-for-your-association)
  - [📑 Table of Contents](#-table-of-contents)
  - [✨ Features](#-features)
  - [🔐 Security \& Authorizations](#-security--authorizations)
    - [Available Authorizations](#available-authorizations)
  - [🧪 Automated Route Testing](#-automated-route-testing)
  - [👁️ Examples](#️-examples)
  - [�️ Manuals and tutorials (in French)](#️-manuals-and-tutorials-in-french)
- [WebApp Summary - Multi-Role Management System](#webapp-summary---multi-role-management-system)
  - [Overview](#overview)
  - [Administrator Roles](#administrator-roles)
    - [🗓️ Event Manager](#️-event-manager)
    - [🎨 Designer](#-designer)
    - [✍️ Redactor (Editor)](#️-redactor-editor)
    - [🧑‍🤝‍🧑 Person Manager](#-person-manager)
    - [🔍 Visitor Insights (Observer)](#-visitor-insights-observer)
    - [🛠️ Webmaster](#️-webmaster)
  - [End User Interface](#end-user-interface)
    - [Personal Space](#personal-space)
    - [Community Features](#community-features)
  - [Authentication System](#authentication-system)
- [🚀 Getting Started](#-getting-started)
  - [1) Download the latest release](#1-download-the-latest-release)
  - [2) Upload to your hosting](#2-upload-to-your-hosting)
  - [3) Log in with the administrator account and change email/password](#3-log-in-with-the-administrator-account-and-change-emailpassword)
  - [4) Create groups with authorizations](#4-create-groups-with-authorizations)
    - [Initial account \& Webmaster group (fresh installs)](#initial-account--webmaster-group-fresh-installs)
  - [5) Fill in the settings \[HomeDesigner\]](#5-fill-in-the-settings-homedesigner)
  - [6) Create event types and requirements \[EventDesigner\]](#6-create-event-types-and-requirements-eventdesigner)
  - [7) Create navigation bars \[NavbarDesigner\]](#7-create-navigation-bars-navbardesigner)
  - [8) Create self-registration and authorizationless groups \[PersonManager\]](#8-create-self-registration-and-authorizationless-groups-personmanager)
  - [9) Write articles \[Redactor\]](#9-write-articles-redactor)
  - [10) Publish public articles \[Editor\]](#10-publish-public-articles-editor)
- [💾 Data \& Backup](#-data--backup)
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

| Authorization       | Description                                                                  |
| ------------------- | ---------------------------------------------------------------------------- |
| **Webmaster**       | Full administrative access. Manage groups with authorizations                |
| **PersonManager**   | Manage members and their groups without authorization (CRUD, import/export). |
| **EventManager**    | Create, edit, and manage events (scheduling, location, participants).        |
| **Editor**          | Can publish public articles.                                                 |
| **Redactor**        | Write content and publish for restricted audiences (not public).             |
| **EventDesigner**   | Define event types and their attributes, assign to groups.                   |
| **HomeDesigner**    | Customize the homepage layout, and all other settings.                       |
| **KanbanDesigner**  | Create/manage kanban projects.                                               |
| **NavbarDesigner**  | Define navigation bars.                                                      |
| **VisitorInsights** | Access visitor statistics and analytics.                                     |

---

## 🧪 Automated Route Testing  

- **180+ routes** are automatically discovered and tested.
- **1900+ routes** are simuled to test each route with each authorization.
- For routes with parameters (`@`), the **JsonGetParameters** column must exist in the test database.  
- For **POST** routes, the **JsonPostParameters** column must be filled.  
- **Authentication** can be simulated via the **JsonConnectedUser** column.  
- Results can be validated with **Query** and **QueryExpectedResponse**.  
- Each test row must define **ExpectedResponseCode**.  

---

## 👁️ Examples  

👉 Demo site : [https://myclub.alwaysdata.net/](https://myclub.alwaysdata.net/)  { test account = user ＠ myclub ․ foo ( user2345 ) }

👉 Nordic walking site : [https://bnw-dijon.fr/](https://bnw-dijon.fr/) 

👉 Static site : [https://peinturesbribri.alwaysdata.net/navbar/show/article/3](https://peinturesbribri.alwaysdata.net/navbar/show/article/3)

👉 Un blog : [https://jeblog.alwaysdata.net/](https://jeblog.alwaysdata.net/)

---

## 👁️ Manuals and tutorials (in French)

👉 [You are](https://myclub.alwaysdata.net/navbar/show/article/10)

👉 [Short videos](https://myclub.alwaysdata.net/navbar/show/article/28)

---

# WebApp Summary - Multi-Role Management System

## Overview
This web application is a comprehensive management system based on user roles, featuring specialized interfaces for different types of administrators and end users. The application follows a modular architecture with contextual navigation based on permissions.

## Administrator Roles

### 🗓️ Event Manager
- **Weekly Calendar**: Overview of the next 3 week's events
- **Upcoming Events**: Planning and tracking of future events  
- **Invitation System**: Sending personalized invitations
- **Email Management**: Communication with participants
- **Pivot Table**: Statistical analysis of events

### 🎨 Designer
Interface divided into sub-specialties:
- **Event Designer**: Designing event types and managing requirements
- **Home Designer**: Configuring general settings and managing designs
- **Kanban Designer**: Creation Kanban projects
- **Navbar Designer**: Customizing the navigation bar

### ✍️ Redactor (Editor)
- **Article Management**: Creating, editing, and publishing content
- **Media Library**: Organizing and managing media files
- **Content Analytics**: Top 50 most viewed articles
- **Analytical Dashboard**: Cross-analysis of content

### 🧑‍🤝‍🧑 Person Manager
- **Member Management**: Member administration
- **Group management**: User group administration
- **Registration management**: Managing users registrations
- **Import members**: From CSV files

### 🔍 Visitor Insights (Observer)
- **Traffic Analysis**: Referrer sites and visitor sources
- **Page Performance**: Page rankings by period
- **Advanced Analytics**: Pivot tables for data analysis
- **Visitor Monitoring**: Logs and traffic statistics
- **Real-time Tracking**: Latest visits and user behavior

### 🛠️ Webmaster
- **Technical Administration**: Database browser
- **Group management**: User group administration
- **Registration management**: Managing users registrations
- **Maintenance**: Set/unset website under maintenance mode

## End User Interface

### Personal Space
- 🧑‍💼 **Account**: Personal profile management
- 🕒 **Availability**: Schedule and available time slots
- 🔐 **Groups**: Membership in various groups
- ⭐ **Preferences**: Customized settings
- 📊 **Statistics**: Personal dashboard

### Community Features
- 🧑‍🤝‍🧑 **Directory**: Member directory
- 📰 **News**: Personalized news feed
- 🗒️ **Notepad**: Personal note-taking space

## Authentication System
- **Custom Avatar**: Emoji display or Gravatar image
- **Conditional Admin Access**: Admin zone access button if authorized
- **Secure Logout**: Logout system
- **Contextual Help**: Integrated support

---

# 🚀 Getting Started

> This quick guide will take you from a freshly downloaded archive to a running and secured instance.

## 1) Download the latest release
- Get the last relase [here](https://github.com/jebissey/MyClub/releases).  

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
- The `Webmaster` group holds the highest level of access but not enable to do everything directly.

**Recommended first step:** create a new group (e.g., `Full`) that is granted **all available authorizations**, then add your regular admin user(s) to that group. This lets you use a daily admin account for routine tasks while keeping the original initial account reserved for emergency recovery and sensitive operations.

## 5) Fill in the settings [HomeDesigner]
- Go to **Administration zone > Designer** 🎨🔧

- Configure the **general settings** of the application (title, description, logo, etc.).

## 6) Create event types and requirements [EventDesigner]
- Go to **Administration zone > Designer** 🎨🗓️/📋
- **Create event types** (walks, meetings, trainings, etc.).
- Define **requirements and constraints** for each type (duration, participants, equipment, etc.).

## 7) Create navigation bars [NavbarDesigner]
- Go to **Administration zone > NavbarDesigner** 🎨📑
- **Design and customize navigation bars** for different user roles.

## 8) Create self-registration and authorizationless groups [PersonManager]
- Go to **Administration zone > PersonManager** 👥
- **Create groups without authorizations**, marked as self-registrable.
- Add users to these groups.

## 9) Write articles [Redactor]
- Go to **Administration zone > Redactor** ✍️
- **Write and manage articles** (drafts, editing, formatting).
- **Review and publish articles** to make them visible to groups or users but not the public.

## 10) Publish public articles [Editor]
- Go to **Articles list > Editor** 📢
- **Review and publish articles** to make them visible to groups, users or the public.

---

# 💾 Data & Backup  

All the site content is stored in a **SQLite database**:  
- Main data: `WebSite/data/MyClub.sqlite`  
- Logs: `WebSite/data/LogMyClub.sqlite`  

A **GFS backup** is created every time an article is saved, stored under:  
`WebSite/backup/<years>/<months>/<weekdays>/`  

> As the IT saying goes: *“Any data stored on a single medium is doomed to disappear.”*  
It is essential to **regularly copy `MyClub.sqlite` to another physical storage** to ensure data safety.

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

