<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\GoogleBooksApi\GoogleBooksRequestHelper;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author tlefebvre@norsys.fr
 *
 * Expose a GET route to retrieve a book's data from its ISBN10 or 13
 */
class BookController extends AbstractController
{
    private GoogleBooksRequestHelper $googleBooksHelper;

    /**
     * @param GoogleBooksRequestHelper $googleBooksHelper
     */
    public function __construct(GoogleBooksRequestHelper $googleBooksHelper)
    {
        $this->googleBooksHelper = $googleBooksHelper;
    }

    /**
     * @param string $isbn
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/api/books/{isbn}', name: 'app_book_show', methods: ['GET'])]
    public function show(string $isbn): JsonResponse
    {
        $book = $this->googleBooksHelper->getBookByIsbn($this->sanitizeIsbn($isbn));

        return $this->json($book);
    }

    /**
     * @param string $isbn
     * @return string
     */
    private function sanitizeIsbn(string $isbn): string
    {
        return trim(strtolower($isbn));
    }
}