<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Brevo\Client\Api\TransactionalEmailsApi; // Nuevo import
use Brevo\Client\Configuration; // Nuevo import
use Brevo\Client\Model\SendSmtpEmail; // Nuevo import
use GuzzleHttp\Client; // Para el cliente HTTP
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller {
    public function index(Request $request) {
        $tasks = $request->user()->tasks()->paginate(10);
        return response()->json(['success' => true, 'data' => $tasks]);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'description' => 'nullable|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $task = $request->user()->tasks()->create($request->all());

        // Para Opción B: Enviar email si hay due_date
        if ($task->due_date) {
            $this->sendReminder($task->id); // Llamamos al método de reminder
        }

        return response()->json(['success' => true, 'data' => $task], 201);
    }

    public function show(Request $request, $id) {
        $task = $request->user()->tasks()->findOrFail($id);
        return response()->json(['success' => true, 'data' => $task]);
    }

    public function update(Request $request, $id) {
        $task = $request->user()->tasks()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|max:200',
            'description' => 'nullable|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'status' => 'sometimes|required|in:pending,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $task->update($request->all());
        return response()->json(['success' => true, 'data' => $task]);
    }

    public function destroy(Request $request, $id) {
        $task = $request->user()->tasks()->findOrFail($id);
        $task->delete();
        return response()->json(['success' => true, 'message' => 'Tarea eliminada']);
    }

    // Opción A: API del Clima
    public function weather(Request $request, $id) {
        $task = $request->user()->tasks()->findOrFail($id);

        if (!$task->due_date) {
            return response()->json(['success' => false, 'message' => 'No hay fecha de vencimiento'], 400);
        }

        // Asumimos una ciudad por defecto (ej. Neiva); en producción, agregar parámetro para ubicación.
        $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'q' => 'Neiva',
            'appid' => env('OPENWEATHERMAP_API_KEY'),
            'units' => 'metric',
        ]);

        if ($response->failed()) {
            return response()->json(['success' => false, 'message' => 'Error al obtener clima'], 500);
        }

        $weather = $response->json();
        return response()->json(['success' => true, 'data' => [
            'task' => $task,
            'weather' => [
                'temperature' => $weather['main']['temp'],
                'description' => $weather['weather'][0]['description'],
            ],
        ]]);
    }

    // Opción B: Enviar Recordatorio por Email
    public function sendReminder($id) {
        $task = auth()->user()->tasks()->findOrFail($id);

        if (!$task->due_date) {
            return response()->json(['success' => false, 'message' => 'No hay fecha de vencimiento'], 400);
        }

        // Configuración de Brevo
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));

        $apiInstance = new TransactionalEmailsApi(
            new Client(),
            $config
        );

        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail->setSender(['name' => 'Task Manager', 'email' => 'pruebas.desarrollo.dev98@gmail.com']);
        $sendSmtpEmail->setTo([['email' => auth()->user()->email, 'name' => auth()->user()->name]]);
        $sendSmtpEmail->setSubject("Recordatorio: " . $task->title);
        $sendSmtpEmail->setTextContent("Tienes una tarea pendiente: " . $task->title . "\nVence el: " . $task->due_date);

        try {
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            return response()->json(['success' => true, 'message' => 'Recordatorio enviado']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al enviar email: ' . $e->getMessage()], 500);
        }
    }
}
