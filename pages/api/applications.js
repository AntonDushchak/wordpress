// Простой тестовый API эндпоинт для приема заявок

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

  if (req.method === 'POST') {
    console.log('🚀 Received job application:');
    console.log('Headers:', req.headers);
    console.log('Body:', JSON.stringify(req.body, null, 2));
    
    // Проверяем данные
    const data = req.body;
    const stats = {
      application_id: data.application_id || 'unknown',
      has_basic_data: !!(data.all_form_data?.full_name || data.all_form_data?.first_name),
      experience_count: data.all_form_data?.experience?.length || 0,
      education_count: data.all_form_data?.education?.length || 0,
      languages_count: data.all_form_data?.languages?.length || 0,
      rights_count: data.all_form_data?.rights?.length || 0,
      custom_fields_count: Object.keys(data.all_form_data?.custom_fields || {}).length,
      received_at: new Date().toISOString()
    };
    
    console.log('📊 Application stats:', stats);
    
    // Возвращаем успешный ответ
    res.status(201).json({
      success: true,
      message: 'Job application received successfully',
      data: stats
    });
  } else {
    res.status(405).json({ 
      success: false, 
      message: 'Method not allowed. Use POST.' 
    });
  }
}