<?php

namespace Ts\Superkicker\SuperkickerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ts\Superkicker\SuperkickerBundle\Domain\Model\Tip;
use Ts\Superkicker\SuperkickerBundle\Domain\Model\User;

abstract class AbstractController extends Controller {

	/**
	 * @var \Symfony\Component\Security\Core\SecurityContext
	 */
	protected $securityContext;

	/**
	 * @return \Symfony\Component\Security\Core\SecurityContext
	 */
	public function getSecurityContext() {
		return $this->securityContext;
	}

	/**
	 * @param \Symfony\Component\Security\Core\SecurityContext $securityContext
	 */
	public function setSecurityContext($securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * @return User
	 */
	protected function getCurrentLoginUser() {
		return $this->securityContext->getToken()->getUser();
	}

	/**
	 * @param integer $matchDay
	 * @return mixed
	 */
	protected function getNextMatchDay($matchDay) {
		return min($matchDay + 1, 34);
	}

	/**
	 * @param integer $matchDay
	 * @return mixed
	 */
	protected function getPreviousMatchDay($matchDay) {
		return max(1, $matchDay - 1);
	}
}