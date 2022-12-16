<?php

declare(strict_types=1);

namespace App\Helper\GoogleBooksApi;

use App\Entity\Book;
use App\Exception\GoogleBookApiResponseError;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author tlefebvre@norsys.fr
 *
 * Call the Google book api to get basic data on a book from its ISBN 10 or 13.
 * Try to reach the cache before the api for performance optimization.
 */
class GoogleBooksRequestHelper
{
    private GoogleBooksCacheHelper $booksCacheHelper;

    private HttpClientInterface $client;

    private ContainerBagInterface $params;

    /**
     * @param GoogleBooksCacheHelper $booksCacheHelper
     * @param HttpClientInterface $client
     * @param ContainerBagInterface $params
     */
    public function __construct(
        GoogleBooksCacheHelper $booksCacheHelper,
        HttpClientInterface    $client,
        ContainerBagInterface  $params
    )
    {
        $this->booksCacheHelper = $booksCacheHelper;
        $this->client = $client;
        $this->params = $params;
    }

    /**
     * @param string|null $isbn
     * @return Book|array
     * @throws Exception
     */
    public function getBookByIsbn(string $isbn = null): Book|array
    {
        $book = $this->booksCacheHelper->getBookCacheData($isbn);

        if (null !== $book) {
            return $book;
        }

        $responseData = $this->getBookResponseData($this->getGoogleBookParameters($isbn));

        try {
            $this->handleGoogleBookResponseData($responseData);
        } catch (GoogleBookApiResponseError $e) {
            return [
                'code' => $e->getCode(),
                'error' => $e->getMessage()
            ];
        }

        $book = $this->formatGoogleBookResponse($responseData);

        $this->booksCacheHelper->addBookToCache($book);

        return $book;
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function getBookResponseData(array $params): array
    {
        try {
            $url = $this->getGoogleBookBaseUrl();
            return json_decode($this->postGoogleBookRequest($url, $params), true);
        } catch (GoogleBookApiResponseError $e) {
            return [
                'code' => $e->getCode(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getGoogleBookBaseUrl(): string
    {
        try {
            return $this->params->get('app.google_book_base_url');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface) {
            throw new Exception('Can\'t find the google book api url parameter. Make sure to add it properly');
        }
    }

    /**
     * @param string $isnb
     * @return array
     */
    private function getGoogleBookParameters(string $isnb): array
    {
        return [
            'q' => $isnb . '+isbn',
            'maxResults' => 1
        ];
    }

    /**
     * @param string $url
     * @param array $params
     * @return string
     * @throws Exception
     */
    private function postGoogleBookRequest(string $url, array $params): string
    {
        try {
            return $this->client->request('GET', $url, [
                'query' => $params,
                'headers' => ['Content-Type: application/json']
            ])->getContent();
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param array $responseData
     * @return void
     * @throws GoogleBookApiResponseError
     */
    private function handleGoogleBookResponseData(array $responseData): void
    {
        if (empty($responseData)) {
            throw new GoogleBookApiResponseError('Google api did not respond.');
        }

        if (array_key_exists('error', $responseData)) {
            throw new GoogleBookApiResponseError($responseData['error']);
        }
    }

    /**
     * @param $responseData
     * @return array
     */
    private function formatGoogleBookResponse($responseData): array
    {
        // result will always be single, but comes in an array of items
        $item = $responseData['items'][0];

        $book['title'] = $item['volumeInfo']['title'];
        $book['subtitle'] = '';
        $book['authors'] = $item['volumeInfo']['authors'];
        $book['publishedDate'] = $item['volumeInfo']['publishedDate'];
        $book['description'] = $item['volumeInfo']['description'];
        $book['shortDescription'] = $item['searchInfo']['textSnippet'];
        $book['pageCount'] = $item['volumeInfo']['pageCount'];
        $book['identifiers'] = $item['volumeInfo']['industryIdentifiers'];
        $book['categories'] = $item['volumeInfo']['categories'];
        $book['thumbnail'] = '';
        $book['smallThumbnail'] = '';

        if (array_key_exists('imageLinks', $item['volumeInfo'])) {
            $book['thumbnail'] = $item['volumeInfo']['imageLinks']['thumbnail'];
            $book['smallThumbnail'] = $item['volumeInfo']['imageLinks']['smallThumbnail'];
        }

        if (array_key_exists('subtitle', $item['volumeInfo'])) {
            $book['subtitle'] = $item['volumeInfo']['subtitle'];
        }

        return $book;
    }
}