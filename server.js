const express = require('express');
const path = require('path');
const app = express();
const PORT = process.env.PORT || 3000;

// Serve static files from the root directory
app.use(express.static(__dirname));

// Basic route for the home page
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.php'));
});

// Note: This Node server will NOT execute PHP code. 
// It is intended for serving static assets or as a base for a Node project.
// To run the PHP application, continue using 'npm run dev' (php -S).

app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
    console.log('NOTE: This node server serves files but does not execute PHP.');
});
