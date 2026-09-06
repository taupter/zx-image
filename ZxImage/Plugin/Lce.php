<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Gigascreen\GigascreenLoader;
use ZxImage\Plugin\Lce\LcePixelRenderer;
use ZxImage\Service\GigascreenScreenParser;
use ZxImage\Service\PluginServices;

final class Lce implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 13824;
    private const int WIDTH = 512;
    private const int HEIGHT = 384;
    private const int BORDER_WIDTH = 64;
    private const int BORDER_HEIGHT = 48;
    private const int FLASH_DELAY = 32;

    private PluginInput $input;
    private PluginGeometry $screenGeometry;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->screenGeometry = new PluginGeometry(requiredFileSize: self::REQUIRED_FILE_SIZE);
        $this->geometry = new PluginGeometry(
            width: self::WIDTH,
            height: self::HEIGHT,
            borderWidth: self::BORDER_WIDTH,
            borderHeight: self::BORDER_HEIGHT,
        );
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    #[Override]
    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    #[Override]
    public function convertFrames(): ?FrameSet
    {
        $dualRawScreen = (new GigascreenLoader())->loadFrom($this->input, $this->screenGeometry, $this->services);
        if ($dualRawScreen === null) {
            return null;
        }

        $screenParser = new GigascreenScreenParser();
        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $firstScreen = $screenParser->parse($dualRawScreen->first, $this->screenGeometry);
        $secondScreen = $screenParser->parse($dualRawScreen->second, $this->screenGeometry);

        return new FrameSet(
            $this->buildFrames($firstScreen, $secondScreen, $colorTable),
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }

    /**
     * @return list<Frame>
     */
    private function buildFrames(
        ParsedScreen $firstScreen,
        ParsedScreen $secondScreen,
        ColorTable $colorTable,
    ): array {
        $renderer = new LcePixelRenderer();
        $hasFlash = $this->hasFlash($firstScreen, $secondScreen);
        if ($hasFlash === false) {
            return [
                new Frame($renderer->render($firstScreen, $secondScreen, $colorTable, false, $this->screenGeometry)),
            ];
        }

        return [
            new Frame(
                $renderer->render($firstScreen, $secondScreen, $colorTable, false, $this->screenGeometry),
                self::FLASH_DELAY,
                $this->renderSettings,
            ),
            new Frame(
                $renderer->render($firstScreen, $secondScreen, $colorTable, true, $this->screenGeometry),
                self::FLASH_DELAY,
                $this->renderSettings,
            ),
        ];
    }

    private function hasFlash(ParsedScreen $firstScreen, ParsedScreen $secondScreen): bool
    {
        return count($firstScreen->attributes->flashMap) > 0
            || count($secondScreen->attributes->flashMap) > 0;
    }
}
