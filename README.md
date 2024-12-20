﻿# Coddict - MCQ Quiz Management System 🎯

![Coddict Banner](./readmeimg/index1.png)

## 📖 Overview

Coddict is a specialized Multiple Choice Question (MCQ) quiz platform designed for conducting preliminary examinations in educational institutions. This system excels in managing objective-type questions, making it perfect for practice tests, preliminary examinations, and academic assessments in colleges and schools.

### ⭐ If you find this project helpful, please consider giving it a star!


## 🚀 Key Features

### For Administrators
- Create and manage MCQ-based quizzes
- Set time limits for examinations
- Define correct answers and scoring patterns
- Monitor student performance
- Generate result reports
- User account management

### For Students
- Easy-to-use MCQ interface
- Real-time countdown timer
- Instant result viewing
- Performance history tracking
- User-friendly navigation

## 💻 Technology Stack

### Frontend
- HTML5
- CSS3
- JavaScript & jQuery
- Bootstrap for responsive design

### Backend
- PHP
- MySQL Database
- Apache Server

## 📸 Screenshots

### Quiz Interface
![Quiz Interface](./readmeimg/quiz1.png)
![Quiz Interface](./readmeimg/quiz2.png)
*Clean and intuitive MCQ quiz interface*

### Admin Control Panel
![Admin Panel](./readmeimg/admin1.png)
![Admin Panel](./readmeimg/admin2.png)
![Admin Panel](./readmeimg/admin3.png)
![Admin Panel](./readmeimg/admin4.png)
![Admin Panel](./readmeimg/admin5.png)
![Admin Panel](./readmeimg/admin6.png)
*Comprehensive quiz management dashboard*

### Results View
![Admin Panel](./readmeimg/admin7.png)
*Detailed result analysis interface*

## 🛠️ Installation Steps

1. Clone the repository
```bash
git clone https://github.com/jerrinm4/coddict.git
```

2. Set up Apache server and configure it to serve the project

3. Import the database
```bash
mysql -u username -p database_name < coddict24.sql
```

4. Update database configuration in `db_con.php`
```php
$conn = new mysqli('localhost', 'root', '', 'dbname');
```

## 🔧 System Requirements

- PHP 5.4+
- MySQL 5.7+
- Apache Web Server
- Modern web browser
- PHP MySQL extension enabled
- PHP Session support enabled

## 👥 User Types

### Administrator
- Create MCQ questions
- Set up quizzes
- Manage student accounts
- View and export results

### Students
- Attempt MCQ quizzes
- View scores immediately
- Track performance history

## 📝 Usage Guide

1. **Setting Up a Quiz (Admin)**
   - Login to admin panel
   - Create new MCQ quiz
   - Add questions with 4 options
   - Set correct answers
   - Define time limit
   - Publish quiz

2. **Taking a Quiz (Student)**
   - Login with student credentials
   - Select available quiz
   - Answer MCQs within time limit
   - Submit and view results

## 🔐 Security Features

- Secure login system
- Session management
- Protected against SQL injection

## 👨‍💻 Developer

Developed and maintained by [Jerrinm4](https://github.com/jerrinm4)

## ⭐ Support This Project

If you find Coddict useful:
- Give it a ⭐ on GitHub
- Share it with your colleagues
- Report any issues you find

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.



---

Made with ❤️ for Educational Institutions

⭐ Don't forget to star this repository if you found it helpful! ⭐