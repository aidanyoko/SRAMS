Study Room Availability Monitoring System (SRAMS)

SRAMS is a website 

Stack
  - Frontend - Laravel blade, Tailwind, JavaScript
  - Backend - Laravel PHP, Node.js, MySQL, Python

Features
  - Room Detection with an AI and Camera System by Python
  - Booking seats in a classroom
  - User Log in & Registration
  - Real time people detection system

Programs Needed to run the website:
  - Browser ( Ex: Google Chrome)
  - VS Code
  - Python
  - Pip
  - Laravel
  - PHP 8.2+
  - MySQL
  - Node js
  - Npm install
  - Git
  - Important Folders
  - SRAMDB
  - app
    - http/controllers
    - LoginController
    - RegisterController
    - RoomController
  - resources
    - css
      - app.css
  - js
    - app.js
    - bootstrap.js
  - Views
    - Index.blade.php
    - Login.blade.php
    - register.blade.php
  - routes
    - console.php
    - web.php
  - env

Steps on installing Python
  1. Open your web browser
  2. Go to Python.org and download
  3. From the downloads tab in the menu, click on your system such as whether you are using Windows or Mac
  4. Click on the latest Python Install Manager to download 
  5. Install Python from the install manager exe
  6. You should have Python on your computer

Installations
  1. Download git from https://git-scm.com/install/
  2. Download Node.js from https://nodejs.org/en/download
  3. Download the project through zip from Github
    a. Run composer install and npm install from command promt on VS code on the laravel project
  4. Python - https://www.python.org/downloads/
  5. MySQL - “https://dev.mysql.com/downloads/workbench/”
  6. PHP  - https://www.php.net/downloads.php
  7. PIP
    a. Download the script from  https://bootstrap.pypa.io/get-pip.py
    b. Open Command prompt, cd to the folder containing get-pip.py
    c. Run “py get-pip.py”
  8. VS code - https://code.visualstudio.com/download

Steps for installing MySQL
  1. Open your browser
  2. Go to MySQL or the link “https://dev.mysql.com/downloads/workbench/”
  3. Download and install MySQL Workbench for your OS and system
  4. Open MySQL Workbench
  5. Create a connection with a host name and password
  6. Go to a SQL tab and enter (CREATE DATABASE ‘database name’)
  7. Go to the laravel project in VS code
  8. Go to the env file
  9. Edit DB connection as ‘mysql’, and write the DB username and password to the one for your SQL server 
  10. Run php artisan migrate in the command prompt of VS code
  11. The database and user accounts should start running

Steps to run the website in VS Code
  1. Go to the directory of the project
  2. Create a new terminal
  3. Open the command prompt terminal
  4. Run “php artisan serve” on the terminal
  5. Either copy the link (http://127.0.0.1:8000]) to your browser or click on the link while also pressing ctrl
  6. You should be in the website
  7. Program can be stopped by pressing ctrl + C

Steps to run the Python file 
  1. Go to the folder that has the python file
  2. Open command prompt from there after right click
  3. run “.venv\Scripts\activate”
    a. For people using this the first time, run “pip install flask”, “pip install opencv-python”, and “pip install ultralytics”
  4. run “python.exe .\backboneYOLO.py”
  5. You can access the link (http://127.0.0.1:9000) by copy pasting to your browser or clicking it with ctrl
  6. Program can be stopped by pressing ctrl + C
