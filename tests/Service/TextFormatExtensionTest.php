<?php

namespace App\Tests\Service;

use App\Twig\TextFormatExtension;
use PHPUnit\Framework\TestCase;

class TextFormatExtensionTest extends TestCase
{
    public function testSmartTruncateKeepsShortText(): void
    {
        $extension = new TextFormatExtension();

        self::assertSame('Charges fixes', $extension->smartTruncate('Charges fixes', 24));
    }

    public function testSmartTruncateCutsAtLastWordWhenPossible(): void
    {
        $extension = new TextFormatExtension();

        self::assertSame('Abonnements...', $extension->smartTruncate('Abonnements services numériques', 20));
        self::assertSame('Salaire de la mort...', $extension->smartTruncate('Salaire de la mort qui tue', 22));
    }

    public function testSmartTruncateCutsLongWordWhenNeeded(): void
    {
        $extension = new TextFormatExtension();

        self::assertSame('CategorieTresLong...', $extension->smartTruncate('CategorieTresLongueSansEspace', 20));
    }
}
