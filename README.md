# Budget Tracker

A premium, enterprise-grade personal finance ecosystem that transforms spending data into actionable intelligence. Built with PHP, MySQL, and an AI-powered Help Desk for smarter money management.

---

## Project Objectives

### General Goals
1. Provide a unified platform for managing allowances, expenses, savings, and historical statements.
2. Empower users with real-time monthly tracking through automated dashboard resets and past-performance reviews.
3. Proactively manage recurring costs with the Bills & Subscriptions Hub.
4. Deliver real-time daily spending intelligence via the Safe-to-Spend Calculator.
5. Ensure multi-level security with a 3-tier role system and proactive session timeouts.
6. Deliver a premium, mobile-responsive experience with smooth desktop-class animations and a Unified Dashboard Hub.

### Specific Outcomes
1. Full CRUD support for allowances, expenses, and savings with decimal-precise tracking.
2. Real-time Dashboard with Monthly Resets for focused financial monitoring.
3. Statement of Accounts engine for reviewing historical monthly summaries and net performance.
4. Category-level budget limits with interactive progress tracking and AI-suggested limit plans.
5. Goal-setting engine with target deadlines, contribution logs, and daily savings requirements.
6. Visual analytics powered by Chart.js and day-intensity heatmaps for spending habits.
7. Context-aware AI Help Desk (Gemini/OpenAI) with grounded data access and 10-minute inactivity timeout.
8. Professional double-entry Financial Journal with AI-driven reflection and compound entry generation.
9. Multi-currency architecture supporting global formatting and symbol-aware display.
10. Hardened security via .htaccess protection, anti-inspection measures, Google OAuth, and session guards.
11. Bills & Subscriptions Hub with Automated Tracker for recurring payments.
12. Safe-to-Spend Calculator for real-time daily spending intelligence.

---

## Key Features

### Finance & Budgeting
1. Monthly Reset Dashboard: Features a consolidated 4-card metric row (Allowance, Expenses, Balance, Safe-to-Spend) that resets every 1st of the month.
2. Quick Access Hub: Dedicated horizontal bar for fast navigation to Journal, Bills, Goals, Reports, and Trends.
3. Statement of Accounts: Dedicated page for reviewing historical data (Income vs Spent) with net savings summaries.
4. Precision Expense Engine: Log expenses with decimal support (0.01) across multiple payment sources (Cash, GCash, Maya, Bank).
5. AI Budget Planner: Automated suggestions for category limits based on your allowance and 3-month spending history.
6. Budget Limits: Monthly spending caps per category with live color-coded progress bars.
7. Allowance Tracker: Track recurring income and receive AI-driven plan prompts after every addition.
8. Savings Integration: Dedicated savings tracking with automatic allowance synchronization.

### Planning & Insights
1. Bills Hub: Automated tracker for recurring payments (Rent, Netflix, Utilities) that logs expenses and calculates next due dates.
2. Safe-to-Spend Calculator: Real-time intelligent card showing exactly how much can be safely spent today.
3. Financial Goal Deep Dive: Target tracking with intelligent daily savings requirement calculations.
4. Financial Journal: AI-assisted ledger reflections with overspending detection.
5. Expense Trends: Historical spending visualizations and monthly comparison.
6. Spending Heatmap: Visual intensity calendar of daily spending habits.

### AI Help Desk
1. Context-Aware Assistant: Floating FAB grounded in your real financial data.
2. Privacy First: Automatically clears chat history after 10 minutes of inactivity.
3. Smart Allowance Support: Grounds advice in your monthly allowance and spending context.
4. Smart Commands: Log expenses or create goals naturally through chat.

### Security & Admin
1. Hardened Infrastructure: Directory-level .htaccess protection and client-side anti-inspection logic.
2. Identity: One-tap Google OAuth login and multi-tier security guards.
3. Access Control: 3-tier roles (Superadmin, Admin, User) with session guards.
4. Admin Panel: User management, activity logs, and credential visibility.
5. Your Wallets: Sidebar widget for all-time balances (Cash, Digital, Savings) distinct from monthly metrics.

---

## Technical Stack

| Layer | Technology |
|---|---|
| Core | PHP 8.x + MySQL / MariaDB |
| Design | Bootstrap 5 + Vanilla CSS (iOS Design System) |
| Analytics | Chart.js + DataTables 1.13 |
| AI | Google Gemini AI / OpenAI API |
| Authentication | Google Identity Services |
| Feedback | SweetAlert2 + FontAwesome 6 |

---

## Installation

1. Database: Configure credentials in includes/db.php. Schema migrations run automatically.
2. AI Config: Set your API key and provider (Gemini/OpenAI) in includes/config.php.
3. Google Auth: Add your Client ID in login.php and register.php.

---

## Project Architecture

1. api: REST endpoints for financial data and analytics.
2. auth: Authentication handlers and Google OAuth logic.
3. core: Main application pages and feature modules.
4. admin: System administration and user management.
5. includes: Database, AI core, and system security modules.
6. assets: Design system, tokens, and client-side logic.

---

## About the System

Budget Tracker is a capstone-grade personal finance management system developed as a full-stack web application.

Developer: Cybel Josh A. Gamido 
Institution: University of Southern Mindanao (USM) 
Contact: gcybeljosh@gmail.com 
Version: v2.5.0
Stack: PHP, MySQL, Gemini AI, Bootstrap 5 

### System Highlights
1. Grounded AI: Assistant specifically trained on user financial data points.
2. Self-Healing: Automatic database migrations via schema detection.
3. Premium UI: Unified design system with stagger animations and page fades.
4. Multi-Currency: Global support for PHP, USD, EUR, and custom symbols.
5. Spending Intelligence: Real-time risk assessment and daily spending limits.
6. AI Robustness: Fixed simulation mode response handling and hardened financial stats logic.
7. Clean Architecture: Streamlined repository structure by removing redundant debug and utility scripts.

Designed & Engineered by Cybel Josh A. Gamido — University of Southern Mindanao (USM).
