<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Utilities\DateList;

use App\Ds2013\Presenters\Utilities\DateList\YearDateListItemPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class YearDateListPresenterTest extends TestCase
{
    public function testGetLink()
    {
        $now = Chronos::now();
        $offset = 3;
        $pid = new Pid('xxxxxxxx');
        /** @var Service|PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->createMock(Service::class);
        $service->method('getPid')->willReturn($pid);
        /** @var UrlGeneratorInterface|PHPUnit_Framework_MockObject_MockObject $urlGeneratorInterface */
        $urlGeneratorInterface = $this->createMock(UrlGeneratorInterface::class);
        $urlGeneratorInterface->expects($this->once())
            ->method('generate')
            ->with(
                'schedules_by_year',
                ['pid' => (string) $pid, 'year' => $now->addYears($offset)->format('Y')],
                UrlGeneratorInterface::ABSOLUTE_URL
            )->willReturn('aUrl');
        $presenter = new YearDateListItemPresenter($urlGeneratorInterface, $now, $service, $offset, new Chronos('+90 days'));
        $presenter->getLink();
    }
}
