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

👉 Demo site: [https://myclub.alwaysdata.net/](https://myclub.alwaysdata.net/)  { test account = user@myclub.foo ( user1234 ) }

👉 Nordic walking site: [https://bnw-dijon.fr/](https://bnw-dijon.fr/) 

👉 Static site: [https://peinturesbribri.alwaysdata.net/navbar/show/article/3](https://peinturesbribri.alwaysdata.net/navbar/show/article/3)  