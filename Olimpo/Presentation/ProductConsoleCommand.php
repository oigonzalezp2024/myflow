<?php

declare(strict_types=1);

namespace Olimpo\Presentation;

use Olimpo\Application\UseCases\CreateProductUseCase;

use Exception;
use InvalidArgumentException;
use LogicException;

class ProductConsoleCommand
{
    private CreateProductUseCase $crearProductoUseCase;

    public function __construct(CreateProductUseCase $crearProductoUseCase)
    {
        $this->crearProductoUseCase = $crearProductoUseCase;
    }

    public function run(array $argv): void
    {
        $categoryName = $argv[1] ?? 'Electrónica';
        $productName = $argv[2] ?? 'Electrolavadora';

        echo "=> Ejecutando comando de consola...\n";
        echo "Categoría: {$categoryName} | Producto: {$productName}\n\n--- Output ---\n";

        try {
            [$category, $product] = $this->crearProductoUseCase->ejecutar($categoryName, $productName);

            echo "¡Proceso exitoso!\n";
            echo "- Categoría ID: " . $category->getId() . " ({$category->getName()})\n";
            echo "- Producto ID: " . $product->getId() . " ({$product->getName()})\n";

        } catch (InvalidArgumentException | LogicException $e) {
            echo "Error de negocio: " . $e->getMessage() . "\n";
            exit(1);
        } catch (Exception $e) {
            echo "Error crítico: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}
