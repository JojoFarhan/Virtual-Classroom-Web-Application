# 📚 Virtual Classroom Web Application

A full-featured web-based virtual classroom platform built using **HTML**, **CSS**, **JavaScript**, **PHP**, and **MySQL** — inspired by platforms like Google Classroom. It allows students and instructors to collaborate, manage coursework, and communicate in a digital environment.

---

## ✨ Features

- 👩‍🏫 Instructor Dashboard
  - Create and manage courses
  - Upload materials and assignments
  - Grade student submissions

- 👨‍🎓 Student Dashboard
  - Enroll in courses
  - Submit assignments
  - View grades and feedback

- 🧑‍💼 Admin Panel
  - Manage users (students/instructors)
  - View system reports

- 📦 Additional Features
  - User authentication and role-based access
  - Discussion forums per course
  - Responsive design for mobile & desktop
  - File upload for assignments

---

## 🛠️ Built With

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP (Procedural + OOP)
- **Database**: MySQL
- **Architecture**: MVC-inspired modular structure

---

## 📁 Project Structure

virtual-classroom/
├── config/ # DB and site configuration
├── includes/ # Common headers, footers, auth
├── assets/ # CSS, JS, images
├── classes/ # PHP classes (User, Course, etc.)
├── admin/ # Admin dashboard pages
├── teacher/ # Teacher dashboard pages
├── student/ # Student dashboard pages
├── login.php # Login page
├── register.php # Registration page
├── index.php # Home page
├── logout.php # Logout script
├── database.sql # MySQL database schema
├── configuration_files # Structure of the files and folders


---

## ⚙️ Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/JojoFarhan/Virtual-Classroom-Web-Application.git
cd virtual-classroom

**2. Setup the Database**
Import database.sql into your MySQL server:
CREATE DATABASE virtual_classroom;
USE virtual_classroom;
-- Paste contents of database.sql here

**3. Configure the Database Connection**
Edit config/database.php and config/config.php with your database credentials:
define('DB_HOST', 'localhost');
define('DB_NAME', 'virtual_classroom');
define('DB_USER', 'root');
define('DB_PASS', '');

**4. Run Locally**
Start Apache (XAMPP/WAMP) and go to:
http://localhost/virtual-classroom/


🧪 Testing
Manual Testing
Manual test cases have been designed and executed to verify key functionality including login, course creation, assignment submission, and grading.

Unit Testing (Optional)
If needed, unit tests can be created using PHPUnit to test backend logic inside /classes/.

📷 Screenshots
![homepage](https://github.com/user-attachments/assets/9ff1c463-d8ad-4751-b3d4-9a8371c0fc08)
![login_page](https://github.com/user-attachments/assets/bfd8a93e-467c-428d-aa6f-715d497547ab)
![Register](https://github.com/user-attachments/assets/be453525-f255-4892-84d5-652e33973bf9)
![admin_dashboard](https://github.com/user-attachments/assets/3440be0b-0767-4bb6-97a6-600be398e8f4)
![student_dashboard](https://github.com/user-attachments/assets/ded7d30c-461e-4c51-bfaf-fb0f58b2ae65)
![Teacher_dashboard](https://github.com/user-attachments/assets/cae7dfda-cc27-4110-83d9-ca99d9df77cc)


🛡️ License
This project is for educational use under the MIT License.

🙏 Acknowledgements
Inspired by Google Classroom

Designed as part of CSE 406 - Integrated Design Project I & II at Green University of Bangladesh

💬 Feedback
Pull requests and suggestions are welcome!
Feel free to fork and customize it for your own learning management system.

---

### ✅ What You Should Do:
- Replace `your-username` in the clone link.
- Add screenshots inside a `/screenshots/` folder.
- Optionally add a `LICENSE` file (MIT is standard for educational projects).
- Update the **features** if you added more (e.g., live video, chat).
