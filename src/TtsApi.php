<?php

/** Реализация URL из документации
 * https://tts.voicetech.yandex.net/generate? 
 * key=<API‑ключ>
 * & text=<текст>
 * & format=<mp3|wav|opus>
 * & [quality=<hi|lo>]
 * & lang=<ru-RU|en-US|uk-UK|tr-TR>
 * & speaker=<jane|oksana|alyss|omazh|zahar|ermil>
 * & [speed=<скорость речи>]
 * & [emotion=<good|neutral|evil>]
 * TODO: Новая версия:
 * https://yandex.cloud/ru/docs/speechkit/tts/request
 */

namespace Shcc\YandexBundle;

use ShccBundle\TTS\ProviderInterface;

class TtsApi implements ProviderInterface
{

    const YANDEX_TTS_API_URL = 'https://tts.voicetech.yandex.net/generate';
    const CHMOD = 0750;

    private string $cache_dir;

    private array $params;

    public function __construct(
        array $params,
    ) {
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'format':
                case 'quality':
                case 'lang':
                case 'speaker':
                case 'speed':
                case 'emotion':
                    break;
                default:
                    throw new TtsApiConfigErrorException("Invalid parameter $key");
            }
        }
        $this->params = array_merge([
            'speaker' => 'jane',
            'format' => 'mp3',
            'lang' => 'ru-RU',
        ], $params);
        $this->validateSpeaker($this->params['speaker']);
        $this->validateFormat($this->params['format']);
        $this->validateLang($this->params['lang']);
        if (isset($this->params['quality'])) {
            $this->validateQuality($this->params['quality']);
        }
        if (isset($this->params['speed'])) {
            $this->validateSpeed($this->params['speed']);
        }
        if (isset($this->params['emotion'])) {
            $this->validateEmotion($this->params['emotion']);
        }
        $cache_dir = getenv('CACHE_DIRECTORY');
        if ($cache_dir == '') {
            $cache_dir = '/var/cache/shcc';
        }
        $this->cache_dir = $cache_dir . '/yandex/' . $this->params['speaker'] . '/' . $this->params['lang'] . '/' . $this->params['emotion'] . '/';
    }

    public function setSpeaker(string $speaker): void
    {
        $this->validateSpeaker($speaker);
        $this->params['speaker'] = $speaker;
    }

    public function setLang(string $lang): void
    {
        $this->validateLang($lang);
        $this->params['lang'] = $lang;
    }

    public function setEmotion(string $emotion): void
    {
        $this->validateEmotion($emotion);
        $this->params['emotion'] = $emotion;
    }

    private function validateSpeaker(string $speaker): void
    {
        switch ($speaker) {
            case "jane":
            case "oksana":
            case "alyss":
            case "omazh":
            case "zahar":
            case "ermil":
                return;
        }
        throw new TtsApiConfigErrorException('Invalid value of the speaker parameter');
    }

    private function validateEmotion(string $emotion): void
    {
        switch ($emotion) {
            case "good":
            case "neutral":
            case "evil":
                return;
        }
        throw new TtsApiConfigErrorException('Invalid value of the emotions parameter');
    }

    private function validateFormat(string $format): void
    {
        switch ($format) {
            case "mp3":
            case "wav":
            case "opus":
                return;
        }
        throw new TtsApiConfigErrorException('Invalid value of the format parameter');
    }

    private function validateQuality(string $quality): void
    {
        switch ($quality) {
            case "hi":
            case "lo":
                return;
        }
        throw new TtsApiConfigErrorException('Invalid value of the quality parameter');
    }

    private function validateLang(string $lang): void
    {
        switch ($lang) {
            case "ru-RU":
            case "en-US":
            case "uk-UK":
            case "tr-TR":
                return;
        }
        throw new TtsApiConfigErrorException('Invalid value of the lang parameter');
    }

    private function validateSpeed(float $speed): void
    {
        if ($speed < 0.1 or $speed > 3.0) {
            throw new TtsApiConfigErrorException('The "speed" value is not in the range of 0.1 to 3.0');
        }
    }

    public function getUrl($text): string
    {
        $params = $this->params;
        $params['text'] = $text;
        return self::YANDEX_TTS_API_URL . '?' . http_build_query($params);
    }

    public function requestApi($filename, $text): void
    {
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename) . '/', self::CHMOD, true);
        }
        file_put_contents($filename, file_get_contents($this->getUrl($text)));
    }

    public function getVoiceFile($text): ?string
    {
        $filename = $this->cache_dir . md5($text) . '.' . $this->params['format'];
        if (!file_exists($filename)) {
            $this->requestApi($filename, $text);
        }
        $realpath = realpath($filename);
        if (!$realpath) {
            return null;
        }
        return $realpath;
    }
}
