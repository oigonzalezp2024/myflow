<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Application\UseCases\CreateProductUseCase;
use Throwable;
use InvalidArgumentException;
use LogicException;

class ProductApiController
{
    private CreateProductUseCase $crearProductoUseCase;

    public function __construct(CreateProductUseCase $crearProductoUseCase)
    {
        $this->crearProductoUseCase = $crearProductoUseCase;
    }

    /**
     * Maneja la petición HTTP REST (o recibe un array opcional para pruebas/CLI).
     */
    public function handleRequest(?array $requestData = null): void
    {
        $isCli = (php_sapi_name() === 'cli');

        if (!$isCli) {
            header('Content-Type: application/json; charset=utf-8');
            
            // Validar que la petición sea POST (buena práctica REST)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405); // Method Not Allowed
                echo json_encode([
                    'success' => false,
                    'error' => 'Método no permitido',
                    'message' => 'Utiliza el método POST para este endpoint.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        // Si no se pasan datos por parámetro (entorno web real), 
        // la propia clase se encarga de leer y decodificar el JSON de la petición HTTP.
        if ($requestData === null) {
            $rawInput = file_get_contents('php://input');
            $requestData = json_decode($rawInput, true);

            // Si el cuerpo no traía un JSON válido, respaldamos con $_POST si aplica
            if (!is_array($requestData)) {
                $requestData = $_POST;
            }
        }

        try {
            $categoryName = trim($requestData['category_name'] ?? '');
            $productName = trim($requestData['product_name'] ?? '');

            // Validación rápida de campos vacíos
            if ($categoryName === '' || $productName === '') {
                throw new InvalidArgumentException('Los campos category_name y product_name son obligatorios.');
            }

            [$category, $product] = $this->crearProductoUseCase->ejecutar($categoryName, $productName);

            if (!$isCli) {
                http_response_code(201); // 201 Created
            }

            echo json_encode([
                'success' => true,
                'message' => 'Producto creado con éxito',
                'data' => [
                    'category' => ['id' => $category->getId(), 'name' => $category->getName()],
                    'product' => ['id' => $product->getId(), 'name' => $product->getName()]
                ]
            ], JSON_UNESCAPED_UNICODE) . "\n";

        } catch (InvalidArgumentException | LogicException $e) {
            if (!$isCli) {
                http_response_code(400); // Bad Request
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error de validación o negocio',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE) . "\n";

        } catch (Throwable $e) {
            if (!$isCli) {
                http_response_code(500); // Internal Server Error
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}
