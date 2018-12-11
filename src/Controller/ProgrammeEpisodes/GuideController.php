<?php
declare(strict_types = 1);

namespace App\Controller\ProgrammeEpisodes;

use App\Controller\Helpers\StructuredDataHelper;
use App\Controller\Traits\IndexerTrait;
use App\Ds2013\PresenterFactory;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Domain\Entity\Series;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use Symfony\Component\HttpFoundation\Request;

class GuideController extends BaseProgrammeEpisodesController
{
    use IndexerTrait;

    const LIMIT = 30;

    public function __invoke(
        ProgrammeContainer $programme,
        PresenterFactory $presenterFactory,
        ProgrammesService $programmesService,
        CollapsedBroadcastsService $collapsedBroadcastService,
        Request $request,
        StructuredDataHelper $structuredDataHelper
    ) {
        $this->setContextAndPreloadBranding($programme);
        $this->setInternationalStatusAndTimezoneFromContext($programme);
        $this->setIstatsProgsPageType('episodes_guide');
        $this->setAtiContentId((string) $programme->getPid(), 'pips');
        $page = $this->getPage();

        $children = $programmesService->findEpisodeGuideChildren(
            $programme,
            self::LIMIT,
            $page
        );

        // If you visit an out-of-bounds page then throw a 404. Page one should
        // always be a 200 so search engines don't drop their reference to the
        // page if a programme has no episodes
        if (!$children && $page !== 1) {
            throw $this->createNotFoundException('Page does not exist');
        }

        $paginator = null;

        if ($children) {
            $totalChildrenCount = $programmesService->countEpisodeGuideChildren($programme);

            if ($totalChildrenCount > self::LIMIT) {
                $paginator = new PaginatorPresenter($page, self::LIMIT, $totalChildrenCount);
            }
        }

        if ($programme->getAvailableEpisodesCount() >= 1) {
            $this->setIstatsExtraLabels(['has_available_items' => 'true']);
        } else {
            $this->setIstatsExtraLabels(['has_available_items' => 'false']);
        }

        $subNavPresenter = $this->getSubNavPresenter($collapsedBroadcastService, $programme, $presenterFactory);
        $upcomingBroadcasts = $this->getUpcomingBroadcastsIndexedByProgrammePid($programme, $collapsedBroadcastService);

        $schema = $this->getSchema($structuredDataHelper, $programme, $children, $upcomingBroadcasts);

        $this->overridenDescription = 'All episodes of ' . $programme->getTitle();

        return $this->renderWithChrome('programme_episodes/guide.html.twig', [
            'programme' => $programme,
            'children' => $children,
            'paginatorPresenter' => $paginator,
            'subNavPresenter' => $subNavPresenter,
            'upcomingBroadcasts' => $upcomingBroadcasts,
            'schema' => $schema,
        ]);
    }

    /**
     * @param StructuredDataHelper $structuredDataHelper
     * @param ProgrammeContainer $programmeContainer
     * @param (Episode|Series)[] $children
     * @param CollapsedBroadcast[] $upcomingBroadcast
     * @return array
     */
    private function getSchema(StructuredDataHelper $structuredDataHelper, ProgrammeContainer $programmeContainer, array $children, array $upcomingBroadcast): array
    {
        $schemaContext = $this->getSchemaForProgrammeContainerAndParents($structuredDataHelper, $programmeContainer);

        foreach ($children as $child) {
            if ($child instanceof Series) {
                $schemaContext['containsSeason'][] = $structuredDataHelper->getSchemaForProgrammeContainer($child);
            } elseif ($child instanceof Episode) {
                $episodeSchema = $structuredDataHelper->getSchemaForEpisode($child, false);
                $cb = $od = null;
                if (isset($upcomingBroadcast[(string) $child->getPid()])) {
                    $cb = $structuredDataHelper->getSchemaForCollapsedBroadcast($upcomingBroadcast[(string) $child->getPid()]);
                }
                if ($child->hasPlayableDestination()) {
                    $od = $structuredDataHelper->getSchemaForOnDemand($child);
                }
                if ($cb || $od) {
                    $episodeSchema['publication'] = ($cb && $od) ? [$cb, $od] : ($cb ?? $od);
                }
                $schemaContext['episode'][] = $episodeSchema;
            }
        }

        return $structuredDataHelper->prepare($schemaContext);
    }
}
