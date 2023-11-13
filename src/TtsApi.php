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
 */

namespace SHCC\Yandex;

use ShccBundle\TTS\ProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TtsApi implements ProviderInterface
{

    const YANDEX_TTS_API_URL = 'https://tts.voicetech.yandex.net/generate';
    const CHMOD = 0750;

    private string $cache_dir;

    private array $params;

    public function __construct(
        #[Autowire(param: 'tts.yandex')]
        array $params,
    ) {
        $this->params = array_merge([
            'speaker' => 'jane',
            'format' => 'mp3',
            'lang' => 'ru-RU',
            'emotion' => 'neutral'
        ], $params);
        $this->validateSpeaker($this->params['speaker']);
        $this->validateEmotion($this->params['emotion']);
        $cache_dir = getenv('CACHE_DIRECTORY');
        if ($cache_dir == '') {
            $cache_dir = '/var/cache/shcc';
        }
        $this->cache_dir = $cache_dir . '/yandex/' . $this->params['speaker'] . '/' . $this->params['lang'] . '/' . $this->params['emotion'] . '/';
    }

    public function setSpeaker($name): void
    {
        if ($this->validateSpeaker($name)) {
            $this->params['speaker'] = $name;
            return;
        }
        throw new YandexException('Unknown speaker');
    }

    private function validateSpeaker($name): bool
    {
        switch ($name) {
            case "jane":
            case "oksana":
            case "alyss":
            case "omazh":
            case "zahar":
            case "ermil":
                return true;
                break;
        }
        return false;
    }

    public function setEmotion($name): void
    {
        if ($this->validateEmotion($name)) {
            $this->params['emotion'] = $name;
            return;
        }
        throw new YandexException('Unknown emotions');
    }

    private function validateEmotion($name): bool
    {
        switch ($name) {
            case "good":
            case "neutral":
            case "evil":
                return true;
                break;
        }
        return false;
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
