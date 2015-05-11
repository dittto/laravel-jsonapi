<?php
namespace EchoIt\JsonApi;

use EchoIt\JsonApi\ErrorResponse as ApiErrorResponse;
use Illuminate\Http\Request;

/**
 * A class used to represented a client request to the API.
 *
 * @author Ronni Egeriis Persson <ronni@egeriis.me>
 */
class JsonApiRequest
{

    /**
     * Contains an optional model ID from the request
     *
     * @var int
     */
    public $id;

    /**
     * Contains an array of linked resource collections to load
     *
     * @var array
     */
    public $include;

    /**
     * Contains an array of column names to sort on
     *
     * @var array
     */
    public $sort;

    /**
     * Contains an array of key/value pairs to filter on
     *
     * @var array
     */
    public $filter;

    /**
     * Specifies the page number to return results for
     * @var integer
     */
    public $pageNumber;

    /**
     * Specifies the number of results to return per page. Only used if
     * pagination is requested (ie. pageNumber is not null)
     *
     * @var integer
     */
    public $pageSize = 50;

    /**
     * Constructor.
     *
     * @param string $url
     * @param string $method
     * @param int    $id
     * @param mixed $content
     * @param array  $include
     * @param array  $sort
     * @param array  $filter
     * @param integer $pageNumber
     * @param integer $pageSize
     */
    public function __construct(Request $request)
    {
        $this->id = $request->route('id');
        $this->request = $request;
        $this->include = ($i = $request->input('include')) ? explode(',', $i) : [];
        $this->sort = ($i = $request->input('sort')) ? explode(',', $i) : [];
        $this->filter = ($i = $request->except('sort', 'include', 'page')) ? $i : [];
        $this->setPaginationControls();
    }


    /**
     * Set the pagination controls
     * Sets any pagination controls on the request
     */
    protected function setPaginationControls()
    {
        $this->page = $this->request->input('page');

        $pageSize = null;
        $pageNumber = null;
        if($this->page) {
            if(is_array($page) && !empty($page['size']) && !empty($page['number'])) {
                $pageSize = $page['size'];
                $pageNumber = $page['number'];
            } else {
                 throw new ApiErrorResponse(400, 400, 'Expected page[size] and page[number]');
            }
        }
        $this->pageSize = $pageSize;
        $this->pageNumber = $pageNumber;
    }


    /**
     * Parse the data attribute from the request
     *
     * @param string expected data type
     *
     * @return Array passed data for $type
     */
    public function parseData($type)
    {
        $content = json_decode($this->request->getContent(), true);

        if (empty($content['data'])) {
            throw new Exception(
                'Payload either contains misformed JSON or missing "data" parameter.',
                static::ERROR_SCOPE | static::ERROR_INVALID_ATTRS,
                BaseResponse::HTTP_BAD_REQUEST
            );
        }

        $data = $content['data'];
        if (!isset($data['type'])) {
            throw new Exception(
                '"type" parameter not set in request.',
                static::ERROR_SCOPE | static::ERROR_INVALID_ATTRS,
                BaseResponse::HTTP_BAD_REQUEST
            );
        }
        if ($data['type'] !== $type) {
            throw new Exception(
                '"type" parameter is not valid. Expecting ' . $type,
                static::ERROR_SCOPE | static::ERROR_INVALID_ATTRS,
                BaseResponse::HTTP_CONFLICT
            );
        }
        unset($data['type']);
        unset($data['links']);

        return $data;
    }


    /**
     * Parse any links sent with request
     *
     * @return Array passed links object
     */
    public function parseLinks()
    {
        $content = json_decode($this->request->getContent(), true);
        $content = $content['data'];

        if (!isset($content['links']) || empty($content['links'])) {
            return array();
        } else {
            return $content['links'];
        }
    }

    /**
     * act as a transparent layer through to the core request
     */
    public function __call($name, $args)
    {
        return $this->request->{$name}(...$args);
    }

    public function __get($key)
    {
        return $this->request->{$key};
    }
}
