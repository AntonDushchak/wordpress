// API эндпоинт для админки Next.js

export default function handler(req, res) {
  // Устанавливаем CORS заголовки
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

  // Обрабатываем preflight OPTIONS запрос
  if (req.method === 'OPTIONS') {
    res.status(200).end();
    return;
  }

  if (req.method === 'GET') {
    res.status(200).json({
      success: true,
      message: 'Admin API is working!',
      timestamp: new Date().toISOString(),
      endpoints: {
        admin: '/api/admin (GET)',
        applications: '/api/applications (POST)',
        test: '/api/test (GET)'
      }
    });
  } else if (req.method === 'POST') {
    // Обрабатываем POST запросы (например, для настроек)
    console.log('🔧 Admin endpoint received POST request:');
    console.log('Body:', JSON.stringify(req.body, null, 2));
    
    res.status(200).json({
      success: true,
      message: 'Admin request processed',
      received_data: req.body
    });
  } else {
    res.status(405).json({ 
      success: false, 
      message: 'Method not allowed. Use GET or POST.' 
    });
  }
}