<?php
declare(strict_types = 1);
namespace App\Controller\FindByPid;

use App\Controller\BaseController;
use App\Controller\Helpers\StructuredDataHelper;
use App\Ds2013\PresenterFactory;
use App\ExternalApi\Ada\Service\AdaClassService;
use App\ExternalApi\Ada\Service\AdaProgrammeService;
use App\ExternalApi\Electron\Service\ElectronService;
use App\ExternalApi\FavouritesButton\Service\FavouritesButtonService;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Contribution;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\ContributionsService;
use BBC\ProgrammesPagesService\Service\GroupsService;
use BBC\ProgrammesPagesService\Service\PodcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\PromotionsService;
use BBC\ProgrammesPagesService\Service\RelatedLinksService;
use BBC\ProgrammesPagesService\Service\SegmentEventsService;
use BBC\ProgrammesPagesService\Service\VersionsService;
use GuzzleHttp\Promise\FulfilledPromise;

class EpisodeController extends BaseController
{
    public function __invoke(
        Episode $episode,
        ContributionsService $contributionsService,
        ProgrammesService $programmesService,
        ProgrammesAggregationService $aggregationService,
        PromotionsService $promotionsService,
        RelatedLinksService $relatedLinksService,
        CollapsedBroadcastsService $collapsedBroadcastsService,
        FavouritesButtonService $favouritesButtonService,
        VersionsService $versionsService,
        SegmentEventsService $segmentEventsService,
        ElectronService $electronService,
        AdaProgrammeService $adaProgrammeService,
        AdaClassService $adaClassService,
        GroupsService $groupsService,
        PresenterFactory $presenterFactory,
        StructuredDataHelper $structuredDataHelper,
        PodcastsService $podcastsService
    ) {
        $this->setIstatsProgsPageType('programmes_episode');
        $this->setContextAndPreloadBranding($episode);
        $this->setInternationalStatusAndTimezoneFromContext($episode);
        $this->setAtiContentId((string) $episode->getPid(), 'pips');
        $this->addAtiStatsExtraLabels(['contentType' => 'episode']);

        $linkedVersions = $versionsService->findLinkedVersionsForProgrammeItem($episode);
        $alternateVersions = [];
        if ($episode->isStreamableAlternate()) {
            $alternateVersions = $versionsService->findAlternateVersionsForProgrammeItem($episode);
        }

        $clips = [];
        if ($episode->getAvailableClipsCount() > 0) {
            $clips = $aggregationService->findStreamableDescendantClips($episode, 4);
        }

        $galleries = [];
        if ($episode->getAggregatedGalleriesCount() > 0) {
            $galleries = $aggregationService->findDescendantGalleries($episode, 4);
        }

        $contributions = [];
        if ($episode->getContributionsCount() > 0) {
            $contributions = $contributionsService->findByContributionToProgramme($episode);
        }

        $relatedLinks = [];
        if ($episode->getRelatedLinksCount() > 0) {
            $relatedLinks = $relatedLinksService->findByRelatedToProgramme($episode, ['related_site', 'miscellaneous']);
        }

        $upcomingBroadcasts = [];
        $lastOnBroadcasts = [];
        $allBroadcasts = [];
        if ($episode->getFirstBroadcastDate()) {
            // Only search for broadcasts if a programme has them
            $upcomingBroadcasts = $collapsedBroadcastsService->findUpcomingByProgrammeWithFullServicesOfNetworksList($episode, 1);
            $lastOnBroadcasts = $collapsedBroadcastsService->findPastByProgrammeWithFullServicesOfNetworksList($episode, 1);
            $allBroadcasts = $collapsedBroadcastsService->findByProgrammeWithFullServicesOfNetworksList($episode, 100);
        }

        $featuredIn = $groupsService->findByCoreEntityMembership($episode, 'Collection');

        // TODO check $episode->getPromotionsCount() once it is populated in
        // Faucet to potentially save on a DB query
        $promotions = $promotionsService->findActivePromotionsByEntityGroupedByType($episode);

        /** @var Episode|null $nextEpisode */
        $nextEpisode = null;
        /** @var Episode|null $previousEpisode */
        $previousEpisode = null;

        $upcomingBroadcast = !empty($upcomingBroadcasts) ? reset($upcomingBroadcasts) : null;
        $lastOnBroadcast = !empty($lastOnBroadcasts) ? reset($lastOnBroadcasts) : null;
        $firstBroadcast = !empty($allBroadcasts) ? reset($allBroadcasts) : null;

        if (!$episode->isTleo()) {
            $nextEpisode = $programmesService->findNextSiblingByProgramme($episode);
            $previousEpisode = $programmesService->findPreviousSiblingByProgramme($episode);
        }

        $segmentsListPresenter = null;
        if ($episode->getSegmentEventCount() > 0 && $linkedVersions['canonicalVersion']) {
            $segmentEvents = $segmentEventsService->findByVersionWithContributions($linkedVersions['canonicalVersion']);
            if ($segmentEvents) {
                $segmentsListPresenter = $presenterFactory->segmentsListPresenter(
                    $episode,
                    $segmentEvents,
                    $firstBroadcast
                );
            }
        }

        $relatedProgrammesPromise = new FulfilledPromise([]);
        $relatedTopicsPromise = new FulfilledPromise([]);
        if ($episode->getOption('show_enhanced_navigation')) {
            // Less than 50 episodes (through ancestry)...
            $tleo = $episode->getTleo();
            $usePerContainerValues = false;
            if ($tleo instanceof ProgrammeContainer) {
                $usePerContainerValues = $tleo->getAggregatedEpisodesCount() >= 50;
            }
            $relatedTopicsPromise = $adaClassService->findRelatedClassesByContainer($episode, $usePerContainerValues, 10);
            $relatedProgrammesPromise = $adaProgrammeService->findSuggestedByProgrammeItem($episode);
        }

        $supportingContentItemsPromise = $electronService->fetchSupportingContentItemsForProgramme($episode);

        $this->setIstatsPastBroadcastLabel($lastOnBroadcast);
        $this->setIstatsUpcomingLabel($upcomingBroadcast);
        $this->setIstatsLiveEpisodeLabel($upcomingBroadcast);

        $resolvedPromises = $this->resolvePromises([
            'favouritesButton' => $favouritesButtonService->getContent(),
            'relatedTopics' => $relatedTopicsPromise,
            'relatedProgrammes' => $relatedProgrammesPromise,
            'supportingContentItems' => $supportingContentItemsPromise,
        ]);

        $podcast = null;
        if ($episode->getTleo() instanceof ProgrammeContainer && $episode->getTleo()->isPodcastable()) {
            $podcast = $podcastsService->findByCoreEntity($episode->getTleo());
        }

        $episodeMapPresenter = $presenterFactory->episodeMapPresenter(
            $episode,
            $linkedVersions['downloadableVersion'],
            $alternateVersions,
            $upcomingBroadcast,
            $lastOnBroadcast,
            $nextEpisode,
            $previousEpisode,
            $podcast
        );

        $schema = $this->getSchema($structuredDataHelper, $episode, $upcomingBroadcast, $clips, $contributions);
        $parameters = [
            'schema' => $schema,
            'contributions' => $contributions,
            'programme' => $episode,
            'clips' => $clips,
            'galleries' => $galleries,
            'relatedLinks' => $relatedLinks,
            'featuredIn' => $featuredIn,
            'promotions' => $promotions,
            'allBroadcasts' => $allBroadcasts,
            'episodeMapPresenter' => $episodeMapPresenter,
            'segmentsListPresenter' => $segmentsListPresenter,
            'podcastedBy' => ($podcast !== null) ? $episode->getTleo() : null,
        ];

        $parameters = array_merge($parameters, $resolvedPromises);
        return $this->renderWithChrome('find_by_pid/episode.html.twig', $parameters);
    }

    private function getSchema(
        StructuredDataHelper $structuredDataHelper,
        Episode $episode,
        ?CollapsedBroadcast $upcomingBroadcast,
        array $clips,
        array $contributions
    ): array {
        $schemaContext = $structuredDataHelper->getSchemaForEpisode($episode, true);
        if ($upcomingBroadcast) {
            if ($episode->hasPlayableDestination()) {
                $schemaContext['publication'] = [
                    $structuredDataHelper->getSchemaForOnDemand($episode),
                    $structuredDataHelper->getSchemaForCollapsedBroadcast($upcomingBroadcast),
                ];
            } else {
                $schemaContext['publication'] = $structuredDataHelper->getSchemaForCollapsedBroadcast($upcomingBroadcast);
            }
        } elseif ($episode->hasPlayableDestination()) {
            $schemaContext['publication'] = $structuredDataHelper->getSchemaForOnDemand($episode);
        }

        foreach ($clips as $clip) {
            $schemaContext['hasPart'][] = $structuredDataHelper->buildSchemaForClip($clip);
        }

        $actors = [];
        $contributors = [];
        /** @var Contribution $contribution */
        foreach ($contributions as $contribution) {
            if ($contribution->getCharacterName()) {
                $actors[] = $structuredDataHelper->getSchemaForActorContribution($contribution);
            } else {
                $contributors[] = $structuredDataHelper->getSchemaForNonActorContribution($contribution);
            }
        }

        if ($actors) {
            $schemaContext['actor'] = $actors;
        }
        if ($contributors) {
            $schemaContext['contributor'] = $contributors;
        }

        return $structuredDataHelper->prepare($schemaContext);
    }

    private function setIstatsPastBroadcastLabel(?CollapsedBroadcast $lastOn) :void
    {
        $hasBroadcastInLast18Months = $lastOn ? $lastOn->getStartAt()->wasWithinLast('18 months') : false;
        if ($hasBroadcastInLast18Months) {
            $this->setIstatsExtraLabels(['past_broadcast' => 'true']);
        } else {
            $this->setIstatsExtraLabels(['past_broadcast' => 'false']);
        }
    }

    private function setIstatsUpcomingLabel(?CollapsedBroadcast $upcomingBroadcast): void
    {
        if (empty($upcomingBroadcast)) {
            $this->setIstatsExtraLabels(['upcoming' => 'false']);
        } else {
            $this->setIstatsExtraLabels(['upcoming' => 'true']);
        }
    }

    private function setIstatsLiveEpisodeLabel(?CollapsedBroadcast $upcomingBroadcast): void
    {
        if (!empty($upcomingBroadcast) && $upcomingBroadcast->isOnAir()) {
            $this->setIstatsExtraLabels(['live_episode' => 'true']);
        } else {
            $this->setIstatsExtraLabels(['live_episode' => 'false']);
        }
    }
}
