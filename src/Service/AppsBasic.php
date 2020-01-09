<?php
declare(strict_types=1);
namespace Karthus\Service;

/**
 * Interface AppsBasic
 *
 * @package Service
 */
interface AppsBasic{
    public function authentication();
    public function done();
    public function execute();
    public function init();
}
