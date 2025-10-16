# CSV-Based Client Management System with Duplicate Detection

## A Laravel-based application for managing client data, featuring CSV import/export, duplicate detection, and an API for seamless integration.

## Table of Contents

-   [Overview](#overview)
-   [Features](#features)
-   [Prerequisites](#prerequisites)
-   [Installation](#installation)
-   [Database Setup & Migration](#database-setup--migration)
-   [Running the Application](#running-the-application)
-   [Running Tests](#running-tests)
-   [API Documentation](#api-documentation)
-   [Code Structure & Architecture](#code-structure--architecture)
-   [Database Schema](#database-schema)
-   [License](#license)

---

## Overview

This Laravel application provides a robust solution for managing client data through CSV file operations. It enables users to upload CSV files containing client information—specifically company_name, email, and phone_number—and processes them efficiently with built-in validation and duplicate detection. The system ensures data integrity by validating each row before import, handling invalid entries gracefully with clear error messages. To manage large datasets, the application supports batch processing, allowing for scalable and efficient imports. Additionally, users can export client data, including duplicates, in CSV format. The application is designed with a RESTful API architecture, adhering to best practices for HTTP methods, validation, and error handling, providing a seamless experience for users.

I have created a migration file named clients and added the necessary columns, along with an index to enhance performance. A controller for web functionality is located in the App/Http/Controllers directory under the name ClientController, while the API controller resides in App/Http/Controllers/Api as ClientApiController. Both controllers include methods for displaying, importing, and deleting client records. For the import functionality, I utilized Laravel's import feature. The logic for importing is encapsulated in the App/Imports directory within the ClientsImport file, and for exporting, it's located in the App/Exports directory as ClientsExport. A basic CSV file is used to demonstrate the functionality. The default Blade templates are employed to display the import file list, the upload section, and a page for showing invalid CSV rows encountered during the import process.

## Features

-   CSV Import: Upload and validate client data from CSV files.

-   CSV Export: Download client data, with filtering options for duplicates and unique records.

-   Duplicate Detection: Automatically identify and flag duplicate client records based on company_name, email, and phone_number.

-   API Endpoints: Provide RESTful API endpoints for CRUD operations on client data.

-   Batch Processing: Efficiently handle large CSV files using chunking and queuing.

## Prerequisites

What must be installed / available to run the project:

-   PHP version ^ 8.2
-   Composer
-   A database - Mysql

## Installation

Step by step instructions to set up the project:

git clone <repo-url>
cd project-directory
composer install
cp .env.example .env
php artisan key:generate

## Database migration

-   php artisan make:migrate

## Queue run for file import

-   php artisan queue:work

## API end points

For the API I have created a ClientApiController for conduct the import and export functionality

-   Get list
    -   URL: api/clients
    -   Method: get
-   Import csv file
    -   URL: api/clients/import
    -   Method : post
-   Export csv file
    -   URL: api/clients/export/file
    -   Method: get
-   Delete row
    -   URL: api/clients/{id}
    -   Method : delete
-   Import status
    -   URL: api/clients/import/status
    -   Method: get

## Web end points

For the web I have created a ClientController for conduct the import and export functionality

-   GIndex page
    -   URL: /clients
    -   Method: get
-   Upload form
    -   URL: /clients/upload
    -   Method : get
-   Import csv
    -   URL: /clients/import
    -   Method : post
-   Export csv file
    -   URL: /clients/export
    -   Method: get
-   Delete row
    -   URL: /clients/{id}
    -   Method : delete
-   Import status
    -   URL: /clients/importStatus
    -   Method: get

##Architecture and trade offs

-   The CSV management system to handle large datasets efficiently while maintaining data integrity and usability. For CSV imports, I implemented batch processing with validation for required fields and proper email formats to prevent corrupt or invalid data from entering the system; this ensures reliability but adds complexity with queued jobs and error handling. To maintain data quality, I implemented duplicate detection by comparing company_name, email, and phone_number, allowing users to view and manage duplicates; this improves accuracy but slightly impacts import performance. For exports, I enabled filtering and large file handling to support both unique and duplicate data downloads, balancing performance with user flexibility. Finally, I designed a RESTful API with proper validation and error responses to allow seamless integration with other systems


## Challanges
   I have faced challenges where I needed to display invalid rows from a CSV file directly in the UI. However, since I used Excel::queueImport(), the import runs asynchronously in the queue after the controller returns. This means that any errors generated during the import cannot be returned immediately to the current request, which posed a significant problem for real-time error reporting.

## Solutions
  For the challenges I faced, I implemented a solution to cache the errors and display them once the import finishes. I used the cache within the import class to store all errors with a unique key. This key is then passed to a separate function after the import completes. In that function, I retrieve the errors from the cache using the key. To display the errors to the user, I created a Blade template that refreshes at set intervals to capture and show errors from the ongoing import process.
  
