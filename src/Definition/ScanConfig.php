<?php
declare(strict_types=1);
namespace Karthus\Definition;

class ScanConfig {
    /**
     * @var array
     */
    protected $dirs;
    /**
     * @var array
     */
    protected $ignoreAnnotations;
    /**
     * @var array
     */
    protected $collectors;

    /**
     * ScanConfig constructor.
     *
     * @param array $dirs
     * @param array $ignoreAnnotations
     * @param array $collectors
     */
    public function __construct(array $dirs = [], array $ignoreAnnotations = [], array $collectors = []) {
        $this->dirs = $dirs;
        $this->ignoreAnnotations = $ignoreAnnotations;
        $this->collectors = $collectors;
    }

    /**
     * @return array
     */
    public function getDirs(): array {
        return $this->dirs;
    }

    /**
     * @param array $dirs
     * @return $this
     */
    public function setDirs(array $dirs): self {
        $this->dirs = $dirs;
        return $this;
    }

    /**
     * @return array
     */
    public function getIgnoreAnnotations(): array {
        return $this->ignoreAnnotations;
    }

    /**
     * @param array $ignoreAnnotations
     * @return $this
     */
    public function setIgnoreAnnotations(array $ignoreAnnotations): self {
        $this->ignoreAnnotations = $ignoreAnnotations;
        return $this;
    }

    /**
     * @return array
     */
    public function getCollectors(): array {
        return $this->collectors;
    }

    /**
     * @param array $collectors
     * @return $this
     */
    public function setCollectors(array $collectors): self {
        $this->collectors = $collectors;
        return $this;
    }
}
