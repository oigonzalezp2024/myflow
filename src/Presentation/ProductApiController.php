<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Application\UseCases\CreateProductUseCase;
use Exception;
use InvalidArgumentException;
use LogicException;

class ProductApiController
{
    private CreateProductUseCase $crearProductoUseCase;

    public function __construct(CreateProductUseCase $crearProductoUseCase)
    {
        $this->crearProductoUseCase = $crearProductoUseCase;
    }

    public function handleRequest(array $requestData): void
    {
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $categoryName = $requestData['category_name'] ?? '';
            $productName = $requestData['product_name'] ?? '';

            [$category, $product] = $this->crearProductoUseCase->ejecutar($categoryName, $productName);

            if (php_sapi_name() !== 'cli') {
                http_response_code(201);
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
            if (php_sapi_name() !== 'cli') {
                http_response_code(400);
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error de validación o negocio',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE) . "\n";
        } catch (Exception $e) {
            if (php_sapi_name() !== 'cli') {
                http_response_code(500);
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}
