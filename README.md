# RecipeMate — Website \& PHP REST API

A full-stack recipe and meal planning platform built with PHP and MySQL. This repository contains the **PHP website** and the **REST API backend** that powers the [RecipeMate Flutter mobile app](#).

\---

## Features

* User registration, login, and session management
* Browse, search, and view recipes
* Save favourite recipes
* Smart daily meal planner
* User profile management
* Spoonacular API integration for extended recipe data
* REST API endpoints consumed by the RecipeMate Flutter mobile app

\---

## Tech Stack

|Layer|Technology|
|-|-|
|Website|PHP 8.2, HTML, CSS|
|Database|MySQL (Aiven managed cloud)|
|API|PHP REST API (JSON responses)|
|Mobile client|Flutter (separate repository)|
|Deployment|Docker, Render, Aiven|
|External API|Spoonacular Food API|

\---

## Project Structure

```
recipe_meal_planner/
├── api/                  # REST API endpoints for the Flutter mobile app
│   ├── dbConnection.php  # API database connection
│   ├── api_config.php    # API base URL config
│   └── ...               # Individual endpoint files
├── config/
│   ├── config.php        # App configuration (reads from environment variables)
│   └── .htaccess         # Blocks direct access to config folder
├── favorites/            # Favourites page
├── planner/              # Meal planner page
├── profile/              # User profile page
├── recipes/              # Recipe listing and detail pages
├── uploads/              # Recipe images
├── admin/                # Admin panel
├── dbConnection.php      # Website database connection
├── dashboard.php         # Main dashboard
├── index.php             # Landing / login page
├── register.php          # Registration page
├── createTables.sql      # Database schema
├── importData.sql        # Seed data
└── Dockerfile            # Docker config for Render deployment
```

\---

## Local Development Setup

### Requirements

* XAMPP (PHP 8.2 + Apache + MySQL)
* Spoonacular API key — get one free at [spoonacular.com/food-api](https://spoonacular.com/food-api)

### Steps

1. Clone the repository into your XAMPP `htdocs` folder inside `recipe_meal_planner` subfolder:

```bash
   git clone https://github.com/rannilsharma/RecipeMate_Website_with_PHP_REST-API 
   ```

2. Start XAMPP and make sure Apache and MySQL are running.
3. Open MySQL Workbench or phpMyAdmin and create a database called `recipe_portal`.
4. Run `createTables.sql` to create the tables, then `importData.sql` to seed the data.
5. Open `config/config.php` — the local settings are already configured for XAMPP defaults. Update the port if yours differs.
6. Visit `http://localhost/recipe_meal_planner/` in your browser.

\---

## Environment Variables (Production)

This app reads all sensitive values from environment variables — nothing is hardcoded. Set the following in your hosting dashboard (e.g. Render):

|Variable|Description|
|-|-|
|`APP_ENV`|Set to `production`|
|`APP_BASE_URL`|Your deployed app URL e.g. `https://your-app.onrender.com`|
|`DB_HOST`|MySQL host  (e.g. Aiven)|
|`DB_USER`|MySQL username|
|`DB_PASS`|MySQL password|
|`DB_NAME`|MySQL database name|
|`DB_PORT`|MySQL port|
|`SPOONACULAR_API_KEY`|Your Spoonacular API key|

\---

## Deployment

This app is deployed using **Docker on Render** with **Aiven managed MySQL**.

* The `Dockerfile` sets up PHP 8.2 with Apache and all required extensions including SSL support for Aiven.
* Database credentials and API keys are injected as environment variables via the Render dashboard.
* The `config/config.php` file automatically switches between local and production settings based on the `APP_ENV` variable.

\---

## REST API

The `api/` folder contains PHP endpoints that serve JSON responses to the RecipeMate Flutter mobile app. All endpoints are stateless and follow REST conventions.

> The Flutter mobile app repository is available here: https://github.com/rannilsharma/RecipeMate_Flutter_Mobile_App

\---

## License

This project was developed as part of an academic project. All rights reserved.

