<?php
declare(strict_types=1);

namespace Tests\App\DsShared\Utilities\EntityContext;

use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use Tests\App\BaseTemplateTestCase;
use Tests\App\TwigEnvironmentProvider;

class EntityContextTemplateTest extends BaseTemplateTestCase
{
    /**
     * @dataProvider entityContextDataProvider
     */
    public function testEntityContext($entity, $expectedOutput)
    {
        $presenterFactory = TwigEnvironmentProvider::dsSharedPresenterFactory();
        $presenter = $presenterFactory->entityContextPresenter($entity);
        $html = $this->presenterHtml($presenter);

        $this->assertEquals($expectedOutput, $html);
    }

    public function entityContextDataProvider()
    {
        $greatGrandparent = $this->buildMockCoreEntity('b0000001', 'great-grandparent', []);
        $grandparent = $this->buildMockCoreEntity('b0000002', 'grandparent', [$greatGrandparent]);
        $parent = $this->buildMockCoreEntity('b0000003', 'parent', [$grandparent, $greatGrandparent]);
        $child = $this->buildMockCoreEntity('b0000004', 'child', [$parent, $grandparent, $greatGrandparent]);

        return [
            'one item' => [$greatGrandparent, '<a class="context__item" href="/programmes/b0000001" tabindex="-1">great-grandparent</a>'],
            'two items' => [$grandparent, '<a class="context__item" href="/programmes/b0000001" tabindex="-1">great-grandparent</a> <a class="context__item" href="/programmes/b0000002" tabindex="-1">grandparent</a>'],
            'three items' => [$parent, '<a class="context__item" href="/programmes/b0000001" tabindex="-1">great-grandparent</a> <a class="context__item" href="/programmes/b0000002" tabindex="-1">grandparent</a>, <a class="context__item" href="/programmes/b0000003" tabindex="-1">parent</a>'],
            'four items' => [$child, '<a class="context__item" href="/programmes/b0000001" tabindex="-1">great-grandparent</a> <a class="context__item" href="/programmes/b0000002" tabindex="-1">grandparent</a>, <a class="context__item" href="/programmes/b0000003" tabindex="-1">parent</a>, <a class="context__item" href="/programmes/b0000004" tabindex="-1">child</a>'],
        ];
    }

    private function buildMockCoreEntity(string $pid, string $title, array $parents)
    {
        $programme = $this->createMock(CoreEntity::class);
        $programme->method('getPid')->willReturn(new Pid($pid));
        $programme->method('getTitle')->willReturn($title);
        $programme->method('getAncestry')->willReturn(array_merge([$programme], $parents));

        return $programme;
    }
}
