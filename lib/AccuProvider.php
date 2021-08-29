<?php

namespace Scrape;

class AccuProvider
{
    private const BASE_URL = 'https://www.accuweather.com';

    private const MONTHS = [
        'january',
        'february',
        'march',
        'april',
        'may',
        'june',
        'july',
        'august',
        'september',
        'october',
        'november',
        'december',
    ];

    private string $city;

    private int $cityCode;

    private string $cityUrl;

    private string $country;

    public function __construct(string $city)
    {
        if (!$this->initCity($city)) {
            throw new \Exception("Could not find city `$city`", 404);
        }
    }

    public function getWeather(int $yearStart, int $yearEnd = null): array
    {
        $date = new \DateTime();
        if (!$yearEnd || $yearEnd > $date->format('Y')) {
            $yearEnd = $yearEnd ?? $date->format('Y');
        }

        $data = [];
        for ($year = $yearStart; $year <= $yearEnd; $year++) {
            $data[] = $this->getWeatherForYear($year, $date);
        }

        return $data ? array_merge(...$data) : [];
    }

    private function getWeatherForYear(int $year, \DateTime $today): array
    {
        $data = [];
        $lastMonth = $year == $today->format('Y') ? $today->format('m') - 1 : 11;
        foreach (self::MONTHS as $month => $monthName) {
            if ($month > $lastMonth) {
                break;
            }

            $url = implode('/', [
                    self::BASE_URL,
                    'en',
                    $this->country,
                    $this->cityUrl,
                    $this->cityCode,
                    $monthName.'-weather',
                    $this->cityCode,
                ]).'?year='.$year;
            $xpath = $this->getXpath($url);
            $nodes = $xpath->query('//div[@class="monthly-calendar"]/a');

            $currentDay = 1;
            foreach ($nodes as $node) {
                $day = (int)$node->childNodes->item(1)->nodeValue;
                if ($day === $currentDay) {
                    if ($month === $lastMonth && $day > $today->format('d')) {
                        break;
                    }

                    $currentDay++;
                    $data[] = [
                        'date' => sprintf('%04d-%02d-%02d', $year, $month + 1, $day),
                        'city' => $this->city,
                        'high_temp' => (int)$node->childNodes->item(3)->childNodes->item(1)->nodeValue,
                        'low_temp' => (int)$node->childNodes->item(3)->childNodes->item(3)->nodeValue,
                    ];
                } elseif ($day < $currentDay) {
                    break;
                }
            }
        }

        return $data;
    }

    private function initCity(string $city): bool
    {
        $url = self::BASE_URL.'/en/search-locations?query='.$city;
        $xpath = $this->getXpath($url);

        if ($xpath->query('//div[@class="content-module"]/div[@class="search-results-heading"]')->count()) {
            $nodes = $xpath->query('//div[@class="content-module"]/div/a');
            $link = $nodes->item(0)->getAttribute('href');

            $headers = $this->execCurl(self::BASE_URL.$link, '-I');
            $pattern = '#location: /en/(?<country>\w+)/(?<city_url>\w+)/(1-)*(?<code>\d+)(_1_al)*/weather-forecast#sm';

            if (preg_match($pattern, $headers, $match)) {
                $this->city = ucwords($city);
                $this->cityCode = $match['code'];
                $this->cityUrl = $match['city_url'];
                $this->country = $match['country'];

                return true;
            }
        }

        return false;
    }

    private function execCurl(string $url, string $options = ''): string
    {
        $command = implode(' ', [
            'curl',
            $options,
            '-s',
            '-H "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) '.
            'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36" ',
            $url,
        ]);
        exec($command, $result);

        return implode("\n", $result);
    }

    private function getXpath(string $url): \DOMXPath
    {
        $result = $this->execCurl($url);
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($result);

        return new \DOMXPath($doc);
    }
}
