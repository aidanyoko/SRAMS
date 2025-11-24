Study Room Availability Monitor (SRAM)

SRAM is a website 

Stack
Frontend - Laravel blade, Tailwind, JavaScript
Backend - Laravel PHP, Node.js, MySQL, Python

Features
Room Detection with an AI and Camera System by Python
Booking seats in a classroom
User Log in & Registration
Real time people detection system

Programs Needed to run the website:

Browser ( Ex: Google Chrome)
VS Code
Python
Pip
Laravel
PHP 8.2+
MySQL
Node js
Npm install
Git

Important Folders
SRAMDB
app
http/controllers
LoginController
RegisterController
RoomController
resources
css
app.css
js
app.js
bootstrap.js
Views
Index.blade.php
Login.blade.php
register.blade.php
routes
console.php
web.php
env


Steps on installing Python
Open your web browser
Go to Python.org and download
From the downloads tab in the menu, click on your system such as whether you are using Windows or Mac
Click on the latest Python Install Manager to download 
Install Python from the install manager exe
You should have Python on your computer

Installations
Download git from https://git-scm.com/install/
Download Node.js from https://nodejs.org/en/download
Download the project through zip from Github
Run composer install and npm install from command promt on VS code on the laravel project
Python - https://www.python.org/downloads/
MySQL - “https://dev.mysql.com/downloads/workbench/”
PHP  - https://www.php.net/downloads.php
PIP
Download the script from  https://bootstrap.pypa.io/get-pip.py
Open Command prompt, cd to the folder containing get-pip.py
Run “py get-pip.py”
VS code - https://code.visualstudio.com/download


Steps for installing MySQL
Open your browser
Go to MySQL or the link “https://dev.mysql.com/downloads/workbench/”
Download and install MySQL Workbench for your OS and system
Open MySQL Workbench
Create a connection with a host name and password
Go to a SQL tab and enter (CREATE DATABASE ‘database name’)
Go to the laravel project in VS code
Go to the env file
Edit DB connection as ‘mysql’, and write the DB username and password to the one for your SQL server 
Run php artisan migrate in the command prompt of VS code
The database and user accounts should start running

Steps to run the website in VS Code

Go to the directory of the project
Create a new terminal
Open the command prompt terminal
Run “php artisan serve” on the terminal
Either copy the link (http://127.0.0.1:8000]) to your browser or click on the link while also pressing ctrl
You should be in the website
Program can be stopped by pressing ctrl + C



Steps to run the Python file 
Go to the folder that has the python file
Open command prompt from there after right click
run “.venv\Scripts\activate”
For people using this the first time, run “pip install flask”, “pip install opencv-python”, and “pip install ultralytics”
run “python.exe .\backboneYOLO.py”
You can access the link (http://127.0.0.1:9000) by copy pasting to your browser or clicking it with ctrl
Program can be stopped by pressing ctrl + C

