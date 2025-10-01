const express = require('express');
const app = express();
const port = 3001; // Используем другой порт для тестирования

// Middleware для парсинга JSON
app.use(express.json());

// CORS headers
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
    
    if (req.method === 'OPTIONS') {
        res.sendStatus(200);
    } else {
        next();
    }
});

// Test endpoint for applications
app.post('/api/applications', (req, res) => {
    console.log('🚀 Received application data:');
    console.log('Headers:', req.headers);
    console.log('Body:', JSON.stringify(req.body, null, 2));
    console.log('---');
    
    // Simulate successful response
    res.status(201).json({
        success: true,
        message: 'Application received successfully',
        application_id: req.body.application_id,
        received_data: {
            basic_fields: req.body.all_form_data ? Object.keys(req.body.all_form_data).length : 0,
            experience_count: req.body.all_form_data?.experience?.length || 0,
            education_count: req.body.all_form_data?.education?.length || 0,
            languages_count: req.body.all_form_data?.languages?.length || 0,
            rights_count: req.body.all_form_data?.rights?.length || 0
        }
    });
});

// Test endpoint
app.get('/test', (req, res) => {
    res.json({ message: 'Server is running!', timestamp: new Date().toISOString() });
});

app.listen(port, () => {
    console.log(`🚀 Test server running on http://localhost:${port}`);
    console.log(`🧪 Test endpoint: http://localhost:${port}/test`);
    console.log(`📝 Applications endpoint: http://localhost:${port}/api/applications`);
});