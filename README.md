# 📈 ApexMarket — Stock Trading & Portfolio Management Platform

ApexMarket is a professional stock trading and portfolio management platform. It allows users to trade stocks, manage their portfolio, track market performance, deposit/withdraw funds and view full trading history.

🔗 **Live Demo:** [apexmarket.eghedev.com](https://apexmarket.eghedev.com)

---

## 🚀 Features

- 📊 **Live Dashboard** — Real-time portfolio overview and account balance
- 📈 **Markets** — Browse and trade available stocks and assets
- 💼 **Portfolio Management** — Track your holdings and performance
- 💰 **Wallet** — Deposit and withdraw funds securely
- 📋 **Trade History** — Full log of all transactions
- ⚙️ **Account Settings** — Profile and security management
- 🔐 **Secure Authentication** — Login, register with protected sessions
- 📱 **Responsive Design** — Works on desktop and mobile

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP |
| Database | MySQL |
| API | RESTful PHP API |
| Server | Apache (XAMPP) |

---

## 🗄️ Database Setup

1. Import `stock_broker.sql` into your MySQL database via phpMyAdmin
2. Update your DB credentials in `config/database.php`

```php
$host = 'localhost';
$dbname = 'stock_broker';
$username = 'root';
$password = '';
```

---

## ⚙️ Installation

```bash
# Clone the repository
git clone https://github.com/El-Nation/apexmarket.git

# Move to your htdocs folder
cp -r apexmarket /xampp/htdocs/stock_broker

# Import the database
# Open phpMyAdmin → Create DB → Import stock_broker.sql

# Visit in browser
http://localhost/stock_broker
```

---

## 👤 Demo Login

| Field | Value |
|-------|-------|
| Username | demo |
| Password | Demo@1234 |

---

## 📸 Screenshots

> Trading dashboard with portfolio overview, market listings and wallet management.

---

## 👨‍💻 Developer

Built by **El-Nation** — [github.com/El-Nation](https://github.com/El-Nation)
