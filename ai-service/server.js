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
 * Useful for verifying if the server is alive after deployment
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
 * Accepts: { "prompt": "your question here" }
 * Returns: { "response": "AI generated answer" }
 */
app.post('/generate', async (req, res) => {
    try {
        const { prompt } = req.body;

        if (!prompt) {
            return res.status(400).json({ error: "Missing 'prompt' in request body" });
        }

        if (!process.env.GEMINI_API_KEY) {
            return res.status(500).json({ error: "GEMINI_API_KEY is not configured on the server" });
        }

        // Generate content using the SDK
        const result = await model.generateContent(prompt);
        const response = await result.response;
        const text = response.text();

        res.json({ response: text });

    } catch (error) {
        console.error('Gemini API Error:', error);

        // Handle specific API errors
        const statusCode = error.status || 500;
        const message = error.message || "An error occurred during AI generation";

        res.status(statusCode).json({ error: message });
    }
});

/**
 * POST /proxy
 * True pass-through for the full Gemini payload.
 */
app.post('/proxy', async (req, res) => {
    try {
        if (!process.env.GEMINI_API_KEY) {
            return res.status(500).json({ error: "GEMINI_API_KEY is not configured" });
        }

        const API_KEY = process.env.GEMINI_API_KEY;
        const MODEL = "gemini-1.5-flash";
        const URL = `https://generativelanguage.googleapis.com/v1beta/models/${MODEL}:generateContent?key=${API_KEY}`;

        // Forward the exact body receive from PHP
        const response = await axios.post(URL, req.body, {
            headers: { 'Content-Type': 'application/json' }
        });

        res.json(response.data);

    } catch (error) {
        console.error('Proxy Error:', error.response ? error.response.data : error.message);
        res.status(error.response ? error.response.status : 500).json(
            error.response ? error.response.data : { error: "Proxy connection failed" }
        );
    }
});

// 5. Start the Server
app.listen(PORT, () => {
    console.log(`\n🚀 Server is running on port ${PORT}`);
    console.log(`🔗 Local URL: http://localhost:${PORT}`);
    console.log(`🛠️ Mode: ES Modules\n`);
});
