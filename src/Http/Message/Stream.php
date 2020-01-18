<?php
declare(strict_types=1);
namespace Karthus\Http\Message;

use Karthus\Spl\SplStream;
use Psr\Http\Message\StreamInterface;

class Stream extends SplStream implements StreamInterface {}
