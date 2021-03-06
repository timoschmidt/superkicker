<?php

namespace Ts\Superkicker\SuperkickerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ts\Superkicker\SuperkickerBundle\Domain\Model\Tip;
use Ts\Superkicker\SuperkickerBundle\Domain\Model\Tournament;
use Ts\Superkicker\SuperkickerBundle\Domain\Model\User;
use Ts\Superkicker\SuperkickerBundle\Domain\Model\Match;
use Webforge\Common\DateTime\DateTime;

class TipController extends AbstractController {

	/**
	 * @var \Ts\Superkicker\SuperkickerBundle\Domain\Repository\MatchRepository
	 */
	protected $matchRepository;

	/**
	 * @var \Ts\Superkicker\SuperkickerBundle\Domain\Repository\TipRepository
	 */
	protected $tipRepository;

	/**
	 * @var \Ts\Superkicker\SuperkickerBundle\Domain\Service\ScoreCalculationService
	 */
	protected $scoreCalculationService;

	/**
	 * @param \Ts\Superkicker\SuperkickerBundle\Domain\Service\ScoreCalculationService $scoreCalculationService
	 */
	public function setScoreCalculationService($scoreCalculationService) {
		$this->scoreCalculationService = $scoreCalculationService;
	}

	/**
	 * @return \Ts\Superkicker\SuperkickerBundle\Domain\Repository\MatchRepository
	 */
	public function getMatchRepository() {
		return $this->matchRepository;
	}

	/**
	 * @param \Ts\Superkicker\SuperkickerBundle\Domain\Repository\MatchRepository
	 */
	public function setMatchRepository($matchRepository) {
		$this->matchRepository = $matchRepository;
	}

	/**
	 * @return \Ts\Superkicker\SuperkickerBundle\Domain\Repository\TipRepository
	 */
	public function getTipRepository() {
		return $this->tipRepository;
	}

	/**
	 * @param \Ts\Superkicker\SuperkickerBundle\Domain\Repository\TipRepository $tipRepository
	 */
	public function setTipRepository($tipRepository) {
		$this->tipRepository = $tipRepository;
	}

	/**
	 * @param int $matchDay
	 * @param int $tournamentId
	 * @param int $saved
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function editAction($matchDay = 1, $tournamentId = 1, $saved = 0) {
		$matches 	= $this->matchRepository->findByMatchDayAndTournament($matchDay, $tournamentId);
		$tips 		= $this->tipRepository->findByUserAndMatchDayAndTournament($this->getCurrentLoginUser(), $matchDay, $tournamentId);

			/** @var $tournament Tournament */
		$tournament = $this->getTournamentRepository()->findById($tournamentId);
		$matchDays 	= $tournament->getMatchDays();
		$prevMatchDay = $this->getPreviousMatchDay($matchDay);
		$nextMatchDay = $this->getNextMatchDay($matchDay, $matchDays);

		$matchTips 	= array();
		foreach($matches as $match) {
			/** @var $match Match */
			$matchTips[$match->getId()]['match'] = $match;
			$matchTips[$match->getId()]['score'] = null;
		}

		$scoreForDay = 0;
		foreach($tips as $tip) {
			/** @var $tip Tip */
			if(array_key_exists($tip->getMatch()->getId(), $matchTips)) {
				$matchTips[$tip->getMatch()->getId()]['tip'] = $tip;

				$score = $this->scoreCalculationService->getScoreForSingleTip($tip);
				$matchTips[$tip->getMatch()->getId()]['score'] = $score;
				$scoreForDay += $score;
			} else {
				//tip for non existing match?

			}
		}

		return $this->templating->renderResponse(
			'SuperkickerBundle:Tip:edit.html.twig',
			array(
				'matchTips' => $matchTips,
				'prevMatchDay' => $prevMatchDay,
				'nextMatchDay' => $nextMatchDay,
				'matchDay' => $matchDay,
				'matchDays' => $matchDays,
				'tournamentId' => $tournamentId,
				'scoreForDay' => $scoreForDay,
				'saved' => $saved
			)
		);
	}

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function saveAction(Request $request) {
		$matches = $request->get('match');
		$matchDay = $request->get('matchDay');
		$tournamentId = $request->get('tournamentId');

		$numberOfTipps = 0;

		$editUrl = $this->router->generate('ts_superkicker_tipp_edit',
				array(
						'matchDay' => $matchDay,
						'tournamentId' => $tournamentId,
						'saved' => true
				)
		);

		if(!is_array($matches) || count($matches) == 0) {
			return new RedirectResponse($editUrl);
		}

		foreach($matches as $matchId => $tipData) {
			/** @var $match Match */
			$match 		= $this->matchRepository->findById($matchId);
			if($match->getIsStarted()) {
				//tipps for started matches can not be saved
				continue;
			}

			$savedTip 	= $this->tipRepository->findOneByUserAndMatch($this->getCurrentLoginUser(), $match);
			$tip 		= is_null($savedTip) ? new Tip() : $savedTip;

			$tip->setMatch($match);

			$homeScore = null;
			if(isset($tipData['home']) && trim($tipData['home']) !== '') {
				$homeScore = $tipData['home'];
			}

			$guestScore = null;
			if(isset($tipData['guest']) && trim($tipData['guest']) !== '') {
				$guestScore = $tipData['guest'];
			}

			$tip->setHomeScore($homeScore);
			$tip->setGuestScore($guestScore);
			$tip->setUser($this->getCurrentLoginUser());

			$this->tipRepository->save($tip);
			$numberOfTipps++;
		}

		return new RedirectResponse($editUrl);
	}


}