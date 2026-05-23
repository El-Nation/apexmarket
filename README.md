# ⚡ APEX MARKETS — Institutional-Grade Crypto Trading Platform

A full-stack trading terminal built with PHP, MySQL, and vanilla JavaScript.
Features live candlestick charts (TradingView), real-time order book and trade feeds
via Binance public API, a complete order matching engine, wallet system, and authentication.

---

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | 8.0+    |
| MySQL       | 5.7+ / MariaDB 10.4+ |
| Web Server  | Apache / Nginx |
| Internet    | For live price feeds (Binance public API) |

---

## 🚀 Quick Setup

### 1. Copy Files to Web Root

```bash
# Apache (Ubuntu/Debian)
cp -r apex-markets/* /var/www/html/

# Or use XAMPP / MAMP / Laragon:
# Copy to:  htdocs/   (XAMPP)
#           www/      (MAMP)
#           www/      (Laragon)
```

### 2. Create the Database

```bash
# Login to MySQL
mysql -u root -p

# Run the schema
mysql -u root -p < /path/to/apex-markets/db/schema.sql
```

Or via phpMyAdmin:
1. Create a new database called `quantum_terminal`
2. Import `db/schema.sql`

### 3. Configure Database Connection

Edit `config/db.php`:

```php
define('DB_HOST', 'localhost');  // your MySQL host
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'quantum_terminal');
define('DB_PORT', 3306);
```

### 4. Web Server Configuration

**Apache** — Create or update `.htaccess` in the root:
```apache
RewriteEngine On
Options -Indexes
```

**Nginx** — Add to your server block:
```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 5. Open in Browser

```
http://localhost/
```

---

## 🔑 Demo Credentials

| Field    | Value             |
|----------|-------------------|
| Username | `demo`            |
| Password | `Demo@1234`       |
| Email    | demo@apexmarkets.com |

The demo account starts with:
- **12,450.80 USDT**
- **0.5241 BTC**
- **2.41 ETH**

---

## 📁 Project Structure

```
apex-markets/
├── index.php               # Entry point (redirects to login or dashboard)
├── login.php               # Authentication page
├── register.php            # User registration
├── logout.php              # Session destruction
├── dashboard.php           # Main trading terminal
├── markets.php             # Live market overview (24 pairs)
├── portfolio.php           # Portfolio analytics & charts
├── history.php             # Order history with filters & pagination
├── wallet.php              # Wallet, deposit, withdraw
│
├── config/
│   ├── db.php              # Database connection & helpers
│   └── session.php         # Auth, CSRF, balance helpers
│
├── api/
│   ├── place_order.php     # Place orders + matching engine
│   ├── cancel_order.php    # Cancel single / all orders
│   ├── get_orders.php      # Fetch user orders (JSON)
│   ├── get_balance.php     # Fetch balances (JSON)
│   ├── deposit.php         # Deposit funds
│   └── withdraw.php        # Withdraw funds
│
├── assets/
│   ├── css/
│   │   ├── main.css        # Full dashboard styles
│   │   └── auth.css        # Login / register styles
│   └── js/
│       └── trading.js      # Live feeds, chart, order logic
│
└── db/
    └── schema.sql          # Full database schema + seed data
```

---

## ✨ Features

### 🔐 Authentication & Security
- Secure login / registration with `password_hash()` (bcrypt, cost 12)
- Session management with `session_regenerate_id()`
- CSRF token protection on all POST endpoints
- HTTP-only session cookies
- Password strength meter on registration

### 📊 Trading Terminal (dashboard.php)
- **TradingView Widget** — Full candlestick chart with drawing tools
- **Functional timeframes**: 1m, 5m, 15m, 1h, 4h, 1D (each switches TradingView interval)
- **Live Order Book** — Real-time Binance depth data, updates every 3s
- **Live Trade Feed** — Recent trades from Binance, updates every 4s
- **Live Ticker** — Price, change, high, low, volume, turnover (every 5s)

### 📋 Order Management
- Limit, Market, Stop order types
- Percentage quick-fill buttons (25%, 50%, 75%, 100%)
- Fee estimation (0.1%)
- Available balance display per side
- **Order matching engine** — Basic price-time priority matching
- Open orders table with cancel functionality
- Order / trade history tabs

### 💰 Wallet System
- Multi-asset balances (USDT, BTC, ETH)
- Deposit modal (instant in demo mode)
- Withdrawal modal with address input
- Transaction history (deposits & withdrawals)
- Real-time balance updates after trades

### 📈 Markets Page
- 12 live trading pairs with Binance ticker data
- Sortable columns (price, change, volume)
- Category filters (Layer 1, DeFi, NFT, Stablecoins)
- Search / filter
- SVG sparkline charts
- Star/favorite pairs (saved to localStorage)
- Global market stats

### 🗂 Portfolio Page
- Live portfolio value (USD)
- Asset allocation donut chart (Chart.js)
- Monthly trading volume bar chart
- Holdings breakdown with percentage bars
- Trade count & fee stats

### 📜 History Page
- Full order history with pagination (25/page)
- Filter by side, status, date range
- Order summary stats (volume, fees, counts)

### 📱 Responsive Design
- Desktop: Full multi-panel layout
- Tablet: Collapsed right panels
- Mobile: Hamburger sidebar, stacked panels, touch-friendly

---

## 🛠 Configuration Options

In `config/db.php`:

```php
define('FEE_RATE', 0.001);          // 0.1% trading fee
define('SESSION_LIFETIME', 7200);   // 2 hours
```

In `api/place_order.php`:
```php
if ($amount > 10) { ... }  // Max order size: 10 BTC (adjust as needed)
```

---

## 🔒 Security Notes

This is a **demo/educational** platform. For production use:

1. **Use HTTPS** — Never run trading platforms over HTTP
2. **Environment variables** — Move DB credentials to `.env`
3. **Rate limiting** — Add rate limiting to API endpoints
4. **Input sanitization** — Already uses PDO prepared statements
5. **2FA** — Implement TOTP (Google Authenticator) for accounts
6. **Email verification** — Require email confirmation on register
7. **Audit logging** — Log all financial transactions

---

## 🐛 Troubleshooting

**Blank page / 500 error:**
- Enable PHP errors: Add `ini_set('display_errors', 1);` to `config/db.php`
- Check MySQL credentials in `config/db.php`

**"Database unavailable":**
- Ensure MySQL is running
- Run `db/schema.sql` to create tables

**Charts not loading:**
- Check browser console for errors
- TradingView widget requires internet access
- Live prices require Binance API access (no API key needed, just internet)

**Order book empty:**
- Binance API may be rate-limited. Wait 60 seconds and refresh.

---

## 📦 Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | PHP 8 (PDO, sessions, CSRF)       |
| Database   | MySQL / MariaDB                   |
| Charts     | TradingView Widget (candlesticks) |
| Analytics  | Chart.js 4 (portfolio/volume)     |
| Price Data | Binance Public REST API (free)    |
| Frontend   | Vanilla JS, CSS3 Grid/Flex        |
| Fonts      | Rajdhani, IBM Plex Mono, Inter    |

---

## 📄 License

For educational and demonstration purposes.
Not intended for real financial trading.
