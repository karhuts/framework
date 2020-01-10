<?php
declare(strict_types=1);

namespace Karthus\Annotation;

use Karthus\Functions\Strings;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class RequestMapping extends Mapping {
    public const GET = 'GET';

    public const POST = 'POST';

    public const PUT = 'PUT';

    public const PATCH = 'PATCH';

    public const DELETE = 'DELETE';

    public const HEADER = 'HEADER';

    public const OPTIONS = 'OPTIONS';

    /**
     * @var array
     */
    public $methods = ['GET', 'POST'];

    /**
     * RequestMapping constructor.
     *
     * @param null $value
     */
    public function __construct($value = null) {
        parent::__construct($value);
        if (isset($value['methods'])) {
            if (is_string($value['methods'])) {
                // Explode a string to a array
                $this->methods = explode(',', Strings::upper(str_replace(' ', '', $value['methods'])));
            } else {
                $methods = [];
                foreach ($value['methods'] as $method) {
                    $methods[] = Strings::upper(str_replace(' ', '', $method));
                }
                $this->methods = $methods;
            }
        }
    }
}
