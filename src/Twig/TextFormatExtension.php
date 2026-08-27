<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TextFormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('smart_truncate', [$this, 'smartTruncate']),
        ];
    }

    public function smartTruncate(?string $value, int $limit = 24, string $suffix = '...'): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $value));
        if ($limit <= 0 || $this->length($text) <= $limit){
            return $text;
        }

        $suffixLength = $this->length($suffix);
        $maxLength = max(1, $limit - $suffixLength);
        $cut = rtrim($this->slice($text, 0, $maxLength));
        $nextCharacter = $this->slice($text, $maxLength, 1);
        $lastCharacter = $this->slice($cut, max(0, $this->length($cut) - 1), 1);

        if (
            '' !== $nextCharacter
            && !$this->isSpace($nextCharacter)
            && !$this->isSpace($lastCharacter)
            && preg_match('/^(.+)\s+\S*$/u', $cut, $matches)
            && $this->length($matches[1]) >= (int) floor($maxLength * 0.55)
        ){
            $cut = rtrim($matches[1]);
        }

        return $cut.$suffix;
    }

    private function length(string $value): int
    {
        if ('' === $value){
            return 0;
        }

        return preg_match_all('/./us', $value) ?: strlen($value);
    }

    private function slice(string $value, int $offset, int $length): string
    {
        if (preg_match_all('/./us', $value, $matches)){
            return implode('', array_slice($matches[0], $offset, $length));
        }

        return substr($value, $offset, $length);
    }

    private function isSpace(string $value): bool
    {
        return '' !== $value && 1 === preg_match('/^\s$/u', $value);
    }
}
