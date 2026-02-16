export class DataManager {
    constructor() {
        this.apiUrl = 'api';
    }

    // --- Allowance Methods ---

    async getAllowances() {
        try {
            const response = await fetch(`${this.apiUrl}/allowance.php?t=${new Date().getTime()}`);
            const json = await response.json();
            return json.success ? json.data : [];
        } catch (error) {
            console.error("Error fetching allowances:", error);
            return [];
        }
    }

    async addAllowance(allowance) {
        try {
            const response = await fetch(`${this.apiUrl}/allowance.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(allowance)
            });
            return await response.json();
        } catch (error) {
            console.error("Error adding allowance:", error);
        }
    }

    async deleteAllowance(id) {
        try {
            const response = await fetch(`${this.apiUrl}/allowance.php`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            });
            return await response.json();
        } catch (error) {
            console.error("Error deleting allowance:", error);
        }
    }

    // --- Expense Methods ---

    async getExpenses() {
        try {
            const response = await fetch(`${this.apiUrl}/expenses.php?t=${new Date().getTime()}`);
            const json = await response.json();
            return json.success ? json.data : [];
        } catch (error) {
            console.error("Error fetching expenses:", error);
            return [];
        }
    }

    async addExpense(expense) {
        try {
            const response = await fetch(`${this.apiUrl}/expenses.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(expense)
            });
            return await response.json();
        } catch (error) {
            console.error("Error adding expense:", error);
        }
    }

    async deleteExpense(id) {
        try {
            const response = await fetch(`${this.apiUrl}/expenses.php`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            });
            return await response.json();
        } catch (error) {
            console.error("Error deleting expense:", error);
        }
    }

    async updateAllowance(allowance) {
        try {
            const response = await fetch(`${this.apiUrl}/allowance.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(allowance)
            });
            return await response.json();
        } catch (error) {
            console.error("Error updating allowance:", error);
        }
    }

    async updateExpense(expense) {
        try {
            const response = await fetch(`${this.apiUrl}/expenses.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(expense)
            });
            return await response.json();
        } catch (error) {
            console.error("Error updating expense:", error);
        }
    }

    // --- Helper Methods for Filtering ---

    async getExpensesByDateRange(monthYear) {
        // Fetch all and filter client-side for simplicity, or implement backend filtering
        const allExpenses = await this.getExpenses();
        if (!monthYear) return allExpenses;

        return allExpenses.filter(expense => {
            return expense.date.startsWith(monthYear);
        });
    }

    async getAllowancesByDateRange(monthYear) {
        const allAllowances = await this.getAllowances();
        if (!monthYear) return allAllowances;

        return allAllowances.filter(allowance => {
            return allowance.date.startsWith(monthYear);
        });
    }
}
