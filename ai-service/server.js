import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { GoogleGenerativeAI } from '@google/generative-ai';

// 1. Configure Environment Variables
dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

// 2. Setup Middleware
app.use(cors()); // Enable CORS for cross-origin requests
app.use(express.json()); // Allow JSON parsing in request bodies

// 3. Initialize Google Gemini AI
// Ensure you set GEMINI_API_KEY in your Render.com environment variables
const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY || '');
const model = genAI.getGenerativeModel({ model: "gemini-1.5-flash" });

// 4. API Endpoints

/**
 * Health Check Endpoint
 */
app.get('/', (req, res) => {
    res.json({
        status: "alive",
        service: "Gemini AI Backend",
        apiKeyConfigured: !!process.env.GEMINI_API_KEY
    });
});

/**
 * POST /generate
 * Standard generation using SDK
 */
app.post('/generate', async (req, res) => {
    try {
        const { prompt } = req.body;
        if (!prompt) return res.status(400).json({ error: "Missing 'prompt'" });
        if (!process.env.GEMINI_API_KEY) return res.status(500).json({ error: "API Key not set" });

        const result = await model.generateContent(prompt);
        const response = await result.response;
        res.json({ response: response.text() });
    } catch (error) {
        console.error('Gemini SDK Error:', error);
        res.status(500).json({ error: error.message });
    }
});

/**
 * POST /proxy
 * True pass-through using native FETCH (Fixes axios errors)
 */
app.post('/proxy', async (req, res) => {
    try {
        if (!process.env.GEMINI_API_KEY) {
            return res.status(500).json({ error: "GEMINI_API_KEY is not configured" });
        }

        const API_KEY = process.env.GEMINI_API_KEY;
        const MODEL = "gemini-1.5-flash";
        const URL = `https://generativelanguage.googleapis.com/v1/models/${MODEL}:generateContent?key=${API_KEY}`;

        // Forward using native Node fetch
        const response = await fetch(URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(req.body)
        });

        const data = await response.json();
        res.status(response.status).json(data);

    } catch (error) {
        console.error('Proxy Error:', error.message);
        res.status(500).json({ error: "Proxy connection failed: " + error.message });
    }
});

// 5. Start the Server
app.listen(PORT, () => {
    console.log(`\n🚀 AI Backend running on port ${PORT} (Using native fetch)\n`);
});
