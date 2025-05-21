# Online Exam System

A robust web-based examination platform designed to facilitate multiple-choice question (MCQ) assessments. This system enables secure, timed online exams with automatic scoring and result management.

## Key Features

- **Multiple-Choice Questions Only:** Focused on objective assessment for streamlined evaluation.
- **CSV-Based Question Upload:** Easily import bulk questions via CSV files to populate the exam database.
- **Timed Exams:** Configurable time limits to ensure standardized testing conditions.
- **Automated Scoring:** Instant evaluation and result presentation upon exam completion.
- **User-Friendly Interface:** Clean and intuitive design leveraging PHP, MySQL, HTML, and JavaScript.

## Technology Stack

- Backend: PHP (server-side logic and database interaction)  
- Database: MySQL (data storage for questions, users, and results)  
- Frontend: HTML5, CSS3, JavaScript (responsive and interactive UI)  

Database Setup
Import the provided SQL schema into your MySQL database to create the necessary tables and relations.

Configure Environment
Update the database configuration parameters (host, username, password, database name) in the configuration file (config.php or equivalent).

Deploy the Application
Host the project on a PHP-enabled web server such as XAMPP, WAMP, MAMP, or a Linux LAMP stack.

Access the System
Open the web application in a browser and begin managing exams.

Usage Guidelines
Administrator Role: Upload MCQ question sets via CSV files to build or update the exam question pool.

Candidate Role: Participate in timed examinations and receive automated scoring with detailed results.

Scalability: Designed to support multiple exams and users concurrently with minimal configuration.

Contribution and Support
Contributions, bug reports, and feature requests are highly encouraged. Please fork the repository and submit a pull request with descriptive comments. For support or inquiries, open an issue or contact the maintainer directly.
