<?php
require 'vendor/autoload.php';

// Importa las clases necesarias
use Tectalic\OpenAi\Manager;
use Tectalic\OpenAi\Authentication;
use GuzzleHttp\Client as GuzzleClient;
use Tectalic\OpenAi\Models\ChatCompletions\CreateRequest;

// Crea un cliente para interactuar con la API de OpenAI
function createOpenAiClient()
{
    $openaiClient = Manager::build(
        new GuzzleClient(),
        new Authentication('TU-API-KEY-DE-OPENAI')
    );
    return $openaiClient;
}

// Verifica si el mensaje del usuario contiene palabras clave relacionadas con promociones
function isUserAskingForPromotionsLink($userMessage)
{
    $keywords = ["enlace", "promociones", "link", "promos", "url"];
    foreach ($keywords as $keyword) {
        if (stripos($userMessage, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// Obtiene la respuesta del chatbot utilizando la API de OpenAI
function getResponseFromChatbot($openaiClient, $messages)
{
    $request = new CreateRequest([
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
    ]);
    $response = $openaiClient->chatCompletions()->create($request)->toModel();
    return $response->choices[0]->message->content;
}

// Verifica si la respuesta es válida y contiene palabras clave relacionadas con pollo a la brasa o Roky's
function isResponseValid($response, $userMessage)
{
    $keywords = ["pollo a la brasa", "pollería", "restaurante", "pollo", "brasa", "plato", "comida", "hola", "saludos", "buen día", "buenas tardes", "buenas noches", "ola", "holas", "hola", "saludo", "adios", "nos vemos", "nos vemos roky", "chao", "cuidate", "muchas gracias", "gracias", "ok"];
    foreach ($keywords as $keyword) {
        if (stripos($response, $keyword) !== false) {
            return true;
        }
    }

    if (is_array($response) && isset($response['response'])) {
        $responseContent = $response['response'];
        if (stripos($responseContent, "pollo a la brasa") !== false) {
            return true;
        }
    }
    if (isUserAskingForPromotionsLink($userMessage)) {
        return true;
    }
    return false;
}

// Verifica si la solicitud es de tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Obtiene el mensaje del usuario a partir de la entrada JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = $input['message'] ?? '';

    // Crea el cliente de OpenAI
    $openaiClient = createOpenAiClient();

    // Define los mensajes a enviar a la API de OpenAI
    $messages = [
        [
            'role' => 'system',
            'content' => "¡Hola! Soy Roky's, tu asistente personal. Estoy aquí para ayudarte con todo lo relacionado con el delicioso pollo a la brasa y la pollería Roky's (https://rokys.com/).",
        ],
        [
            'role' => 'assistant',
            'content' => "Roky's es más que un restaurante de pollo a la brasa. Es un lugar donde puedes disfrutar de sabores únicos, atención excepcional y un ambiente acogedor. Estoy aquí para responder todas tus preguntas sobre Roky's, incluyendo las promociones actuales.",
        ],
        [
            'role' => 'assistant',
            'content' => "Nuestro objetivo es brindarte una experiencia deliciosa y satisfactoria en cada visita. Si tienes alguna pregunta sobre nuestros platos, menús, promociones o cualquier otra cosa, no dudes en preguntar. ¡Estoy aquí para ayudarte!",
        ],
        [
            'role' => 'user',
            'content' => $userMessage,
        ],
    ];

    try {
        // Si el usuario pregunta por promociones, devuelve el enlace directamente
        if (isUserAskingForPromotionsLink($userMessage)) {
            $response = [
                'response' => '¡Claro! Para conocer nuestras promociones actuales, visita el siguiente enlace: https://rokys.com/carta/promociones',
            ];
        } else {
            // Si no, utiliza la API de OpenAI para obtener una respuesta
            $response = [
                'response' => getResponseFromChatbot($openaiClient, $messages),
            ];
        }

        // Verifica si la respuesta es válida y devuelve la respuesta apropiada
        if (isResponseValid($response['response'], $userMessage)) {
            echo json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['response' => 'Lo siento, solo puedo responder preguntas relacionadas con pollos a la brasa e información sobre Roky\'s.'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
    } catch (Throwable $error) {
        // Si hay un error, devuelve un mensaje de error y establece el código de estado HTTP a 500
        http_response_code(500);
        echo json_encode(['error' => 'Error al procesar la respuesta del chatbot'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
