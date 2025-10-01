// Простой тестовый эндпоинт для проверки соединения

export default function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

  if (req.method === 'OPTIONS') {
    res.status(200).end();
    return;
  }

  if (req.method === 'GET') {
    res.status(200).json({
      success: true,
      message: 'API is working!',
      timestamp: new Date().toISOString(),
      endpoints: {
        applications: '/api/applications (POST)',
        test: '/api/test (GET)'
      }
    });
  } else {
    res.status(405).json({ 
      success: false, 
      message: 'Method not allowed. Use GET.' 
    });
  }
}