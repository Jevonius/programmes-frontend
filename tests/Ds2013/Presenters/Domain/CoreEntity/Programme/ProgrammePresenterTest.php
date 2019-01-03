<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Domain\CoreEntity\Programme;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\ProgrammePresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeBodyPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeOverlayPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters\CoreEntityTitlePresenter;
use App\DsShared\Helpers\HelperFactory;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\DsShared\Helpers\TitleLogicHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProgrammePresenterTest extends TestCase
{
    private $mockRouter;

    private $mockHelperFactory;

    public function setUp()
    {
        $this->mockRouter = $this->createMock(UrlGeneratorInterface::class);
        $this->mockHelperFactory = $this->createMock(HelperFactory::class);
        $playTranslationHelper = $this->createMock(PlayTranslationsHelper::class);
        $this->mockHelperFactory->method('getPlayTranslationsHelper')->willReturn($playTranslationHelper);
        $titleLogicHelper = $this->createMock(TitleLogicHelper::class);
        $this->mockHelperFactory->method('getTitleLogicHelper')->willReturn($titleLogicHelper);
    }

    public function testGetProgrammeOverlayPresenterAndOptions()
    {
        $options = [
            'branding_context' => 'subtle',
            'context_programme' => $this->createMock(Brand::class),
            'truncation_length' => 50,
            'image_options' => [
                'is_lazy_loaded' => false,
                'sizes' => [
                    0 => '0vw',
                    320 => 1 / 6,
                    480 => 1 / 2,
                    600 => 1,
                ],
            ],
        ];
        $programme = $this->createMock(Brand::class);
        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme,
            $options
        );
        $programmeOverlayPresenter = $programmePresenter->getProgrammeOverlayPresenter();
        $this->assertInstanceOf(ProgrammeOverlayPresenter::class, $programmeOverlayPresenter);
        $expectedOptions = array_merge($options, $options['image_options']);
        unset($expectedOptions['image_options']);
        foreach ($expectedOptions as $key => $value) {
            $this->assertEquals($value, $programmeOverlayPresenter->getOption($key));
        }
    }

    public function testGetProgrammeTitlePresenterAndOptions()
    {
        $options = [
            'branding_context' => 'page',
            'context_programme' => $this->createMock(Clip::class),
            'truncation_length' => 500,
            'title_options' => [
                'title_format' => 'item::ancestry',
                'title_tag' => 'h4',
            ],
        ];
        $programme = $this->createMock(Brand::class);
        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme,
            $options
        );
        $programmeTitlePresenter = $programmePresenter->getProgrammeTitlePresenter();
        $this->assertInstanceOf(CoreEntityTitlePresenter::class, $programmeTitlePresenter);
        $expectedOptions = array_merge($options, $options['title_options']);
        unset($expectedOptions['title_options']);
        foreach ($expectedOptions as $key => $value) {
            $this->assertEquals($value, $programmeTitlePresenter->getOption($key));
        }
    }

    public function testGetProgrammeBodyPresenterAndOptions()
    {
        $options = [
            'branding_context' => 'highlight',
            'context_programme' => $this->createMock(Episode::class),
            'truncation_length' => null,
            'body_options' => [
                'show_synopsis' => false,
                'show_duration' => true,
            ],
        ];
        $programme = $this->createMock(Clip::class);
        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme,
            $options
        );
        $programmeBodyPresenter = $programmePresenter->getProgrammeBodyPresenter();
        $this->assertInstanceOf(ProgrammeBodyPresenter::class, $programmeBodyPresenter);
        $expectedOptions = array_merge($options, $options['body_options']);
        unset($expectedOptions['body_options']);
        foreach ($expectedOptions as $key => $value) {
            $this->assertEquals($value, $programmeBodyPresenter->getOption($key));
        }
    }

    /**
     * @dataProvider getOuterDivClassesProvider
     */
    public function testGetOuterDivClasses($programme, $options, $expectedResultContains)
    {
        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme,
            $options
        );
        $outerDivClassArray = $programmePresenter->getOuterDivClasses();
        foreach ($expectedResultContains as $expectedKey) {
            $this->assertTrue($outerDivClassArray[$expectedKey]);
        }
    }

    public function getOuterDivClassesProvider()
    {
        return [
            [
                $this->buildMockProgrammeForDiv(Episode::class, 'tv'), // programme
                [// options
                    'branding_context' => 'subtle',
                ],
                [// result expected
                    'programme', 'programme--tv', 'programme--episode', 'block-link',
                ],
            ],
            [
                $this->buildMockProgrammeForDiv(Episode::class, 'radio'), // programme
                [// options
                    'branding_context' => 'subtle',
                    'highlight_box_classes' => 'highlight-box--list',
                ],
                [// result expected
                    'programme', 'programme--radio', 'programme--episode', 'block-link',
                    'highlight-box--list', 'br-keyline', 'br-blocklink-subtle', 'br-subtle-linkhover-onbg015--hover',
                ],
            ],
            [
                $this->buildMockProgrammeForDiv(Clip::class, 'radio'), // programme
                [// options
                    'branding_context' => 'subtle',
                    'container_classes' => 'cclass',
                ],
                [// result expected
                    'programme', 'programme--clip', 'block-link', 'cclass',
                ],
            ],
        ];
    }

    /**
     * @expectedException \App\Exception\InvalidOptionException
     */
    public function testInvalidContextProgramme()
    {
        $options = [
            'context_programme' => 'A small jar of elderberry jam',
        ];
        $programme = $this->createMock(Clip::class);
        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme,
            $options
        );
    }

    public function testHasFutureAvailability()
    {
        $programme = $this->createMock(Clip::class);
        $programme->expects($this->once())
            ->method('hasFutureAvailability')
            ->willReturn(true);

        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme
        );
        $this->assertTrue($programmePresenter->hasFutureAvailability());
    }

    public function testHasNotFutureAvailability()
    {
        $programme = $this->createMock(Clip::class);
        $programme->expects($this->once())
            ->method('hasFutureAvailability')
            ->willReturn(false);

        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme
        );
        $this->assertFalse($programmePresenter->hasFutureAvailability());
    }

    public function testIsAvailable()
    {
        $programme = $this->createMock(Clip::class);
        $programme->expects($this->once())
            ->method('hasPlayableDestination')
            ->willReturn(true);

        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme
        );
        $this->assertTrue($programmePresenter->isAvailable());
    }

    public function testIsNotAvailable()
    {
        $programme = $this->createMock(Clip::class);
        $programme->expects($this->once())
            ->method('hasPlayableDestination')
            ->willReturn(false);

        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme
        );
        $this->assertFalse($programmePresenter->isAvailable());
    }

    public function testIsContainer()
    {
        $programme = $this->createMock(Brand::class);
        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme
        );
        $this->assertTrue($programmePresenter->isContainer());
    }

    public function testIsNotContainer()
    {
        $programme = $this->createMock(Episode::class);
        $programmePresenter = new ProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $programme
        );
        $this->assertFalse($programmePresenter->isContainer());
    }

    private function buildMockProgrammeForDiv(
        string $type,
        string $mediaType
    ) {
        $programme = $this->createMock($type);
        if ($mediaType == 'tv') {
            $programme->method('isTv')->willReturn(true);
            $programme->method('isRadio')->willReturn(false);
        } elseif ($mediaType == 'radio') {
            $programme->method('isTv')->willReturn(false);
            $programme->method('isRadio')->willReturn(true);
        } else {
            $programme->method('isTv')->willReturn(false);
            $programme->method('isRadio')->willReturn(false);
        }
        return $programme;
    }
}
