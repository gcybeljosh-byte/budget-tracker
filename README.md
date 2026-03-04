# Budget Tracker

A premium, enterprise-grade personal finance ecosystem that transforms spending data into actionable intelligence. Built with PHP, MySQL, and an AI-powered Help Desk for smarter money management.

---

## Project Objectives

### General Objectives
1. **Unified Command Center**: Provide a secure, centralized platform for managing allowances, expenses, savings, and historical financial statements.
2. **Real-time Intelligence**: Empower users with automated daily spending limits via the **Safe-to-Spend** calculation engine.
3. **Data-Driven Focused**: Facilitate zero-based budgeting through automated monthly resets and clear visibility of net performance.
4. **Proactive Management**: Simplify recurring financial obligations with an automated Bills & Subscriptions Hub.
5. **Security First**: Ensure multi-level data protection with a 3-tier role architecture and proactive session guards.
6. **Premium Experience**: Deliver a world-class, mobile-responsive ecosystem with smooth desktop-class animations and a unified design system.

### Specific Outcomes
1. **Precision Tracking**: Full CRUD support for all financial records with decimal-precise accuracy (0.01).
2. **Monthly Statement Engine**: Generate bank-grade summaries of monthly performance and net savings automatically.
3. **Interactive Budgeting**: Category-level spending caps with live visual progress bars and overspend alerts.
4. **Grounded AI Advice**: Context-aware AI Help Desk with access to personal financial data for bespoke guidance.
5. **Goal-Oriented Savings**: Goal-setting engine with target deadlines and intelligent daily contribution requirements.
6. **Visual Habiting**: Comprehensive spending heatmaps and historical trend analytics powered by Chart.js.
7. **Soft-Delete Safety**: Dedicated Recycle Bin for recovering accidentally deleted expenses or financial goals.
8. **Automated Reminders**: Multi-channel notification center for low balances and upcoming bills.

---

## Core Features

### Finance & Ecosystem
1. **Monthly Reset Dashboard**: Consolidated 4-card metric row (Allowance, Expenses, Balance, Safe-to-Spend) that resets every 1st of the month.
2. **Quick Access Hub**: Dedicated navigation bar for fast access to Journal, Bills, Goals, Reports, and Trends.
3. **Statement of Accounts**: Professional-grade page for reviewing historical data with net savings summaries.
4. **AI Help Desk**: Context-aware assistant grounded in your real financial data (Limited to 10 free daily prompts).
5. **Recycle Bin**: Safety net for all financial records—restore anything accidentally deleted with one click.
6. **Precision Expense Engine**: Advanced logging with source-type tracking (Cash, GCash, Maya, Bank).
7. **Safe-to-Spend**: Tells you exactly how much you can spend today without going broke before month-end.

### Planning & Identity
1. **Bills Hub**: Recurring payment tracker (Netflix, Rent, Utilities) with automatic expense logging.
2. **Goal Deep Dive**: Intelligent target tracking with precise daily savings requirement calculations.
3. **Financial Journal**: Double-entry ledger reflections for formal financial transaction logging.
4. **Expense Trends**: Dynamic historical visualizations and category-level HABIT analysis.
5. **Spending Heatmap**: Visual intensity calendar showing exactly when you spend the most.
6. **3-Tier Identity**: Granular access control for Superadmins, Admins, and Regular Users.
7. **Production Hardened**: Built with anti-inspection guards, session timeouts, and encrypted local storage sync.

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
