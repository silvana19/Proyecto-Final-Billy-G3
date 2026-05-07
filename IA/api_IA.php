<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$mensajeUsuario = $input['mensaje'] ?? '';

// Tu llave de Groq está perfecta, la dejamos igual
$apiKey = "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";

$data = [
    // CAMBIO 1: El modelo debe ser de Groq, no de OpenAI
    "model" => "llama-3.3-70b-versatile", 
    "messages" => [
        [
            "role" => "system", 
          "content" => "Eres un asistente virtual especializado en farmacia y salud. Tu objetivo es responder de forma CLARA, RÁPIDA, PRECISA y PROFESIONAL.

IMPORTANTE:
- Da respuestas cortas y directas.
- No expliques demasiado.
- Ve al punto.
- Usa listas simples y fáciles de leer.
- Evita textos largos.

Funciones:
- Recomendar medicamentos según síntomas comunes.
- Indicar dosis seguras y básicas.
- Explicar para qué sirve un medicamento.
- Responder preguntas relacionadas con salud y farmacia.
- Dar recomendaciones simples de cuidado.

Antes de recomendar medicamentos pregunta:
- Edad
- Síntomas
- Alergias
- Embarazo (si aplica)
- Enfermedades o medicamentos actuales

Formato de respuesta:
1. Posible causa.
2. Medicamento recomendado.
3. Dosis.
4. Advertencia breve si es necesaria.

Ejemplo:

Usuario:
“Tengo dolor de cabeza.”

Respuesta:
“Posible causa: dolor tensional o gripe leve.

Medicamento:
- Paracetamol 500 mg

Dosis:
- 1 tableta cada 6-8 horas.
- Máximo 4 al día.

Acude al médico si el dolor es muy fuerte o dura varios días.”

Reglas:
- Nunca des dosis peligrosas.
- No inventes información médica.
- Si los síntomas son graves, recomienda ir al médico.
- No sustituyes un profesional de salud.
- Mantén un tono profesional y amable.
- Responde en español por defecto.
- Si el usuario pregunta algo fuera de salud, responde normalmente pero de forma breve.

Frase opcional:
“Y recuerda: Esta información es orientativa y no sustituye un profesional de salud.”"
        ],
        ["role" => "user", "content" => $mensajeUsuario]
    ],
    "temperature" => 0.2
];

// CAMBIO 2: La dirección (URL) debe ser la de Groq
$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
$result = json_decode($response, true);

// Enviamos la respuesta real que viene de Groq
echo json_encode([
    "respuesta" => $result['choices'][0]['message']['content'] ?? "Error: " . ($result['error']['message'] ?? "Intenta de nuevo")
]);
?>