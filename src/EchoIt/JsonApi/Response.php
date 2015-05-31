<?php namespace EchoIt\JsonApi;

use Illuminate\Http\JsonResponse;

/**
 * This class contains the parameters to return in the response to an API request.
 *
 * @property array $included included resources
 */
class Response
{
    /**
     * An array of parameters.
     *
     * @var array
     */
    protected $responseData = [];

    /**
     * The main response.
     *
     * @var array|object
     */
    protected $body;

    /**
     * HTTP status code
     *
     * @var int
     */
    protected $httpStatusCode;

    /**
     * Body Singular
     *
     * @var  bool true to set body as a single primary entity
     */
    protected $singularBodyElement = False;

    /**
     * Constructor
     *
     * @param array|object $body
     */
    public function __construct($body, $httpStatusCode = 200)
    {
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * Used to set or overwrite a parameter.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        if ($key == 'body') {
            $this->body = $value;
            return;
        }
        $this->responseData[$key] = $value;
    }

    /**
     * Set body singular
     *
     * Flag that the response body should be a single element as opposed to a collection
     *
     * @return  null
     */
    public function setBodySingular()
    {
        $this->singularBodyElement = True;
    }

    /**
     * Returns a JsonResponse with the set parameters and body.
     *
     * @param  string $bodyKey The key on which to set the main response.
     * @return Illuminate\Http\JsonResponse
     */
    public function toJsonResponse($bodyKey = 'data', $options = 0)
    {
        if ($this->singularBodyElement) {
            $this->body = $this->body->first();
        }

        return new JsonResponse(array_merge(
            [ $bodyKey => $this->body ],
            array_filter($this->responseData)
        ), $this->httpStatusCode, ['Content-Type' => 'application/vnd.api+json'], $options);
    }
}
