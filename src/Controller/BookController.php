<?php

/**
 * @author tldev
 */

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    #[Route('/api/books/{id}')]
    public function getBook($id): JsonResponse
    {
        $book = [
            'id' => $id,
            'name' => 'TEST POUR AMEL',
            'url' => 'https://symfonycasts.s3.amazonaws.com/sample.mp3',
        ];

        return $this->json($book);
    }
}