<?php

namespace Scrape;

class Config
{
    private string $city;

    private int $year;

    public function __construct()
    {
        $options = getopt('c:y:', ['city:', 'year:']);

        if (isset($options['city'])) {
            $this->city = trim($options['city']);
        } elseif (isset($options['c'])) {
            $this->city = trim($options['c']);
        } else {
            throw new \Exception('Parameter `city` is required.');
        }

        if (isset($options['year']) && is_numeric($options['year']) && $options['year'] > 0) {
            $this->year = $options['year'];
        } elseif (isset($options['y']) && is_numeric($options['y']) && $options['y'] > 0) {
            $this->year = $options['y'];
        } else {
            throw new \Exception('Parameter `year` is invalid.');
        }
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getYear()
    {
        return $this->year;
    }
}
