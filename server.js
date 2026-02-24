const express = require('express');
const path = require('path');
const axios = require('axios');
const app = express();
const PORT = process.env.PORT || 3000;

// Middleware for parsing JSON
app.use(express.json());
// Serve static files from the root directory
app.use(express.static(__dirname));

// --- AI Proxy Endpoint ---
// This allows you to test the AI connection via Node instead of PHP
app.post('/api/ai', async (req, res) => {
    try {
        const { message } = req.body;

        // This is a direct proxy to Gemini v1beta
        // In a real app, MOVE THESE TO .ENV
        const API_KEY = 'AIzaSyBxDe2cpzoliPWBsZAnP-IpPMDft8fD0GQ';
        const MODEL = 'gemini-1.5-flash';
        const URL = `https://generativelanguage.googleapis.com/v1beta/models/${MODEL}:generateContent?key=${API_KEY}`;

        const response = await axios.post(URL, {
            contents: [{
                role: 'user',
                parts: [{ text: message }]
            }],
            generationConfig: {
                temperature: 0.7,
                response_mime_type: 'application/json'
            },
            // Note: Simplistic proxy, doesn't include full context like AiHelper.php
            system_instruction: {
                parts: [{ text: "You are a helpful budget assistant. Answer accurately." }]
            }
        });

        const aiText = response.data.candidates[0].content.parts[0].text;
        res.json({ success: true, data: { message: aiText } });

    } catch (error) {
        console.error('AI Proxy Error:', error.response ? error.response.data : error.message);
        res.status(500).json({ success: false, message: 'AI Proxy failed to connect' });
    }
});

// Basic route for the home page
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.php'));
});

app.listen(PORT, () => {
    console.log(`\n🚀 Node server running on http://localhost:${PORT}`);
    console.log(`🤖 AI Proxy available at http://localhost:${PORT}/api/ai\n`);
    console.log('NOTE: This server serves static files and provides a Node-based AI connection.');
});
