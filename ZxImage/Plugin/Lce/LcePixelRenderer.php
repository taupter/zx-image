<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Lce;

use GdImage;
use RuntimeException;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;

final readonly class LcePixelRenderer
{
    private const int HORIZONTAL_SCALE = 2;
    private const int VERTICAL_SCALE = 2;

    public function render(
        ParsedScreen $firstScreen,
        ParsedScreen $secondScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $screenGeometry,
    ): GdImage {
        $image = imagecreatetruecolor(
            $screenGeometry->width * self::HORIZONTAL_SCALE,
            $screenGeometry->height * self::VERTICAL_SCALE,
        );
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }

        foreach (array_keys($firstScreen->pixelsData) as $y) {
            $targetY = $y * self::VERTICAL_SCALE;
            $this->renderRow($image, $firstScreen, $y, $targetY, $colorTable, $flashedImage, $screenGeometry);
            $this->renderRow($image, $secondScreen, $y, $targetY + 1, $colorTable, $flashedImage, $screenGeometry);
        }

        return $image;
    }

    private function renderRow(
        GdImage $image,
        ParsedScreen $parsedScreen,
        int $sourceY,
        int $targetY,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $screenGeometry,
    ): void {
        $mapY = intdiv($sourceY, $screenGeometry->attributeHeight);

        foreach ($parsedScreen->pixelsData[$sourceY] as $x => $pixel) {
            $mapX = intdiv($x, $screenGeometry->attributeWidth);
            $color = $colorTable->colors[$this->resolveColor($parsedScreen, $pixel, $mapX, $mapY, $flashedImage)];
            $targetX = $x * self::HORIZONTAL_SCALE;
            imagesetpixel($image, $targetX, $targetY, $color);
            imagesetpixel($image, $targetX + 1, $targetY, $color);
        }
    }

    private function resolveColor(
        ParsedScreen $parsedScreen,
        int $pixel,
        int $mapX,
        int $mapY,
        bool $flashedImage,
    ): int {
        if ($flashedImage && isset($parsedScreen->attributes->flashMap[$mapY][$mapX])) {
            return $pixel === 1
                ? $parsedScreen->attributes->paperMap[$mapY][$mapX]
                : $parsedScreen->attributes->inkMap[$mapY][$mapX];
        }

        return $pixel === 1
            ? $parsedScreen->attributes->inkMap[$mapY][$mapX]
            : $parsedScreen->attributes->paperMap[$mapY][$mapX];
    }
}
