# Budget Tracker AI

A modern, AI-powered budget tracking system built with PHP and MySQL. Features include expense management, allowance tracking, savings goals, and an AI-powered financial assistant.

## Features

- **AI Budget Assistant**: Chat with your financial data using Gemini AI.
- **Expense Tracking**: Categorize and monitor your spending.
- **Allowance Management**: Track your income and sources of funds.
- **Savings Goals**: Set and monitor your progress towards financial goals.
- **Visual Reports**: Beautiful charts and summaries of your financial health.
- **Modern UI**: Clean, responsive design inspired by iOS and Tailwind CSS.

## Prerequisites

- **PHP**: 7.4 or higher
- **MySQL/MariaDB**
- **Web Server**: Apache (XAMPP/WAMP/MAMP recommended)
- **API Key**: A Gemini API key (optional, for AI features)

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/budget-tracker.git
   cd budget-tracker
   ```

2. **Database Setup**:
   - Create a new database named `budget_tracker` in PHPMyAdmin/MySQL.
   - Import the `database.sql` file provided in the root directory.

3. **Configuration**:
   - Open `includes/config.php`.
   - Set your `AI_PROVIDER` (e.g., 'gemini').
   - Paste your API key in `AI_API_KEY`.
   - Update `includes/db.php` with your database credentials if different from default.

4. **Launch**:
   - Move the project to your web server's root (e.g., `htdocs`).
   - Open `http://localhost/budget-tracker` in your browser.

## Security Note

- **API Keys**: Never commit your real API keys to version control. Use placeholders in the provided config file.
- **Production**: For live deployment, ensure you update `db.php` with secure credentials and disable display errors in PHP.

## License

MIT License. See [LICENSE](LICENSE) (if added) for more details.
